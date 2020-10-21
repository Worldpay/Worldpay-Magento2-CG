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

    public function __construct(
        WorldpayLogger $logger,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->logger = $logger;
        $this->session = $session;
         $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalInformation(PaymentTokenInterface $paymentToken): array
    {
        $vaultCardDetails = json_decode($paymentToken->getDetails());
        $dfId = $this->checkoutSession->getDfReferenceId();
        if ($dfId === null) {
            return [
            'cc_type' => $vaultCardDetails->type,
            'card_brand' => str_replace('-SSL', '', $vaultCardDetails->type),
            'token' => $paymentToken->getGatewayToken(),
            
            ];
        } else {
            $this->checkoutSession->unsDfReferenceId();
            return [
            'cc_type' => $vaultCardDetails->type,
            'card_brand' => str_replace('-SSL', '', $vaultCardDetails->type),
            'token' => $paymentToken->getGatewayToken(),
            'dfReferenceId' => $dfId,
            
            ];
        }
    }
}
