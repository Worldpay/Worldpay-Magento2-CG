<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class MotoRedirectService extends \Magento\Framework\DataObject
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_session;
    /**
     * @var \Sapient\Worldpay\Model\Response\RedirectResponse
     */
    protected $_redirectResponseModel;

     /**
      * @var \Sapient\Worldpay\Model\Request\PaymentServiceRequest
      */
    protected $mappingservice;
    /**
     * @var \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory
     */
    protected $paymentservicerequest;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var \Sapient\Worldpay\Helper\Registry
     */
    protected $registryhelper;
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutsession;
    
    /**
     * @var \Sapient\Worldpay\Model\Payment\Service
     */
    protected $paymentservice;
    
    /**
     * @var \Sapient\Worldpay\Model\Response\RedirectResponse
     */
    protected $redirectresponse;
    
    /**
     * @var \Sapient\Worldpay\Model\Utilities\PaymentMethods
     */
    protected $paymentlist;
       
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
         
    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayhelper;

    /**
     * MotoRedirectService constructor
     *
     * @param \Sapient\Worldpay\Model\Mapping\Service $mappingservice
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Model\Response\RedirectResponse $redirectresponse
     * @param \Sapient\Worldpay\Helper\Registry $registryhelper
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Magento\Checkout\Model\Session $checkoutsession
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentlist
     */
    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingservice,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Response\RedirectResponse $redirectresponse,
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentlist
    ) {
        $this->mappingservice = $mappingservice;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->wplogger = $wplogger;
        $this->paymentservice = $paymentservice;
        $this->redirectresponse = $redirectresponse;
        $this->registryhelper = $registryhelper;
        $this->checkoutsession = $checkoutsession;
        $this->paymentlist = $paymentlist;
        $this->_urlBuilder = $urlBuilder;
        $this->worldpayhelper = $worldpayhelper;
    }

    /**
     * Handles provides authorization data for Moto redirect service
     *
     * It initiates a  XML request to WorldPay and registers worldpayRedirectUrl
     *
     * @param MageOrder $mageOrder
     * @param Quote $quote
     * @param string $orderCode
     * @param string $orderStoreId
     * @param array $paymentDetails
     * @param Payment $payment
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

        $redirectOrderParams = $this->mappingservice->collectRedirectOrderParameters(
            $orderCode,
            $quote,
            $orderStoreId,
            $paymentDetails
        );

        $responseXml = $this->paymentservicerequest->redirectOrder($redirectOrderParams);

        $successUrl = $this->_buildRedirectUrl(
            $responseXml,
            $redirectOrderParams['paymentType'],
            $this->_getCountryForQuote($quote)
        );

        $payment->setIsTransactionPending(1);
        $this->checkoutsession->setAdminWpRedirecturl($successUrl);
    }

    /**
     * Build redirect url
     *
     * @param string $responseXml
     * @param string $paymentType
     * @param string $countryCode
     * @return string
     */
    private function _buildRedirectUrl($responseXml, $paymentType, $countryCode)
    {
        $redirectUrl = $this->_getUrlFromResponse($responseXml);
        $redirectUrl = $this->_addOutcomeRoutes($redirectUrl);
        $redirectUrl = $this->_addExtraUrlParameters($redirectUrl, $paymentType, $countryCode);

        return $redirectUrl;
    }

    /**
     * Get the url from response
     *
     * @param SimpleXMLElement $responseXml
     * @return string
     */
    private function _getUrlFromResponse($responseXml)
    {
        $responseXmlElement = new \SimpleXmlElement($responseXml);
        $url = $responseXmlElement->xpath('reply/orderStatus/reference');

        return trim($url[0]);
    }

    /**
     * Add outcome routes
     *
     * @param string $redirectUrl
     * @return string
     */
    private function _addOutcomeRoutes($redirectUrl)
    {
        $redirectUrl .= '&successURL=' . $this->_encodeUrl('worldpay/motoRedirectResult/success');
        $redirectUrl .= '&cancelURL=' . $this->_encodeUrl('worldpay/motoRedirectResult/cancel');
        $redirectUrl .= '&failureURL=' . $this->_encodeUrl('worldpay/motoRedirectResult/failure');

        return $redirectUrl;
    }

    /**
     * Add extra url parameters
     *
     * @param string $redirectUrl
     * @param string $paymentType
     * @param string $countryCode
     * @return string
     */
    private function _addExtraUrlParameters($redirectUrl, $paymentType, $countryCode)
    {
        $redirectUrl .= '&preferredPaymentMethod=' . $paymentType;
        $redirectUrl .= '&country=' . $countryCode;
        $redirectUrl .= '&language=' . $this->_getLanguageForLocale();

        return $redirectUrl;
    }

    /**
     * Encode url
     *
     * @param string $path
     * @param array $additionalParams
     * @return string
     */
    private function _encodeUrl($path, $additionalParams = [])
    {
        $urlParams = ['_type' => 'direct_link', '_secure' => true];
        $urlParams = array_merge($urlParams, $additionalParams);
        $rawurlencode = rawurlencode(
            $this->_urlBuilder->getUrl($path, $urlParams)
        );

        return $rawurlencode;
    }

    /**
     * Get billing Country
     *
     * @param Quote $quote
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
     *
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
}
