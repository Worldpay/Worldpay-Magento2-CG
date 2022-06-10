<?php
/**
 * MotoRedirectService @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class MotoRedirectService extends \Magento\Framework\DataObject
{
    /** @var  session */
    protected $_session;

    /** @var  redirectResponseModel */
    protected $_redirectResponseModel;

    /**
     * Constructor
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
     * AuthorizePayment
     *
     * @param int|string $mageOrder
     * @param int|string $quote
     * @param int|string $orderCode
     * @param int|string $orderStoreId
     * @param int|string $paymentDetails
     * @param int|string $payment
     * @return string
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
     * BuildRedirectUrl
     *
     * @param int|string $responseXml
     * @param int|string $paymentType
     * @param int|string $countryCode
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
     * GetUrlFromResponse
     *
     * @param int|string $responseXml
     * @return string
     */
    private function _getUrlFromResponse($responseXml)
    {
        $responseXmlElement = new \SimpleXmlElement($responseXml);
        $url = $responseXmlElement->xpath('reply/orderStatus/reference');

        return trim($url[0]);
    }

    /**
     * AddOutcomeRoutes
     *
     * @param int|string $redirectUrl
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
     * AddExtraUrlParameters
     *
     * @param int|string $redirectUrl
     * @param int|string $paymentType
     * @param int|string $countryCode
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
     * EncodeUrl
     *
     * @param int|string $path
     * @param int|string $additionalParams
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
     * GetCountryForQuote
     *
     * @param int|string $quote
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
     * GetLanguageForLocale
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
