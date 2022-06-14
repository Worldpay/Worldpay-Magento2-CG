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
     * Handles provides authorization data for redirect
     *
     * It initiates a  XML request to WorldPay and registers worldpayRedirectUrl
     *
     * @param string $countryId
     * @param string $paymenttype
     * @return array
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
        
        if ($this->worldpayhelper->isGlobalApmEnable()) {
            $additionalMerchanPaymentoptions =  $this->getAdditionalMerchantPaymentOptions($countryId, $paymentoptions);
            $paymentoptions = array_merge($paymentoptions, $additionalMerchanPaymentoptions);
        }
        return $paymentoptions;
    }

    /**
     * Get payment options
     *
     * @param SimpleXMLElement $xml
     * @return array|null
     */
    private function getPaymentOptions($xml)
    {
        if (isset($xml->reply->paymentOption)) {
            return (array) $xml->reply->paymentOption;
        }
        return null;
    }

    /**
     *  Get Additional merchant profile from merchant override configuration
     *  and merge all the unique values(if not available in global merchant profile)
     *  to the global payment method array
     *
     * @param string $countryId
     * @param array $paymentoptions
     * @return array
     */
    public function getAdditionalMerchantPaymentOptions($countryId, $paymentoptions)
    {
        $additionalMerchantConfigurations = $this->worldpayhelper->getAdditionalMerchantProfiles();
        $additonalPaymentMethods = [];
        if (!empty($additionalMerchantConfigurations)) {
            
            foreach ($additionalMerchantConfigurations as $paymentType => $merchant) {
                $paymentOptionParams = [
                        'merchantCode' => $merchant['merchant_code'],
                        'countryCode' => $countryId,
                        'paymentType'=> $paymentType
                ];
               
                $response = $this->paymentservicerequest->paymentOptionsByCountry($paymentOptionParams);
                $responsexml = simplexml_load_string($response);
                $paymentMethods =  $this->getPaymentOptions($responsexml);

                if (!empty($paymentMethods)) {
                    foreach ($paymentMethods as $paymentMethod) {
                        if (!in_array($paymentMethod, $paymentoptions)) {
                            $additonalPaymentMethods[] = $paymentMethod;
                        }
                    }
                }

            }
        }

        return $additonalPaymentMethods;
    }
}
