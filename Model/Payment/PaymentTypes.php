<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

use Sapient\Worldpay\Api\PaymentTypeInterface; 

class PaymentTypes implements PaymentTypeInterface
{

	 public function __construct(
       \Sapient\Worldpay\Model\Authorisation\PaymentOptionsService $paymentoptionsservice
    ) {
        $this->paymentoptionsservice = $paymentoptionsservice;        
    }
   
    public function getPaymentType($countryId) 
    {
        $responsearray = array();
        $result = $this->paymentoptionsservice->collectPaymentOptions($countryId,$paymenttype = null);
        if(!empty($result)){
        	$responsearray = $result;
        }
        return json_encode($responsearray);
    }
    
}