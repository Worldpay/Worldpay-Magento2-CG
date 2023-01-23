<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

use Laminas\Uri\UriFactory;

class PayByLinkService extends \Magento\Framework\DataObject
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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

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
     * @param \Sapient\Worldpay\Helper\SendPayByLinkEmail $payByLinkEmail
     * @param \Sapient\Worldpay\Model\Request $request
     * @param \Magento\Store\Model\StoreManagerInterface $_storeManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
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
        \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentlist,
        \Sapient\Worldpay\Helper\SendPayByLinkEmail $payByLinkEmail,
        \Sapient\Worldpay\Model\Request $request,
        \Magento\Store\Model\StoreManagerInterface $_storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper
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
        $this->payByLinkEmail = $payByLinkEmail;
        $this->_request = $request;
        $this->_storeManager = $_storeManager;
        $this->_messageManager = $messageManager;
        $this->cart = $cart;
        $this->_addressConfig = $addressConfig;
        $this->pricingHelper = $pricingHelper;
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
            $this->_getCountryForQuote($quote),
            $orderCode
        );
        $payment->setIsTransactionPending(1);
        $this->registryhelper->setworldpayRedirectUrl($successUrl);
        $this->checkoutsession->setPayByLinkRedirecturl($successUrl);
        $paybylink_url = $this->_storeManager->getStore()
                              ->getBaseUrl().'worldpay/paybylink/process?orderkey='.$orderCode;
       
        $grandTotal = $this->getFormatGrandTotal($mageOrder);
        $address = $mageOrder->getShippingAddress();
        if (empty($address)) {
            $address = $mageOrder->getBillingAddress();
        }
        $formatedAddress = $this->getFormatAddressByCode($address->getData());
        $this->payByLinkEmail->sendPayBylinkEmail([
            'paybylink_url'=>$paybylink_url,
            'orderId'=> $mageOrder->getIncrementId(),
            'order_total'=> $grandTotal,
            'formated_shipping'=> $formatedAddress,
            'customerName' => $mageOrder->getCustomerFirstName().' '.$mageOrder->getCustomerLastName()]);
    }

    /**
     * Format Shipping Address
     *
     * @param array $address
     * @return array
     */

    public function getFormatAddressByCode($address)
    {
        $renderer = $this->_addressConfig->getFormatByCode('html')->getRenderer();
        return $renderer->renderArray($address);
    }

    /**
     * Format Grand Total
     *
     * @param \Magento\Sales\Model\Order $mageOrder
     * @return string
     */
    public function getFormatGrandTotal($mageOrder)
    {
        if ($mageOrder->getGrandTotal()) {
            $formattedTotal = $this->pricingHelper->currency($mageOrder->getGrandTotal(), true, false);
            return $formattedTotal;
        }
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
    public function authorizeRegenaretPayment(
        $mageOrder,
        $quote,
        $orderCode,
        $orderStoreId,
        $paymentDetails,
        $payment
    ) {
        $this->checkoutsession->setauthenticatedOrderId($mageOrder->getIncrementId());

        try {
            $redirectOrderParams = $this->mappingservice->collectRedirectOrderParameters(
                $orderCode,
                $quote,
                $orderStoreId,
                $paymentDetails
            );
            /* Sending Order Inquiry */
            $this->xmlinquiry = new \Sapient\Worldpay\Model\XmlBuilder\Inquiry();
            $inquirySimpleXml = $this->xmlinquiry->build(
                $redirectOrderParams['merchantCode'],
                $orderCode
            );
            /* Response Order Inquiry */
            $responseInquiry = $this->_sendRequest(
                dom_import_simplexml($inquirySimpleXml)->ownerDocument,
                $this->worldpayhelper->getXmlUsername($redirectOrderParams['paymentType']),
                $this->worldpayhelper->getXmlPassword($redirectOrderParams['paymentType'])
            );

            $paymentService = new \SimpleXmlElement($responseInquiry);
            $lastEvent = $paymentService->xpath('//lastEvent');
            $error = $paymentService->xpath('//error');

            if (!empty($error) && $error[0] == 'Could not find payment for order') {
                $this->wplogger->info('Could not find payment for order ########'.$orderCode);
                $responseXml = $this->paymentservicerequest->redirectOrder($redirectOrderParams);
                $successUrl = $this->_buildRedirectUrl(
                    $responseXml,
                    $redirectOrderParams['paymentType'],
                    $this->_getCountryForQuote($quote),
                    $orderCode
                );
                $payment->setIsTransactionPending(1);
                $this->registryhelper->setworldpayRedirectUrl($successUrl);
                $this->checkoutsession->setPayByLinkRedirecturl($successUrl);
                return $successUrl;
            } elseif ((!empty($error) && $error[0] == 'Order not ready') ||
            ($lastEvent && $lastEvent[0] =='AUTHORISED' || $lastEvent[0] =='CAPTURED'
             || $lastEvent[0] =='SENT_FOR_REFUND' || $lastEvent[0] =='REFUNDED')) {
                $this->cartClear($quote);
                $this->_messageManager->addNotice(
                    $this->paymentservicerequest->getCreditCardSpecificException('CCAM29')
                );
                return ['payment'=>true];
            }
        } catch (Exception $ex) {
            $this->wplogger->error($ex->getMessage());
            $this->_messageManager->addNotice($ex->getMessage());
            return $this->worldpayhelper->getBaseUrl().'checkout/cart/';
        }
    }

    /**
     * Build redirect url
     *
     * @param string $responseXml
     * @param string $paymentType
     * @param string $countryCode
     * @param string $orderCode
     * @return string
     */
    private function _buildRedirectUrl($responseXml, $paymentType, $countryCode, $orderCode)
    {
        $redirectUrl = $this->_getUrlFromResponse($responseXml);
        $parts = UriFactory::factory($redirectUrl);
        $orderParams = $parts->getQueryAsArray();
        $redirectUrl = $this->_addOutcomeRoutes($redirectUrl, $orderCode, $orderParams);
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
     * @param string $orderCode
     * @param string $orderParams
     * @return string
     */
    private function _addOutcomeRoutes($redirectUrl, $orderCode, $orderParams)
    {
        $redirectUrl .= '&successURL=' . $this->_encodeUrl('worldpay/paybylink/success').
        '?orderKey='.$orderParams['OrderKey'];
        $redirectUrl .= '&cancelURL=' . $this->_encodeUrl('worldpay/paybylink/cancel').
        '?orderKey='.$orderParams['OrderKey'];
        $redirectUrl .= '&failureURL=' . $this->_encodeUrl('worldpay/paybylink/failure').
        '?orderKey='.$orderParams['OrderKey'];

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

    /**
     * Get redirect response model
     *
     * @return \Sapient\Worldpay\Model\Response\RedirectResponse
     */
    protected function _getRedirectResponseModel()
    {
        if ($this->_redirectResponseModel === null) {
            $this->_redirectResponseModel = $this->redirectresponse;
        }
        return $this->_redirectResponseModel;
    }

    /**
     * Set redirect pay by link hpp
     *
     * @param string $redirectLink
     * @return \Sapient\Worldpay\Model\Response\RedirectResponse
     */
    private function _setredirectpaybylinkhpp($redirectLink)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($redirectLink);
        return $resultRedirect;
    }
    /**
     * Process the request
     *
     * @param SimpleXmlElement $xml
     * @param string $username
     * @param string $password
     * @return SimpleXmlElement $response
     */
    protected function _sendRequest($xml, $username, $password)
    {
        $response = $this->_request->sendRequest($xml, $username, $password);
        return $response;
    }
    /**
     * Clear Cart
     *
     * @param \Magento\Quote\Model\Quote $quote
     */
    protected function cartClear($quote)
    {
        $cart = $this->cart;
        $quoteItems = $quote->getItemsCollection();
        foreach ($quoteItems as $item) {
            $cart->removeItem($item->getId())->save();
        }
    }
}
