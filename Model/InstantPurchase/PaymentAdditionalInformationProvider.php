<?php
namespace Sapient\Worldpay\Model\InstantPurchase;

use Magento\InstantPurchase\PaymentMethodIntegration\PaymentAdditionalInformationProviderInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Sapient\Worldpay\Logger\WorldpayLogger;

/**
 * Provides Braintree specific payment additional information for instant purchase.
 */
class PaymentAdditionalInformationProvider implements PaymentAdditionalInformationProviderInterface
{

    public function __construct(WorldpayLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalInformation(PaymentTokenInterface $paymentToken): array
    {
        $vaultCardDetails = json_decode($paymentToken->getDetails());
        return [
            'cc_type' => $vaultCardDetails->type,
            'card_brand' => str_replace('-SSL', '', $vaultCardDetails->type),
            'token' => $paymentToken->getGatewayToken(),
        ];
    }
}
