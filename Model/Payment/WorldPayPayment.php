<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

class WorldPayPayment 
{
    public function __construct(			 
         \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment                
    ) {    	
        $this->worldpaypayment = $worldpaypayment;         
    }

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
		
		$wpp->save();
    }
}
