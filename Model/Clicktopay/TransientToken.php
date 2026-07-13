<?php
/**
 * @copyright 2024 Sapient
 */
namespace Sapient\Worldpay\Model\Clicktopay;

use Exception;
use Sapient\Worldpay\Api\TransientTokenInterface;
use Sapient\Worldpay\Logger\WorldpayLogger;

class TransientToken implements TransientTokenInterface
{
    public const LOG_PREFIX = '[Click to Pay]';

    /**
     * @var CaptureContextJwtValidator
     */
    private $jwtValidator;

    /**
     * @var WorldpayLogger
     */
    private $wplogger;

    /**
     * Constructor
     *
     * @param CaptureContextJwtValidator $jwtValidator
     * @param WorldpayLogger $wplogger
     */
    public function __construct(
        CaptureContextJwtValidator $jwtValidator,
        WorldpayLogger $wplogger
    ) {
        $this->jwtValidator = $jwtValidator;
        $this->wplogger = $wplogger;
    }

    public function validateToken(string $transientToken): string
    {
        if (empty($transientToken)) {
            return json_encode(['error' => 'Transient token is required']);
        }

        $jwtParts = explode('.', $transientToken);

        try {
            $this->jwtValidator->validate($jwtParts);
        } catch (Exception $e) {
            return json_encode(['error' => 'Token validation failed: ' . $e->getMessage()]);
        }

        $payload = json_decode(base64_decode(strtr($jwtParts[1], '-_', '+/')), true);

        if (!$payload) {
            return json_encode(['error' => 'Failed to decode token payload']);
        }

        $this->wplogger->info(self::LOG_PREFIX . ' Transient token validated successfully');

        $cardBin = null;

        if (isset($payload['content']['paymentInformation']['tokenizedCard']['number']['bin'])) {
            $cardBin = $payload['content']['paymentInformation']['tokenizedCard']['number']['bin'];
        } elseif (isset($payload['content']['paymentInformation']['card']['number']['bin'])) {
            $cardBin = $payload['content']['paymentInformation']['card']['number']['bin'];
        }

        $result = [
            'valid' => true,
            'payload' => $payload
        ];

        if ($cardBin) {
            $result['tokenizedCardBin'] = $cardBin;
        }

        return json_encode($result);
    }
}
