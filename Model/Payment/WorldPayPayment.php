<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

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
		
		$wpp->save();
    }
}
