<?php
/**
 * @copyright 2024 Sapient
 */
namespace Sapient\Worldpay\Model\Clicktopay;

use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Sapient\Worldpay\Api\CaptureContextInterface;
use Sapient\Worldpay\Helper\Data as WorldpayHelper;
use Sapient\Worldpay\Logger\WorldpayLogger;

class CaptureContext implements CaptureContextInterface
{
    public const LOG_PREFIX = '[Click to Pay]';

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var WorldpayHelper
     */
    protected $worldpayHelper;

    /**
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * @var WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var PayloadBuilder
     */
    protected $payloadBuilder;

    /**
     * @var SignatureGenerator
     */
    private $signatureGenerator;

    /**
     * @var CaptureContextJwtValidator
     */
    private $jwtValidator;

    /**
     * Constructor
     *
     * @param CheckoutSession $checkoutSession
     * @param WorldpayHelper $worldpayHelper
     * @param ApiClient $apiClient
     * @param WorldpayLogger $wplogger
     * @param PayloadBuilder $payloadBuilder
     * @param SignatureGenerator $signatureGenerator
     * @param CaptureContextJwtValidator $jwtValidator
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        WorldpayHelper $worldpayHelper,
        ApiClient $apiClient,
        WorldpayLogger $wplogger,
        PayloadBuilder $payloadBuilder,
        SignatureGenerator $signatureGenerator,
        CaptureContextJwtValidator $jwtValidator
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->worldpayHelper = $worldpayHelper;
        $this->apiClient = $apiClient;
        $this->wplogger = $wplogger;
        $this->payloadBuilder = $payloadBuilder;
        $this->signatureGenerator = $signatureGenerator;
        $this->jwtValidator = $jwtValidator;
    }

    public function captureContext(): ?string
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote || !$quote->getId()) {
            return json_encode(['error' => 'No active quote found.']);
        }

        $merchantId = $this->worldpayHelper->getCyberSourceMerchantId();
        $merchantKeyId = $this->worldpayHelper->getCyberSourceKeyId();
        $merchantSecretKey = $this->worldpayHelper->getMerchantSecretKey();

        if (!$merchantId || !$merchantKeyId || !$merchantSecretKey) {
            return json_encode(['error' => 'Click To Pay credentials are not configured.']);
        }

        $decodedSecretKey = base64_decode($merchantSecretKey, true);
        if ($decodedSecretKey !== false) {
            $merchantSecretKey = $decodedSecretKey;
        }
        $payload = $this->payloadBuilder->build($quote);

        $endpoint = $this->worldpayHelper->getCyberSourceBaseUrl() . '/up/v1/capture-contexts';

        $this->wplogger->info(self::LOG_PREFIX . ' Requesting CyberSource Capture Context with JWT authentication', [
            'merchant_id' => $merchantId,
            'endpoint' => $endpoint
        ]);

        $date = gmdate('D, d M Y H:i:s', time()) . ' GMT';
        $digest = 'SHA-256=' . base64_encode(hash('sha256', json_encode($payload), true));

        $signature = $this->signatureGenerator->generate(
            $merchantKeyId,
            $merchantSecretKey,
            $merchantId,
            $endpoint,
            'post',
            $date,
            $digest
        );
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'v-c-merchant-id: ' . $merchantId,
            'date: ' . $date,
            'digest: ' . $digest,
            'signature: ' . $signature
        ];

        $rawResponse = $this->apiClient->post($endpoint, $payload, $headers);

        $response = json_decode($rawResponse, true);

        if ($rawResponse === null) {
            $this->wplogger->info(self::LOG_PREFIX . ' No response received from CyberSource');
            return json_encode(['error' => 'No response received from CyberSource.']);
        }

        if($response && isset($response['error'])) {
            $this->wplogger->info(self::LOG_PREFIX . ' Error requesting CyberSource Capture Context', ['response' => $response]);
            return json_encode(['error' => 'Error from CyberSource: ' . ($response['error']['message'] ?? 'Unknown error')]);
        }

        $this->wplogger->info(self::LOG_PREFIX . ' Successfully obtained CyberSource Capture Context');

        $jwtParts = explode('.', $rawResponse);

        try{
            $this->jwtValidator->validate($jwtParts);
        } catch (Exception $e) {
            return json_encode(['error' => 'JWT validation failed: ' . $e->getMessage()]);
        }

        $payload = json_decode(base64_decode(strtr($jwtParts[1], '-_', '+/')), true);

        if (!$payload || !isset($payload['ctx'][0]['data']['clientLibrary'])) {
            return json_encode(['error' => 'Unable to decode JWT payload or missing clientLibrary']);
        }

        $result = [
            'jwt' => $rawResponse,
            'clientLibrary' => $payload['ctx'][0]['data']['clientLibrary'],
            'clientLibraryIntegrity' => $payload['ctx'][0]['data']['clientLibraryIntegrity'] ?? ''
        ];

        return json_encode($result);
    }
}
