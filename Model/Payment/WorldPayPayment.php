<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

use Magento\Payment\Model\InfoInterface;

/**
 * Updating Risk gardian
 */
class WorldPayPayment
{

    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment
     */
    public function __construct(
        \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment
    ) {
        $this->worldpaypayment = $worldpaypayment;
    }

    /**
     * Updating Risk gardian
     *
     * @param \Sapient\Worldpay\Model\Payment\State $paymentState
     */
    public function updateWorldpayPayment(\Sapient\Worldpay\Model\Payment\State $paymentState)
    {
         $wpp = $this->worldpaypayment->create();

        $wpp = $wpp->loadByWorldpayOrderId($paymentState->getOrderCode());

        $wpp->setData('payment_status', $paymentState->getPaymentStatus());
        $wpp->setData('card_number', $paymentState->getCardNumber());
        $wpp->setData('avs_result', $paymentState->getAvsResultCode());
        $wpp->setData('cvc_result', $paymentState->getCvcResultCode());
        $wpp->setData('risk_score', $paymentState->getRiskScore());
        $wpp->setData('risk_provider', $paymentState->getAdvancedRiskProvider());
        $wpp->setData('risk_provider_score', $paymentState->getAdvancedRiskProviderScore());
        $wpp->setData('risk_provider_id', $paymentState->getAdvancedRiskProviderId());
        $wpp->setData('risk_provider_threshold', $paymentState->getAdvancedRiskProviderThreshold());
        $wpp->setData('risk_provider_final', $paymentState->getAdvancedRiskProviderFinalScore());
        $wpp->setData('refusal_code', $paymentState->getPaymentRefusalCode());
        $wpp->setData('refusal_description', $paymentState->getPaymentRefusalDescription());
        $wpp->setData('aav_address_result_code', $paymentState->getAAVAddressResultCode());
        $wpp->setData('avv_postcode_result_code', $paymentState->getAAVPostcodeResultCode());
        $wpp->setData('aav_cardholder_name_result_code', $paymentState->getAAVCardholderNameResultCode());
        $wpp->setData('aav_telephone_result_code', $paymentState->getAAVTelephoneResultCode());
        $wpp->setData('aav_email_result_code', $paymentState->getAAVEmailResultCode());
        $wpp->setData('primerouting_networkused', $paymentState->getNetworkUsed());
        $wpp->setData('source_type', $paymentState->getSourceType());
        $wpp->setData('available_balance', $paymentState->getAvailableBalance());
        $wpp->setData('prepaid_card_type', $paymentState->getPrepaidCardType());
        $wpp->setData('reloadable', $paymentState->getReloadable());
        $wpp->setData('card_product_type', $paymentState->getCardProductType());
        $wpp->setData('affluence', $paymentState->getAffluence());
        $wpp->setData('account_range_id', $paymentState->getAccountRangeId());
        $wpp->setData('issuer_country', $paymentState->getIssuerCountry());
        $wpp->setData('virtual_account_number', $paymentState->getVirtualAccountNumber());
        $wpp->setData('fraudsight_message', $paymentState->getFraudsightMessage());
        $wpp->setData('fraudsight_score', $paymentState->getFraudsightScore());
        $wpp->setData('fraudsight_reasoncode', $paymentState->getFraudsightReasonCode());
        $wpp->save();
    }
    
    /**
     * Update prime routing data
     *
     * @param InfoInterface $payment
     * @param \Sapient\Worldpay\Model\Payment\State $paymentState
     */
    public function updatePrimeroutingData(InfoInterface $payment, \Sapient\Worldpay\Model\Payment\State $paymentState)
    {
        $wpp = $this->worldpaypayment->create();
        $wpp = $wpp->loadByWorldpayOrderId($paymentState->getOrderCode());
        $isPrimeRoutingRequest = $this->getPrimeRoutingEnabled($payment);
        $wpp->setData('is_primerouting_enabled', $isPrimeRoutingRequest);
        $wpp->save();
    }
    
    /**
     * Check if request fell under prime routing
     *
     * @param InfoInterface $paymentObject
     * @return bool
     */
    private function getPrimeRoutingEnabled(InfoInterface $paymentObject)
    {
        $paymentAditionalInformation = $paymentObject->getAdditionalInformation();
        if (!empty($paymentAditionalInformation)
                && array_key_exists('worldpay_primerouting_enabled', $paymentAditionalInformation)) {
            $wpPrimeRoutingEnabled=$paymentAditionalInformation['worldpay_primerouting_enabled'];
            return $wpPrimeRoutingEnabled;
        }
    }
}
