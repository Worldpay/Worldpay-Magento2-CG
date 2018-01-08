<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;
use Exception;

class PaymentOptionsService extends \Magento\Framework\DataObject
{
   
    /**
     * Constructor
     * @param \Sapient\Worldpay\Model\Mapping\Service $mappingservice
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper   
     */
    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingservice,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $worldpayhelper
    ) {
       $this->mappingservice = $mappingservice;
       $this->paymentservicerequest = $paymentservicerequest;
       $this->wplogger = $wplogger;
       $this->worldpayhelper = $worldpayhelper;
    }
    /**
     * handles provides authorization data for redirect
     * It initiates a  XML request to WorldPay and registers worldpayRedirectUrl 
     */
    public function collectPaymentOptions(
        $countryId,
        $paymenttype
    ) {      
        $paymentOptionParams = $this->mappingservice->collectPaymentOptionsParameters(
            $countryId,
            $paymenttype
        );

        $response = $this->paymentservicerequest->paymentOptionsByCountry($paymentOptionParams);
        $responsexml = simplexml_load_string($response);

        $paymentoptions =  $this->getPaymentOptions($responsexml);
        return $paymentoptions;
    }

    private function getPaymentOptions($xml)
    {
         if (isset($xml->reply->paymentOption)) {
            return (array) $xml->reply->paymentOption;
        }
        return null;

    }

    
}
