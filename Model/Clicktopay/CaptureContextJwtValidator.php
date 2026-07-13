<?php
/**
 * @copyright 2024 Sapient
 */
namespace Sapient\Worldpay\Model\Clicktopay;

use Exception;
use Magento\Framework\App\CacheInterface;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Math\BigInteger;
use Sapient\Worldpay\Helper\Data as WorldpayHelper;
use Sapient\Worldpay\Logger\WorldpayLogger;

class CaptureContextJwtValidator
{
    private const JWT_CLOCK_SKEW = 20;
    private const PUBLIC_KEY_CACHE_TTL = 86400;
    private const PUBLIC_KEY_CACHE_PREFIX = 'cybersource_pubkey_';

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var WorldpayHelper
     */
    private $worldpayHelper;

    /**
     * @var WorldpayLogger
     */
    private $wplogger;

    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(
        ApiClient $apiClient,
        WorldpayHelper $worldpayHelper,
        WorldpayLogger $wplogger,
        CacheInterface $cache
    ) {
        $this->apiClient = $apiClient;
        $this->worldpayHelper = $worldpayHelper;
        $this->wplogger = $wplogger;
        $this->cache = $cache;
    }

    public function validate(array $jwtParts): void
    {
        if (count($jwtParts) !== 3) {
            throw  new Exception('Invalid JWT format: expected 3 parts separated by dots');
        }

        $header = json_decode(base64_decode(strtr($jwtParts[0], '-_', '+/')), true);

        if (!$header || !isset($header['alg']) || !isset($header['kid'])) {
            throw new Exception('Invalid JWT header: missing alg or kid');
        }

        if ($header['alg'] !== 'RS256') {
            throw new Exception('Unexpected JWT signing algorithm: ' . $header['alg']);
        }

        $payload = json_decode(base64_decode(strtr($jwtParts[1], '-_', '+/')), true);

        if (!$payload) {
            throw new Exception('Failed to decode JWT payload');
        }

        $now = time();

        if (isset($payload['exp']) && $now > ($payload['exp'] + self::JWT_CLOCK_SKEW)) {
            $this->wplogger->warning(CaptureContext::LOG_PREFIX . ' JWT has expired', [
                'exp' => $payload['exp'],
                'now' => $now
            ]);
            throw new Exception('Capture Context JWT has expired');
        }

        $this->verifySignature($header['kid'], $jwtParts);

        $this->wplogger->info(CaptureContext::LOG_PREFIX . ' JWT validation passed', ['kid' => $header['kid']]);
    }

    private function verifySignature(string $kid, array $jwtParts): void
    {
        $publicKeyData = $this->fetchPublicKey($kid);

        if (!isset($publicKeyData['n']) || !isset($publicKeyData['e'])) {
            throw new Exception('Public key response missing n or e');
        }

        $pemKey = $this->buildRsaPublicKey($publicKeyData['n'], $publicKeyData['e']);
        $signature = base64_decode(strtr($jwtParts[2], '-_', '+/'));

        $pubKeyResource = openssl_pkey_get_public($pemKey);
        if (!$pubKeyResource) {
            throw new Exception('Failed to parse reconstructed RSA public key');
        }

        $result = openssl_verify($jwtParts[0] . '.' . $jwtParts[1], $signature, $pubKeyResource, OPENSSL_ALGO_SHA256);
        if ($result !== 1) {
            $this->wplogger->warning(CaptureContext::LOG_PREFIX . ' JWT signature verification failed', [
                'kid' => $kid,
                'openssl_error' => openssl_error_string()
            ]);
            throw new Exception('Capture Context JWT signature verification failed');
        }
    }

    private function fetchPublicKey(string $kid): array
    {
        $cacheKey = self::PUBLIC_KEY_CACHE_PREFIX . $kid;
        $cached = $this->cache->load($cacheKey);

        if ($cached) {
            $this->wplogger->info(CaptureContext::LOG_PREFIX . ' Using cached public key', ['kid' => $kid]);
            return json_decode($cached, true);
        }

        $response = $this->apiClient->get(
            $this->worldpayHelper->getCyberSourceBaseUrl() . '/flex/v2/public-keys/' . $kid
        );

        if (!$response) {
            throw new Exception('Failed to fetch public key from CyberSource');
        }

        $decoded = json_decode($response, true);
        if (!$decoded) {
            throw new Exception('Invalid public key response from CyberSource');
        }

        $this->cache->save($response, $cacheKey, [], self::PUBLIC_KEY_CACHE_TTL);
        $this->wplogger->info(CaptureContext::LOG_PREFIX . ' Cached public key', ['kid' => $kid]);

        return $decoded;
    }

    private function buildRsaPublicKey(string $n, string $e): string
    {
        $modulus = new BigInteger(base64_decode(strtr($n, '-_', '+/')), 256);
        $exponent = new BigInteger(base64_decode(strtr($e, '-_', '+/')), 256);

        $publicKey = PublicKeyLoader::load(['n' => $modulus, 'e' => $exponent]);

        return $publicKey->toString('PKCS8');
    }
}
