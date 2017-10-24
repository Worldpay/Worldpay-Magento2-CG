<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;
use Exception;

class RedirectService extends \Magento\Framework\DataObject
{
   
    /** @var \Sapient\Worldpay\Model\Response\RedirectResponse */
    protected $_redirectResponseModel;

    /**
     * Constructor
     * @param \Sapient\Worldpay\Model\Mapping\Service $mappingservice
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Model\Response\RedirectResponse $redirectresponse
     * @param \Sapient\Worldpay\Helper\Registry $registryhelper
     * @param \Magento\Checkout\Model\Session $checkoutsession
     * @param \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentlist
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper   
     */
    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingservice,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Response\RedirectResponse $redirectresponse,
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentlist,
        \Sapient\Worldpay\Helper\Data $worldpayhelper
    ) {
       $this->mappingservice = $mappingservice;
       $this->paymentservicerequest = $paymentservicerequest;
       $this->wplogger = $wplogger;
       $this->paymentservice = $paymentservice;
       $this->redirectresponse = $redirectresponse;
       $this->registryhelper = $registryhelper;
       $this->checkoutsession = $checkoutsession;
       $this->paymentlist = $paymentlist;
       $this->worldpayhelper = $worldpayhelper;
    }
    /**
     * handles provides authorization data for redirect
     * It initiates a  XML request to WorldPay and registers worldpayRedirectUrl 
     */
    public function authorizePayment(
        $mageOrder,
        $quote,
        $orderCode,
        $orderStoreId,
        $paymentDetails,
        $payment
    ) {      
        $this->checkoutsession->setauthenticatedOrderId($mageOrder->getIncrementId());
        if($paymentDetails['additional_data']['cc_type'] == 'KlARNA-SSL'){
             $redirectOrderParams = $this->mappingservice->collectKlarnaOrderParameters(
                $orderCode,
                $quote,
                $orderStoreId,
                $paymentDetails
            );

            $response = $this->paymentservicerequest->redirectKlarnaOrder($redirectOrderParams);
       }else{
            $redirectOrderParams = $this->mappingservice->collectRedirectOrderParameters(
                $orderCode,
                $quote,
                $orderStoreId,
                $paymentDetails
            );

            $response = $this->paymentservicerequest->redirectOrder($redirectOrderParams);
        }
        $successUrl = $this->_buildRedirectUrl(
            $this->_getRedirectResponseModel()->getRedirectLocation($response),
            $redirectOrderParams['paymentType'],
            $this->_getCountryForQuote($quote),
            $this->_getLanguageForLocale()
        );

        $payment->setIsTransactionPending(1);
        
        $this->registryhelper->setworldpayRedirectUrl($successUrl);
        $this->checkoutsession->setWpRedirecturl($successUrl);

    }

    private function _buildRedirectUrl($redirect, $paymentType, $countryCode, $languageCode)
    {
        $redirect .= '&preferredPaymentMethod=' . $paymentType;
        $redirect .= '&country=' . $countryCode;
        $redirect .= '&language=' . $languageCode;

        return $redirect;
    }

    /**
     * Get billing Country
     * @return string
     */
    private function _getCountryForQuote($quote)
    {
        $address = $quote->getBillingAddress();
        if ($address->getId()) {
            return $address->getCountry();
        }        
        return $this->worldpayhelper->getDefaultCountry();
        
    }

    /**
     * Get local language code
     * @return string
     */
    protected function _getLanguageForLocale()
    {        
        $locale = $this->worldpayhelper->getLocaleDefault();
        if (substr($locale, 3, 2) == 'NO') {
            return 'no';
        }         
        return substr($locale, 0, 2);
    }
    
    /**
     * @return \Sapient\Worldpay\Model\Response\RedirectResponse
     */
    protected function _getRedirectResponseModel()
    {
        if ($this->_redirectResponseModel === null) {
            $this->_redirectResponseModel = $this->redirectresponse;
        }
        return $this->_redirectResponseModel;
    }

    
}
