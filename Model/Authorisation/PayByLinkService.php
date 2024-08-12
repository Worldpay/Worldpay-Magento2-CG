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
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutsession;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
         
    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayhelper;

    /**
     * @var \Sapient\Worldpay\Helper\SendPayByLinkEmail
     */
    protected $payByLinkEmail;

    /**
     * @var \Sapient\Worldpay\Model\Request
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $_addressConfig;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * @var \Sapient\Worldpay\Helper\Multishipping
     */
    protected $multishippingHelper;

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
     * @param \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
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
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
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
        $this->multishippingHelper = $multishippingHelper;
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
        /** Start Multishipping Code */
        $isMultishipping = false;
        if ($this->multishippingHelper->isMultiShipping($quote)) {
            $isMultishipping = true;
            $sessionOrderCode = $this->multishippingHelper->getOrderCodeFromSession();
            if (!empty($sessionOrderCode)) {
                $orgWorldpayPayment = $this->multishippingHelper->getOrgWorldpayId($sessionOrderCode);
                $orgOrderId = $orgWorldpayPayment['order_id'];
                $isOrg = false;
                $this->multishippingHelper->_createWorldpayMultishipping($mageOrder, $sessionOrderCode, $isOrg);
                $this->multishippingHelper->_copyWorldPayPayment($orgOrderId, $orderCode);
                $payment->setIsTransactionPending(1);
                return;
            } else {
                $isOrg = true;
                $this->multishippingHelper->_createWorldpayMultishipping($mageOrder, $orderCode, $isOrg);
            }
        }
        /** End Multishipping Code */
        $paymentDetails['is_paybylink_order'] = 1;
        $redirectOrderParams = $this->mappingservice->collectRedirectOrderParameters(
            $orderCode,
            $quote,
            $orderStoreId,
            $paymentDetails
        );
        $redirectOrderParams['is_paybylink_order'] = 1;
        if ($isMultishipping) {
            $redirectOrderParams['isMultishippingOrder'] = 1;
        }
        $responseXml = $this->paymentservicerequest->redirectOrder($redirectOrderParams);

        $successUrl = $this->_buildRedirectUrl(
            $responseXml,
            $redirectOrderParams['paymentType'],
            $this->_getCountryForQuote($quote),
            $orderCode
        );
        $this->wplogger->info('Multishipping Payment URL =>');
        $this->wplogger->info($successUrl);
        $payment->setIsTransactionPending(1);
        $this->registryhelper->setworldpayRedirectUrl($successUrl);
        $this->checkoutsession->setPayByLinkRedirecturl($successUrl);
        $paybylink_url = $this->_storeManager->getStore()
        ->getBaseUrl().'worldpay/paybylink/process?orderkey='.$orderCode;
        $this->wplogger->info('Pay By link URl '.$paybylink_url);
        $grandTotal = $this->getFormatGrandTotal($mageOrder);
        $address = $mageOrder->getShippingAddress();
        if (empty($address)) {
            $address = $mageOrder->getBillingAddress();
        }
        $quote_id = $quote->getId();
        $formatedAddress = $this->getFormatAddressByCode($address->getData());
        $multishippingOrderIds = $this->multishippingHelper->getMultishippingOrdersIds($quote_id);
        $this->payByLinkEmail->sendPayBylinkEmail([
            'paybylink_url'=>$paybylink_url,
            'orderId'=> $mageOrder->getIncrementId(),
            'order_total'=> $grandTotal,
            'formated_shipping'=> $formatedAddress,
            'customerName' => $mageOrder->getCustomerFirstName().' '.$mageOrder->getCustomerLastName(),
            'is_multishipping' => $isMultishipping,
            'is_resend' => false,
            'customerEmail' => false,
            'quote_id' => $quote_id,
            'pblexpirytime' => $this->getPayByLinkExpiryTime(),
            'multishipping_order_ids'=> $multishippingOrderIds
        ]);
    }

    /**
     * Get PBL expiry time     *
     */
    public function getPayByLinkExpiryTime()
    {
        $currentDate = date('Y-m-d H:i:s');
        $pblExpiryConfiguration = $this->worldpayhelper->getPayByLinkExpiryTime();
        $pblExpTime = $this->worldpayhelper->findPblOrderExpiryTime($currentDate, $pblExpiryConfiguration);
        $interval = date("Y-m-d H:i:s", $pblExpTime);
        return $interval;
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
            $paymentDetails['is_paybylink_order'] = 1;
            $redirectOrderParams = $this->mappingservice->collectRedirectOrderParameters(
                $orderCode,
                $quote,
                $orderStoreId,
                $paymentDetails
            );
            /* Sending Order Inquiry */
            $this->xmlinquiry = new \Sapient\Worldpay\Model\XmlBuilder\Inquiry();

            $merchantCode = $redirectOrderParams['merchantCode'];
            $merchantUsername = $this->worldpayhelper->getXmlUsername($redirectOrderParams['paymentType']);
            $merchantPassword = $this->worldpayhelper->getXmlPassword($redirectOrderParams['paymentType']);
            $installationId = $this->worldpayhelper->getInstallationId($redirectOrderParams['paymentType']);
            // pbl merchant configurations
            $pblMerchantUn = $this->worldpayhelper->getPayByLinkMerchantUsername($orderStoreId);
            $pblMerchantPw = $this->worldpayhelper->getPayByLinkMerchantPassword($orderStoreId);
            $pblMerchantCode = $this->worldpayhelper->getPayByLinkMerchantCode($orderStoreId);
            $pblInstallationId = $this->worldpayhelper->getPayByLinkInstallationId($orderStoreId);

            $merchantUsername = !empty($pblMerchantUn) ? $pblMerchantUn : $merchantUsername ;
            $merchantPassword = !empty($pblMerchantPw) ? $pblMerchantPw : $merchantPassword ;
            $merchantCode = !empty($pblMerchantCode) ? $pblMerchantCode : $merchantCode ;
            $installationId = !empty($pblInstallationId) ? $pblInstallationId : $installationId ;
           
            $inquirySimpleXml = $this->xmlinquiry->build(
                $merchantCode,
                $orderCode
            );
            /* Response Order Inquiry */
            $responseInquiry = $this->_sendRequest(
                dom_import_simplexml($inquirySimpleXml)->ownerDocument,
                $merchantUsername,
                $merchantPassword
            );

            $paymentService = new \SimpleXmlElement($responseInquiry);
            $lastEvent = $paymentService->xpath('//lastEvent');
            $error = $paymentService->xpath('//error');

            if (!empty($error) && $error[0] == 'Could not find payment for order') {
                $this->wplogger->info('Could not find payment for order ########'.$orderCode);
                if ($quote->getIsMultiShipping()) {
                    $multiDesc = $this->worldpayhelper->getMultiShippingOrderDescription();
                    $redirectOrderParams['orderDescription'] = $multiDesc;
                }
                $redirectOrderParams['is_paybylink_order'] = 1;
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
