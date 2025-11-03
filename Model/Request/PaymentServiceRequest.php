<?php
namespace Sapient\Worldpay\Model\Request;

/**
 * @copyright 2017 Sapient
 */
use Exception;
use Sapient\Worldpay\Helper\ProductOnDemand;
use Sapient\Worldpay\Model\SavedToken;
use Sapient\Worldpay\Model\XmlBuilder\PaypalOrder;
use Sapient\Worldpay\Model\XmlBuilder\RedirectOrder;

/**
 * Prepare the request and process them
 */
class PaymentServiceRequest extends \Magento\Framework\DataObject
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    public $_urlBuilder;
    /**
     * @var \Sapient\Worldpay\Model\Request $request
     */
    protected $_request;
    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $_wplogger;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayhelper;

    /**
     * @var \Sapient\Worldpay\Helper\GeneralException
     */
    protected $exceptionHelper;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var \Sapient\Worldpay\Helper\SendErrorReport
     */
    protected $emailErrorReportHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    public const SEND_ADDITIONAL_HEADER = true;
    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Request $request
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Sapient\Worldpay\Helper\GeneralException $exceptionHelper
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Sapient\Worldpay\Helper\SendErrorReport $emailErrorReportHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Request $request,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Sapient\Worldpay\Helper\GeneralException $exceptionHelper,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Sapient\Worldpay\Helper\SendErrorReport $emailErrorReportHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ProductOnDemand $productOnDemand,
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->_wplogger = $wplogger;
        $this->_request = $request;
        $this->worldpayhelper = $worldpayhelper;
        $this->exceptionHelper = $exceptionHelper;
        $this->_invoiceService = $invoiceService;
        $this->emailErrorReportHelper = $emailErrorReportHelper;
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->productOnDemand = $productOnDemand;
    }

    /**
     * Send 3d direct order XML to Worldpay server
     *
     * @param array $directOrderParams
     * @return mixed
     */
    public function order3DSecure($directOrderParams)
    {
        $loggerMsg = '########## Submitting direct 3DSecure order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $directOrderParams['orderCode'] . ' ##########');
        $merchantCode = $directOrderParams['merchantCode'];
        if (!empty($directOrderParams['isMultishippingOrder'])) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }
        $directOrderParams['paymentDetails']['isEnabledEFTPOS'] = false;
        if ($this->worldpayhelper->isEnabledEFTPOS()) {
            $eftPosMerchantCode = $this->worldpayhelper->getEFTPOSMerchantCode();
            $merchantCode = !empty($eftPosMerchantCode) ? $eftPosMerchantCode : $merchantCode ;
            $directOrderParams['paymentDetails']['isEnabledEFTPOS'] = true;
        }

        if (isset($directOrderParams['tokenRequestConfig'])) {
            $requestConfiguration = [
                'threeDSecureConfig' => $directOrderParams['threeDSecureConfig'],
                'tokenRequestConfig' => $directOrderParams['tokenRequestConfig']
            ];

            $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectOrder(
                $this->customerSession,
                $this->worldpayhelper,
                $requestConfiguration
            );
            $paymentType = isset($directOrderParams['paymentDetails']['brand']) ?
                $directOrderParams['paymentDetails']['brand']: $directOrderParams['paymentDetails']['paymentType'];
            $orderSimpleXml = $this->xmldirectorder->build3DSecure(
                $merchantCode,
                $directOrderParams['orderCode'],
                $directOrderParams['paymentDetails'],
                $directOrderParams['paResponse'],
                $directOrderParams['echoData']
            );
        } else {
            $requestConfiguration = [
                'threeDSecureConfig' => $directOrderParams['threeDSecureConfig']
            ];
            $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\WalletOrder($requestConfiguration);
            $paymentType = $directOrderParams['paymentType'];
            $orderSimpleXml = $this->xmldirectorder->build3DSecure(
                $merchantCode,
                $directOrderParams['orderCode'],
                $directOrderParams['paymentDetails'],
                $directOrderParams['paResponse'],
                $directOrderParams['echoData']
            );
        }

        $xmlUsername = $this->worldpayhelper->getXmlUsername($paymentType);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($paymentType);

        if (!empty($directOrderParams['isMultishippingOrder'])) {
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
        }

        if ($this->worldpayhelper->isEnabledEFTPOS()) {
            $eftMerchantUn = $this->worldpayhelper->getEFTPosXmlUsername();
            $eftMerchantPw = $this->worldpayhelper->getEFTPOSXmlPassword();

            $xmlUsername = !empty($msMerchantUn) ? $eftMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $eftMerchantPw : $xmlPassword ;
        }

        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Send direct order XML to Worldpay server
     *
     * @param array $directOrderParams
     * @return mixed
     */
    public function order($directOrderParams)
    {
        $loggerMsg = '########## Submitting direct order request. OrderCode: ';
        $requestConfiguration = [
            'threeDSecureConfig' => $directOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $directOrderParams['tokenRequestConfig']
        ];
        //$directOrderParams['paymentDetails']['cardType'] ='';
        //Level 23 data validation
        if ($this->worldpayhelper->isLevel23Enabled() && isset($directOrderParams['paymentDetails']['cardType'])
            && ($directOrderParams['paymentDetails']['cardType'] === 'ECMC-SSL'
                || $directOrderParams['paymentDetails']['cardType'] === 'VISA-SSL')
            && ($directOrderParams['billingAddress']['countryCode'] === 'US'
                || $directOrderParams['billingAddress']['countryCode'] === 'CA')) {
            $directOrderParams['paymentDetails']['isLevel23Enabled'] = true;
            $directOrderParams['paymentDetails']['cardAcceptorTaxId'] = $this->worldpayhelper->getCardAcceptorTaxId();
            $directOrderParams['paymentDetails']['dutyAmount'] = $this->worldpayhelper->getDutyAmount();
            $directOrderParams['paymentDetails']['countryCode'] = $directOrderParams['billingAddress']['countryCode'];
        }

        $xmlUsername = $this->worldpayhelper->getXmlUsername($directOrderParams['paymentDetails']['paymentType']);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($directOrderParams['paymentDetails']['paymentType']);
        $merchantCode = $directOrderParams['merchantCode'];

        if ($directOrderParams['method'] == 'worldpay_moto'
            && $directOrderParams['paymentDetails']['dynamicInteractionType'] == 'MOTO') {

            #### Get General Configuration by store id ######
            $motoStoreId = $this->worldpayhelper->getStoreIdFromQuoteForAdminOrder();
            $wpMUsername = $this->worldpayhelper->getMerchantUsernameByStoreId($motoStoreId);
            $wpMPassword = $this->worldpayhelper->getMerchantPasswordByStoreId($motoStoreId);
            $wpMcode = $this->worldpayhelper->getMerchantCodeByStoreId($motoStoreId);

            $xmlUsername = !empty($wpMUsername) ? $wpMUsername : $xmlUsername;
            $xmlPassword = !empty($wpMPassword) ? $wpMPassword : $xmlPassword;
            $merchantCode = !empty($wpMcode) ? $wpMcode : $merchantCode;

            ####   get moto order details #####
            $wpMotoUsername = $this->worldpayhelper->getMotoUsername($motoStoreId);
            $wpMotoPassword = $this->worldpayhelper->getMotoPassword($motoStoreId);
            $wpMotoCode = $this->worldpayhelper->getMotoMerchantCode($motoStoreId);

            $xmlUsername = !empty($wpMotoUsername) ? $wpMotoUsername : $xmlUsername;
            $xmlPassword = !empty($wpMotoPassword) ? $wpMotoPassword : $xmlPassword;
            $merchantCode = !empty($wpMotoCode) ? $wpMotoCode : $merchantCode;
        }
        if (!empty($directOrderParams['isMultishippingOrder'])) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }
        $directOrderParams['paymentDetails']['isEnabledEFTPOS'] = false;
        if ($this->worldpayhelper->isEnabledEFTPOS()) {
            $eftposMerchantCode = $this->worldpayhelper->getEFTPOSMerchantCode();
            $eftposMerchantUn = $this->worldpayhelper->getEFTPosXmlUsername();
            $eftposMerchantPw = $this->worldpayhelper->getEFTPOSXmlPassword();

            $xmlUsername = !empty($eftposMerchantUn) ? $eftposMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($eftposMerchantPw) ? $eftposMerchantPw : $xmlPassword ;
            $merchantCode = !empty($eftposMerchantCode) ? $eftposMerchantCode : $merchantCode ;
            $directOrderParams['paymentDetails']['isEnabledEFTPOS'] = true;
        }
        if ($this->worldpayhelper->isEnabledEFTPOS()
            && !empty($this->worldpayhelper->getEFTPOSRoutingMid())) {
            $directOrderParams['paymentDetails']['routingMID'] =
                $this->worldpayhelper->getEFTPOSRoutingMid();
        }
        $directOrderParams['paymentDetails']['sendShopperIpAddress'] = $this->isSendShopperIpAddress();
        ##### Added orderContent node for plugin tracker ######
        $directOrderParams['orderContent'] = $this->collectPluginTrackerDetails(
            $directOrderParams['paymentDetails']['paymentType']
        );
        $captureDelay = $this->worldpayhelper->getCaptureDelayValues();
        $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectOrder(
            $this->customerSession,
            $this->worldpayhelper,
            $requestConfiguration
        );
        if ($this->worldpayhelper->getsubscriptionStatus()) {
            $directOrderParams['paymentDetails']['subscription_order'] = 1;
        }

        if ($this->productOnDemand->isProductOnDemandQuote()) {
            $directOrderParams['paymentDetails']['zero_auth_order'] = 1;
        }

        if (empty($directOrderParams['thirdPartyData']) && empty($directOrderParams['shippingfee'])) {
            $directOrderParams['thirdPartyData']='';
            $directOrderParams['shippingfee']='';
        }
        if (empty($directOrderParams['shippingAddress'])) {
            $directOrderParams['shippingAddress']='';
        }
        if (empty($directOrderParams['saveCardEnabled'])) {
            $directOrderParams['saveCardEnabled']='';
        }
        if (empty($directOrderParams['tokenizationEnabled'])) {
            $directOrderParams['tokenizationEnabled']='';
        }
        if (empty($directOrderParams['storedCredentialsEnabled'])) {
            $directOrderParams['storedCredentialsEnabled']='';
        }
        if (empty($directOrderParams['exemptionEngine'])) {
            $directOrderParams['exemptionEngine']='';
        }
        if (empty($directOrderParams['cusDetails'])) {
            $directOrderParams['cusDetails']='';
        }
        if (empty($directOrderParams['primeRoutingData'])) {
            $directOrderParams['primeRoutingData'] = '';
        }
        if (empty($directOrderParams['orderLineItems'])) {
            $directOrderParams['orderLineItems'] = '';
        }

        $orderSimpleXml = $this->xmldirectorder->build(
            $merchantCode,
            $directOrderParams['orderCode'],
            $directOrderParams['orderDescription'],
            $directOrderParams['currencyCode'],
            $directOrderParams['amount'],
            $directOrderParams['orderContent'],
            $directOrderParams['paymentDetails'],
            $directOrderParams['cardAddress'],
            $directOrderParams['shopperEmail'],
            $directOrderParams['acceptHeader'],
            $directOrderParams['userAgentHeader'],
            $directOrderParams['shippingAddress'],
            $directOrderParams['billingAddress'],
            $directOrderParams['shopperId'],
            $directOrderParams['saveCardEnabled'],
            $directOrderParams['tokenizationEnabled'],
            $directOrderParams['storedCredentialsEnabled'],
            $directOrderParams['cusDetails'],
            $directOrderParams['exemptionEngine'],
            $directOrderParams['thirdPartyData'],
            $directOrderParams['shippingfee'],
            $directOrderParams['exponent'],
            $directOrderParams['primeRoutingData'],
            $directOrderParams['orderLineItems'],
            $captureDelay,
            $directOrderParams['browserFields'],
            $directOrderParams['telephoneNumber']
        );
        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword,
        );
    }

    /**
     * Send ACH order XML to Worldpay server
     *
     * @param array $directOrderParams
     * @return mixed
     */
    public function achOrder($directOrderParams)
    {
        $this->_wplogger->info('########## Submitting ACH order request. OrderCode: ' .
            $directOrderParams['orderCode'] . ' ##########');

        $xmlUsername = $this->worldpayhelper->getXmlUsername($directOrderParams['paymentDetails']['paymentType']);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($directOrderParams['paymentDetails']['paymentType']);
        $merchantCode = $directOrderParams['merchantCode'];
        if (!empty($directOrderParams['isMultishippingOrder'])) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }

        ##### Added orderContent node for plugin tracker ######
        $directOrderParams['orderContent'] = $this->collectPluginTrackerDetails(
            $directOrderParams['paymentDetails']['paymentType']
        );

        $captureDelay = $this->worldpayhelper->getCaptureDelayValues();
        $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\ACHOrder();
        $orderSimpleXml = $this->xmldirectorder->build(
            $merchantCode,
            $directOrderParams['orderCode'],
            $directOrderParams['orderDescription'],
            $directOrderParams['currencyCode'],
            $directOrderParams['amount'],
            $directOrderParams['orderContent'],
            $directOrderParams['paymentDetails'],
            $directOrderParams['shopperEmail'],
            $directOrderParams['acceptHeader'],
            $directOrderParams['userAgentHeader'],
            $directOrderParams['shippingAddress'],
            $directOrderParams['billingAddress'],
            $directOrderParams['shopperId'],
            $directOrderParams['statementNarrative'],
            $directOrderParams['exponent'],
            $captureDelay
        );

        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Send SEPA order XML to Worldpay server
     *
     * @param array $directOrderParams
     * @return mixed
     */
    public function sepaOrder($directOrderParams)
    {
        $this->_wplogger->info('########## Submitting SEPA order request. OrderCode: ' .
            $directOrderParams['orderCode'] . ' ##########');

        $xmlUsername = $this->worldpayhelper->getXmlUsername($directOrderParams['paymentDetails']['paymentType']);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($directOrderParams['paymentDetails']['paymentType']);
        $merchantCode = $directOrderParams['merchantCode'];
        if (!empty($directOrderParams['isMultishippingOrder'])) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }
        $captureDelay = $this->worldpayhelper->getCaptureDelayValues();

        ##### Added orderContent node for plugin tracker ######
        $directOrderParams['orderContent'] = $this->collectPluginTrackerDetails(
            $directOrderParams['paymentDetails']['paymentType']
        );

        $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\SEPAOrder();
        $orderSimpleXml = $this->xmldirectorder->build(
            $merchantCode,
            $directOrderParams['orderCode'],
            $directOrderParams['orderDescription'],
            $directOrderParams['currencyCode'],
            $directOrderParams['amount'],
            $directOrderParams['orderContent'],
            $directOrderParams['paymentDetails'],
            $directOrderParams['shopperEmail'],
            $directOrderParams['acceptHeader'],
            $directOrderParams['userAgentHeader'],
            $directOrderParams['shippingAddress'],
            $directOrderParams['billingAddress'],
            $directOrderParams['shopperId'],
            $directOrderParams['statementNarrative'],
            $directOrderParams['exponent'],
            $captureDelay
        );

        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Send a payment request using tokenised saved card to the WorldPay server based on the order parameters.
     *
     * @param array $tokenOrderParams
     * @return mixed
     */
    public function orderToken($tokenOrderParams)
    {
        $loggerMsg = '########## Submitting direct token order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $tokenOrderParams['orderCode'] . ' ##########');

        if ($this->worldpayhelper->isLevel23Enabled()
            && isset($tokenOrderParams['paymentDetails']['cardType'])
            && ($tokenOrderParams['paymentDetails']['cardType'] === 'ECMC-SSL'
                || $tokenOrderParams['paymentDetails']['cardType'] === 'VISA-SSL')
            && ($tokenOrderParams['billingAddress']['countryCode'] === 'US'
                || $tokenOrderParams['billingAddress']['countryCode'] === 'CA')) {
            $tokenOrderParams['paymentDetails']['isLevel23Enabled'] = true;
            $tokenOrderParams['paymentDetails']['cardAcceptorTaxId'] = $this->worldpayhelper->getCardAcceptorTaxId();
            $tokenOrderParams['paymentDetails']['dutyAmount'] = $this->worldpayhelper->getDutyAmount();
            $tokenOrderParams['paymentDetails']['countryCode'] = $tokenOrderParams['billingAddress']['countryCode'];
        }

        $requestConfiguration = [
            'threeDSecureConfig' => $tokenOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $tokenOrderParams['tokenRequestConfig']
        ];

        $methodCode = $tokenOrderParams['paymentDetails']['brand'];
        if (isset($tokenOrderParams['paymentDetails']['methodCode'])) {
            $methodCode = $tokenOrderParams['paymentDetails']['methodCode'];
        }
        $xmlUsername = $this->worldpayhelper->getXmlUsername($methodCode);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($methodCode);
        $merchantCode = $tokenOrderParams['merchantCode'];

        if ($tokenOrderParams['method'] == 'worldpay_moto'
            && $tokenOrderParams['paymentDetails']['dynamicInteractionType'] == 'MOTO') {
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
        }

        // Different Merchant code for Recurring Orders
        if (!empty($tokenOrderParams['paymentDetails']['isRecurringOrder'])) {
            if ($tokenOrderParams['paymentDetails']['isRecurringOrder'] == 1) {
                $recurringUserName = $this->worldpayhelper->getRecurringUsername();
                $recurringPassword = $this->worldpayhelper->getRecurringPassword();
                $recurringCode = $this->worldpayhelper->getRecurringMerchantCode();
                $xmlUsername    = !empty($recurringUserName) ? $recurringUserName : $xmlUsername;
                $xmlPassword    = !empty($recurringPassword) ? $recurringPassword : $xmlPassword;
                $merchantCode   = !empty($recurringCode) ? $recurringCode : $merchantCode;
            }
        }

        $this->xmltokenorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectOrder(
            $this->customerSession,
            $this->worldpayhelper,
            $requestConfiguration
        );
        if (empty($tokenOrderParams['thirdPartyData']) && empty($tokenOrderParams['shippingfee'])) {
            $tokenOrderParams['thirdPartyData']='';
            $tokenOrderParams['shippingfee']='';
        }
        if (empty($tokenOrderParams['primeRoutingData'])) {
            $tokenOrderParams['primeRoutingData'] = '';
        }
        if (empty($tokenOrderParams['orderLineItems'])) {
            $tokenOrderParams['orderLineItems'] = '';
        }

        if (!empty($tokenOrderParams['isMultishippingOrder'])) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }
        $tokenOrderParams['paymentDetails']['isEnabledEFTPOS'] = false;
        if ($this->worldpayhelper->isEnabledEFTPOS()) {
            $eftposMerchantCode = $this->worldpayhelper->getEFTPOSMerchantCode();
            $eftposMerchantUn = $this->worldpayhelper->getEFTPosXmlUsername();
            $eftposMerchantPw = $this->worldpayhelper->getEFTPOSXmlPassword();

            $xmlUsername = !empty($eftposMerchantUn) ? $eftposMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($eftposMerchantPw) ? $eftposMerchantPw : $xmlPassword ;
            $merchantCode = !empty($eftposMerchantCode) ? $eftposMerchantCode : $merchantCode ;
            $tokenOrderParams['paymentDetails']['isEnabledEFTPOS'] = true;
            $tokenOrderParams['paymentDetails']['sendShopperIpAddress'] = $this->isSendShopperIpAddress();
        }

        if ($this->worldpayhelper->isEnabledEFTPOS()
            && !empty($this->worldpayhelper->getEFTPOSRoutingMid())) {
            $tokenOrderParams['paymentDetails']['routingMID'] =
                $this->worldpayhelper->getEFTPOSRoutingMid();
        }

        $captureDelay = $this->worldpayhelper->getCaptureDelayValues();
        ##### Added orderContent node for plugin tracker ######
        $tokenOrderParams['orderContent'] = $this->collectPluginTrackerDetails(
            $tokenOrderParams['paymentDetails']['paymentType']
        );
        if ($this->productOnDemand->isProductOnDemandQuote()) {
            $tokenOrderParams['paymentDetails']['zero_auth_order'] = 1;
        }
        $orderSimpleXml = $this->xmltokenorder->build(
            $merchantCode,
            $tokenOrderParams['orderCode'],
            $tokenOrderParams['orderDescription'],
            $tokenOrderParams['currencyCode'],
            $tokenOrderParams['amount'],
            $tokenOrderParams['orderContent'],
            $tokenOrderParams['paymentDetails'],
            $tokenOrderParams['cardAddress'],
            $tokenOrderParams['shopperEmail'],
            $tokenOrderParams['acceptHeader'],
            $tokenOrderParams['userAgentHeader'],
            $tokenOrderParams['shippingAddress'],
            $tokenOrderParams['billingAddress'],
            $tokenOrderParams['shopperId'],
            $tokenOrderParams['saveCardEnabled'],
            $tokenOrderParams['tokenizationEnabled'],
            $tokenOrderParams['storedCredentialsEnabled'],
            $tokenOrderParams['cusDetails'],
            $tokenOrderParams['exemptionEngine'],
            $tokenOrderParams['thirdPartyData'],
            $tokenOrderParams['shippingfee'],
            $tokenOrderParams['exponent'],
            $tokenOrderParams['primeRoutingData'],
            $tokenOrderParams['orderLineItems'],
            $captureDelay,
            $tokenOrderParams['browserFields'],
            $tokenOrderParams['telephoneNumber']
        );
        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Send redirect order XML to Worldpay server
     *
     * @param array $redirectOrderParams
     * @return mixed
     */
    public function redirectOrder($redirectOrderParams)
    {
        $loggerMsg = '########## Submitting redirect order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $redirectOrderParams['orderCode'] . ' ##########');

        //Level 23 data validation
        if ($this->worldpayhelper->isLevel23Enabled()
            && ($redirectOrderParams['paymentType'] === 'ECMC-SSL'
                || $redirectOrderParams['paymentType'] === 'VISA-SSL')
            && ($redirectOrderParams['billingAddress']['countryCode'] === 'US'
                || $redirectOrderParams['billingAddress']['countryCode'] === 'CA')) {
            $redirectOrderParams['paymentDetails']['isLevel23Enabled'] = true;
            $redirectOrderParams['paymentDetails']['cardAcceptorTaxId'] = $this->worldpayhelper->getCardAcceptorTaxId();
            $redirectOrderParams['paymentDetails']['dutyAmount'] = $this->worldpayhelper->getDutyAmount();
            $redirectOrderParams['paymentDetails']['countryCode'] =
                $redirectOrderParams['billingAddress']['countryCode'];
        }

        if (
            $this->worldpayhelper->isHppPaypalSmartButtonEnabled()
            && $this->worldpayhelper->isApmEnabled()
            && $redirectOrderParams['paymentType'] == "ONLINE"
        ) {
            $redirectOrderParams['paymentType'] = "ONLINE,PAYPAL-SSL";
        }

        $requestConfiguration = [
            'threeDSecureConfig' => $redirectOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $redirectOrderParams['tokenRequestConfig'],
            'shopperId' => $redirectOrderParams['shopperId']
        ];

        $xmlUsername = $this->worldpayhelper->getXmlUsername($redirectOrderParams['paymentDetails']['cardType']);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($redirectOrderParams['paymentDetails']['cardType']);
        $merchantCode = $redirectOrderParams['merchantCode'];
        $installationId = $redirectOrderParams['installationId'];

        if ($redirectOrderParams['method'] == 'worldpay_moto') {
            $redirectOrderParams['paymentDetails']['PaymentMethod'] = $redirectOrderParams['method'];
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
        }

        if (!empty($redirectOrderParams['isMultishippingOrder'])) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();
            $msInstallationId = $this->worldpayhelper->getMultishippingInstallationId();
            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
            $installationId = !empty($msInstallationId) ? $msInstallationId : $installationId ;
        }
        if (!empty($redirectOrderParams['is_paybylink_order'])) {
            $pblMerchantUn = $this->worldpayhelper->getPayByLinkMerchantUsername();
            $pblMerchantPw = $this->worldpayhelper->getPayByLinkMerchantPassword();
            $pblMerchantCode = $this->worldpayhelper->getPayByLinkMerchantCode();
            $pblInstallationId = $this->worldpayhelper->getPayByLinkInstallationId();
            $xmlUsername = !empty($pblMerchantUn) ? $pblMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($pblMerchantPw) ? $pblMerchantPw : $xmlPassword ;
            $merchantCode = !empty($pblMerchantCode) ? $pblMerchantCode : $merchantCode ;
            $installationId = !empty($pblInstallationId) ? $pblInstallationId : $installationId ;
        }
        ##### Added orderContent node for plugin tracker ######
        $redirectOrderParams['orderContent'] = $this->collectPluginTrackerDetails(
            $redirectOrderParams['paymentDetails']['cardType']
        );

        $this->xmlredirectorder = new RedirectOrder();

        if ($this->worldpayhelper->getsubscriptionStatus()) {
            $redirectOrderParams['paymentDetails']['subscription_order'] = 1;
        }

        $captureDelay = $this->worldpayhelper->getCaptureDelayValues();
        $redirectSimpleXml = $this->xmlredirectorder->build(
            $merchantCode,
            $redirectOrderParams,
            $installationId,
            $captureDelay,
            $requestConfiguration
        );
        return $this->_sendRequest(
            dom_import_simplexml($redirectSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Send Klarna Order request to Worldpay server
     *
     * @param array $redirectOrderParams
     * @return mixed
     */
    public function redirectKlarnaOrder($redirectOrderParams)
    {
        try {
            $loggerMsg = '########## Submitting klarna redirect order request. OrderCode: ';
            $this->_wplogger->info($loggerMsg . $redirectOrderParams['orderCode'] . ' ##########');
            if (empty($redirectOrderParams['statementNarrative'])) {
                $redirectOrderParams['statementNarrative']='';
            }
            $xmlUsername = $this->worldpayhelper->getXmlUsername($redirectOrderParams['paymentType']);
            $xmlPassword = $this->worldpayhelper->getXmlPassword($redirectOrderParams['paymentType']);
            $merchantCode = $redirectOrderParams['merchantCode'];
            $installationId = $redirectOrderParams['installationId'];
            if (!empty($redirectOrderParams['isMultishippingOrder'])) {
                $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
                $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
                $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();
                $msinstallationId = $this->worldpayhelper->getMultishippingInstallationId();

                $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
                $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
                $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
                $installationId = !empty($msinstallationId) ? $msinstallationId : $installationId ;
            }

            ##### Added orderContent node for plugin tracker ######
            $redirectOrderParams['orderContent'] = $this->collectPluginTrackerDetails($redirectOrderParams['paymentType']);

            $captureDelay = $this->worldpayhelper->getCaptureDelayValues();

            $isStorePickup = $this->worldpayhelper->isStorePickup();
            $isEnabledStorePickup = $this->worldpayhelper->isStorePickUpEnabled();
            $storePickUpMethod = $this->worldpayhelper->getStorePickUpMethod();
            $storepickUpType = $this->worldpayhelper->getStorePickUpType();

            $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\RedirectKlarnaOrder();

            $redirectSimpleXml = $this->xmlredirectorder->build(
                $merchantCode,
                $redirectOrderParams['orderCode'],
                $redirectOrderParams['orderDescription'],
                $redirectOrderParams['currencyCode'],
                $redirectOrderParams['amount'],
                $redirectOrderParams['paymentType'],
                $redirectOrderParams['shopperEmail'],
                $redirectOrderParams['statementNarrative'],
                $redirectOrderParams['acceptHeader'],
                $redirectOrderParams['userAgentHeader'],
                $redirectOrderParams['shippingAddress'],
                $redirectOrderParams['billingAddress'],
                $redirectOrderParams['paymentPagesEnabled'],
                $installationId,
                $redirectOrderParams['hideAddress'],
                $redirectOrderParams['orderLineItems'],
                $redirectOrderParams['exponent'],
                $redirectOrderParams['sessionData'],
                $redirectOrderParams['orderContent'],
                $captureDelay,
                $isStorePickup,
                $isEnabledStorePickup,
                $storePickUpMethod,
                $storepickUpType
            );

            return $this->_sendRequest(
                dom_import_simplexml($redirectSimpleXml)->ownerDocument,
                $xmlUsername,
                $xmlPassword
            );
        } catch (Exception $ex) {
            $this->_wplogger->error($ex->getMessage());
            if ($ex->getMessage() == 'Payment Method KLARNA_PAYNOW-SSL is unknown; The Payment Method is not available.'
                || $ex->getMessage() == 'Payment Method KLARNA_SLICEIT-SSL is unknown; '
                . 'The Payment Method is not available.'
                || $ex->getMessage() == 'Payment Method KLARNA_PAYLATER-SSL is unknown; '
                . 'The Payment Method is not available.') {
                $codeErrorMessage = 'Klarna payment method is currently not available for this country.';
                $camErrorMessage = $this->getCreditCardSpecificException('AKLR01');
                $errorMessage = $camErrorMessage? $camErrorMessage : $codeErrorMessage;
                throw new \Magento\Framework\Exception\LocalizedException(__($errorMessage));
            }
        }
    }

    /**
     * Send direct ideal order XML to Worldpay server
     *
     * @param array $redirectOrderParams
     * @return mixed
     */
    public function directIdealOrder($redirectOrderParams)
    {
        $loggerMsg = '########## Submitting direct Ideal order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $redirectOrderParams['orderCode'] . ' ##########');

        $requestConfiguration = [
            'threeDSecureConfig' => $redirectOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $redirectOrderParams['tokenRequestConfig'],
            'shopperId' => $redirectOrderParams['shopperId']
        ];
        if (empty($redirectOrderParams['statementNarrative'])) {
            $redirectOrderParams['statementNarrative']='';
        }

        $xmlUsername = $this->worldpayhelper->getXmlUsername($redirectOrderParams['paymentType']);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($redirectOrderParams['paymentType']);
        $merchantCode = $redirectOrderParams['merchantCode'];
        $installationId = $redirectOrderParams['installationId'];
        if (!empty($redirectOrderParams['isMultishippingOrder'])) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();
            $msMerchantInstallationId = $this->worldpayhelper->getMultishippingInstallationId();

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
            $installationId = !empty($msMerchantInstallationId) ? $msMerchantInstallationId : $installationId ;
        }
        $captureDelay = $this->worldpayhelper->getCaptureDelayValues();
        ##### Added orderContent node for plugin tracker ######
        $redirectOrderParams['orderContent'] = $this->collectPluginTrackerDetails($redirectOrderParams['paymentType']);

        $this->xmldirectidealorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectIdealOrder($requestConfiguration);
        $redirectSimpleXml = $this->xmldirectidealorder->build(
            $merchantCode,
            $redirectOrderParams['orderCode'],
            $redirectOrderParams['orderDescription'],
            $redirectOrderParams['currencyCode'],
            $redirectOrderParams['amount'],
            $redirectOrderParams['orderContent'],
            $redirectOrderParams['paymentType'],
            $redirectOrderParams['shopperEmail'],
            $redirectOrderParams['statementNarrative'],
            $redirectOrderParams['acceptHeader'],
            $redirectOrderParams['userAgentHeader'],
            $redirectOrderParams['shippingAddress'],
            $redirectOrderParams['billingAddress'],
            $redirectOrderParams['paymentPagesEnabled'],
            $installationId,
            $redirectOrderParams['hideAddress'],
            $redirectOrderParams['callbackurl'],
            $redirectOrderParams['cc_bank'],
            $redirectOrderParams['exponent'],
            $captureDelay
        );

        return $this->_sendRequest(
            dom_import_simplexml($redirectSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Send capture XML to Worldpay server
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $wp
     * @param string $paymentMethodCode
     * @param array|null $capturedItems
     * @return mixed
     */
    public function capture(\Magento\Sales\Model\Order $order, $wp, $paymentMethodCode, $capturedItems = null)
    {
        try {
            $orderCode = $wp->getWorldpayOrderId();
            $loggerMsg = '########## Submitting capture request. Order: ';
            $this->_wplogger->info($loggerMsg . $orderCode . ' Amount:' . $order->getGrandTotal() . ' ##########');
            $this->xmlcapture = new \Sapient\Worldpay\Model\XmlBuilder\Capture(
                $this->scopeConfig,
                $this->storeManager
            );
            $currencyCode = $order->getOrderCurrencyCode();
            $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);

            if (strpos($wp->getPaymentType(), "KLARNA") !== false && !empty($capturedItems)) {
                $instorePickup = '';
                if (strpos($order->getShippingMethod(), 'instore_pickup') !== false) {
                    $instorePickup = $orderCode;
                }
                $invoicedItems = $this->getInvoicedItemsDetails($capturedItems, $instorePickup);
            } else {
                $invoicedItems = '';
            }
            $captureType = 'full';
            $storeId = $order->getStoreId();
            $xmlUsername = $this->worldpayhelper->getXmlUsername($wp->getPaymentType(), $storeId);
            $xmlPassword = $this->worldpayhelper->getXmlPassword($wp->getPaymentType(), $storeId);
            $merchantCode = $this->worldpayhelper->getMerchantCode($wp->getPaymentType(), $storeId);
            if ($wp->getInteractionType() === 'MOTO') {
                $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                    ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
                $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                    ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
                $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                    ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
            }

            if ($wp->getIsMultishippingOrder()) {
                $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode($storeId);
                $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername($storeId);
                $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword($storeId);

                $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
                $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
                $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
            }
            if ($paymentMethodCode == 'worldpay_paybylink') {
                $pblMerchantCode = $this->worldpayhelper->getPayByLinkMerchantCode($storeId);
                $pblMerchantUn = $this->worldpayhelper->getPayByLinkMerchantUsername($storeId);
                $pblMerchantPw = $this->worldpayhelper->getPayByLinkMerchantPassword($storeId);
                $merchantCode = !empty($pblMerchantCode) ? $pblMerchantCode : $merchantCode;
                $xmlUsername = !empty($pblMerchantUn) ? $pblMerchantUn : $xmlUsername;
                $xmlPassword = !empty($pblMerchantPw) ? $pblMerchantPw : $xmlPassword;
            }

            if ($this->worldpayhelper->isEnabledEFTPOS()) {
                $eftposMerchantCode = $this->worldpayhelper->getEFTPOSMerchantCode();
                $eftposMerchantUn = $this->worldpayhelper->getEFTPosXmlUsername();
                $eftposMerchantPw = $this->worldpayhelper->getEFTPOSXmlPassword();

                $xmlUsername = !empty($eftposMerchantUn) ? $eftposMerchantUn : $xmlUsername ;
                $xmlPassword = !empty($eftposMerchantPw) ? $eftposMerchantPw : $xmlPassword ;
                $merchantCode = !empty($eftposMerchantCode) ? $eftposMerchantCode : $merchantCode ;
            }

            $captureSimpleXml = $this->xmlcapture->build(
                $merchantCode,
                $orderCode,
                $order->getOrderCurrencyCode(),
                $order->getGrandTotal(),
                $exponent,
                $order,
                $captureType,
                $wp->getIsMultishippingOrder(),
                $wp->getPaymentType(),
                $invoicedItems
            );

            return $this->_sendRequest(
                dom_import_simplexml($captureSimpleXml)->ownerDocument,
                $xmlUsername,
                $xmlPassword
            );
        } catch (Exception $e) {
            $this->_wplogger->error($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Send Partial capture XML to Worldpay server
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $wp
     * @param float $grandTotal
     * @param array|null $capturedItems
     * @param string|null $paymentMethodCode
     * @return mixed
     */
    public function partialCapture(
        \Magento\Sales\Model\Order $order,
                                   $wp,
                                   $grandTotal,
                                   $capturedItems = null,
                                   $paymentMethodCode = null
    ) {
        try {
            $orderCode = $wp->getWorldpayOrderId();
            $loggerMsg = '########## Submitting Partial capture request. Order: ';
            $this->_wplogger->info($loggerMsg . $orderCode . ' Amount:' . $grandTotal . ' ##########');
            $this->xmlcapture = new \Sapient\Worldpay\Model\XmlBuilder\Capture(
                $this->scopeConfig,
                $this->storeManager
            );
            $currencyCode = $order->getOrderCurrencyCode();
            $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);

            if (strpos($wp->getPaymentType(), "KLARNA") !== false && !empty($capturedItems)) {
                $instorePickup='';
                if (strpos($order->getShippingMethod(), 'instore_pickup') !== false) {
                    $instorePickup = $orderCode;
                }
                $invoicedItems = $this->getInvoicedItemsDetails($capturedItems, $instorePickup);
            } else {
                $invoicedItems = '';
            }

            $captureType = 'partial';
            $storeId = $order->getStoreId();
            $xmlUsername = $this->worldpayhelper->getXmlUsername($wp->getPaymentType(), $storeId);
            $xmlPassword = $this->worldpayhelper->getXmlPassword($wp->getPaymentType(), $storeId);
            $merchantCode = $this->worldpayhelper->getMerchantCode($wp->getPaymentType(), $storeId);

            if ($wp->getInteractionType() === 'MOTO') {
                $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                    ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
                $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                    ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
                $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                    ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
            }
            if ($wp->getIsMultishippingOrder()) {
                $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode($storeId);
                $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername($storeId);
                $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword($storeId);

                $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
                $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
                $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
            }
            if ($paymentMethodCode == 'worldpay_paybylink') {
                $pblMerchantCode = $this->worldpayhelper->getPayByLinkMerchantCode($storeId);
                $pblMerchantUn = $this->worldpayhelper->getPayByLinkMerchantUsername($storeId);
                $pblMerchantPw = $this->worldpayhelper->getPayByLinkMerchantPassword($storeId);
                $merchantCode = !empty($pblMerchantCode) ? $pblMerchantCode : $merchantCode;
                $xmlUsername = !empty($pblMerchantUn) ? $pblMerchantUn : $xmlUsername;
                $xmlPassword = !empty($pblMerchantPw) ? $pblMerchantPw : $xmlPassword;
            }

            if ($this->worldpayhelper->isEnabledEFTPOS()) {
                $eftposMerchantCode = $this->worldpayhelper->getEFTPOSMerchantCode();
                $eftposMerchantUn = $this->worldpayhelper->getEFTPosXmlUsername();
                $eftposMerchantPw = $this->worldpayhelper->getEFTPOSXmlPassword();

                $xmlUsername = !empty($eftposMerchantUn) ? $eftposMerchantUn : $xmlUsername ;
                $xmlPassword = !empty($eftposMerchantPw) ? $eftposMerchantPw : $xmlPassword ;
                $merchantCode = !empty($eftposMerchantCode) ? $eftposMerchantCode : $merchantCode ;
            }

            $captureSimpleXml = $this->xmlcapture->build(
                $merchantCode,
                $orderCode,
                $order->getOrderCurrencyCode(),
                $grandTotal,
                $exponent,
                $order,
                $captureType,
                $wp->getIsMultishippingOrder(),
                $wp->getPaymentType(),
                $invoicedItems
            );

            return $this->_sendRequest(
                dom_import_simplexml($captureSimpleXml)->ownerDocument,
                $xmlUsername,
                $xmlPassword
            );
        } catch (Exception $e) {
            $this->_wplogger->error($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Process the request
     *
     * @param SimpleXmlElement $xml
     * @param string $username
     * @param string $password
     * @return SimpleXmlElement $response
     */
    public function _sendRequest($xml, $username, $password)
    {
        $response = $this->_request->sendRequest($xml, $username, $password);
        $this->_checkForError($response, $xml);
        return $response;
    }

    /**
     * Cancel Pending order Cron request
     *
     * @param SimpleXmlElement $xml
     * @param string $username
     * @param string $password
     * @param bool $isOrderClenup
     * @return SimpleXmlElement $response
     */
    protected function _sendPendingOrderRequest($xml, $username, $password, $isOrderClenup)
    {
        $response = $this->_request->sendRequest($xml, $username, $password);
        $this->_checkForError($response, $xml, $isOrderClenup);
        return $response;
    }

    /**
     * Check error
     *
     * @param SimpleXmlElement $response
     * @param string|null $xml
     * @param bool|null $isOrderClenup
     * @throw Exception
     */
    protected function _checkForError($response, $xml = "", $isOrderClenup = "")
    {
        $paymentService = new \SimpleXmlElement($response);
        $lastEvent = $paymentService->xpath('//lastEvent');
        if ($lastEvent && $lastEvent[0] =='REFUSED') {
            return;
        }
        $error = $paymentService->xpath('//error');
        if ($isOrderClenup) {
            if ($error) {
                $this->emailErrorReportHelper->sendErrorReport([
                    'request'=>$xml->saveXML(),
                    'response'=>$response,
                    'error_code'=>''.$error[0]['code'],
                    'error_message'=>''.$error[0]
                ]);
            }
            return $response;
        }

        if ($error) {
            $this->emailErrorReportHelper->sendErrorReport([
                'request'=>$xml->saveXML(),
                'response'=>$response,
                'error_code'=>''.$error[0]['code'],
                'error_message'=>''.$error[0]
            ]);

            $this->_wplogger->error('An error occurred while sending the request');
            $this->_wplogger->error('Error (code ' . $error[0]['code'] . '): ' . $error[0]);
            if ($error[0]['code'] == 6) {
                $error[0] = $this->getCreditCardSpecificException('CCAM12');
            }
            throw new \Magento\Framework\Exception\ValidatorException(
                __($error[0])
            );
        }
    }

    /**
     * Send refund XML to Worldpay server
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $wp
     * @param string $paymentMethodCode
     * @param float $amount
     * @param string|array $reference
     * @return mixed
     */
    public function refund(
        \Magento\Sales\Model\Order $order,
                                   $wp,
                                   $paymentMethodCode,
                                   $amount,
                                   $reference
    ) {
        $orderCode = $wp->getWorldpayOrderId();
        $loggerMsg = '########## Submitting refund request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $orderCode . ' ##########');
        $this->xmlrefund = new \Sapient\Worldpay\Model\XmlBuilder\Refund(
            $this->scopeConfig
        );
        $currencyCode = $order->getOrderCurrencyCode();
        $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);
        $storeId = $order->getStoreId();
        $xmlUsername = $this->worldpayhelper->getXmlUsername($wp->getPaymentType(), $storeId);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($wp->getPaymentType(), $storeId);
        $merchantCode = $this->worldpayhelper->getMerchantCode($wp->getPaymentType(), $storeId);
        if ($wp->getInteractionType() === 'MOTO') {
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
        }
        if ($wp->getIsMultishippingOrder()) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode($storeId);
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername($storeId);
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword($storeId);

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }
        if ($paymentMethodCode == 'worldpay_paybylink') {
            $pblMerchantCode = $this->worldpayhelper->getPayByLinkMerchantCode($storeId);
            $pblMerchantUn = $this->worldpayhelper->getPayByLinkMerchantUsername($storeId);
            $pblMerchantPw = $this->worldpayhelper->getPayByLinkMerchantPassword($storeId);
            $merchantCode = !empty($pblMerchantCode) ? $pblMerchantCode : $merchantCode;
            $xmlUsername = !empty($pblMerchantUn) ? $pblMerchantUn : $xmlUsername;
            $xmlPassword = !empty($pblMerchantPw) ? $pblMerchantPw : $xmlPassword;
        }
        if ($this->worldpayhelper->isEnabledEFTPOS()) {
            $eftposMerchantCode = $this->worldpayhelper->getEFTPOSMerchantCode();
            $eftposMerchantUn = $this->worldpayhelper->getEFTPosXmlUsername();
            $eftposMerchantPw = $this->worldpayhelper->getEFTPOSXmlPassword();

            $xmlUsername = !empty($eftposMerchantUn) ? $eftposMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($eftposMerchantPw) ? $eftposMerchantPw : $xmlPassword ;
            $merchantCode = !empty($eftposMerchantCode) ? $eftposMerchantCode : $merchantCode ;
        }
        $refundSimpleXml = $this->xmlrefund->build(
            $merchantCode,
            $orderCode,
            $order->getOrderCurrencyCode(),
            $amount,
            $reference,
            $exponent,
            $order,
            $wp->getPaymentType()
        );

        return $this->_sendRequest(
            dom_import_simplexml($refundSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Send order inquery XML to Worldpay server
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @param int $storeId
     * @param string $paymentMethodCode
     * @param string $paymenttype
     * @param string $interactionType
     * @param bool|null $isOrderClenup
     * @return mixed
     */
    public function inquiry(
        $merchantCode,
        $orderCode,
        $storeId,
        $paymentMethodCode,
        $paymenttype,
        $interactionType,
        $isOrderClenup = ""
    ) {
        $this->_wplogger->info('########## Submitting order inquiry. OrderCode: (' . $orderCode . ') ##########');
        $xmlUsername = $this->worldpayhelper->getXmlUsername($paymenttype, $storeId);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($paymenttype, $storeId);
        $merchantcode = $merchantCode;

        if ($interactionType === 'MOTO') {
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantcode = !empty($this->worldpayhelper->getMotoMerchantCode())
                ? $this->worldpayhelper->getMotoMerchantCode() : $merchantcode;
        }

        if ($interactionType == \Sapient\Worldpay\Model\Payment\Service::INTERACTION_TYPE_MS) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode($storeId);
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername($storeId);
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword($storeId);

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantcode = !empty($msMerchantCode) ? $msMerchantCode : $merchantcode ;
        }

        if ($paymentMethodCode == 'worldpay_paybylink') {
            $pblMerchantCode = $this->worldpayhelper->getPayByLinkMerchantCode($storeId);
            $pblMerchantUn = $this->worldpayhelper->getPayByLinkMerchantUsername($storeId);
            $pblMerchantPw = $this->worldpayhelper->getPayByLinkMerchantPassword($storeId);
            $merchantcode = !empty($pblMerchantCode) ? $pblMerchantCode : $merchantcode;
            $xmlUsername = !empty($pblMerchantUn) ? $pblMerchantUn : $xmlUsername;
            $xmlPassword = !empty($pblMerchantPw) ? $pblMerchantPw : $xmlPassword;
        }

        if ($this->worldpayhelper->isEnabledEFTPOS()) {
            $eftposMerchantCode = $this->worldpayhelper->getEFTPOSMerchantCode();
            $eftposMerchantUn = $this->worldpayhelper->getEFTPosXmlUsername();
            $eftposMerchantPw = $this->worldpayhelper->getEFTPOSXmlPassword();

            $xmlUsername = !empty($eftposMerchantUn) ? $eftposMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($eftposMerchantPw) ? $eftposMerchantPw : $xmlPassword ;
            $merchantcode = !empty($eftposMerchantCode) ? $eftposMerchantCode : $merchantcode ;
        }

        $this->xmlinquiry = new \Sapient\Worldpay\Model\XmlBuilder\Inquiry();
        $inquirySimpleXml = $this->xmlinquiry->build(
            $merchantcode,
            $orderCode
        );

        if ($isOrderClenup) {
            return $this->_sendPendingOrderRequest(
                dom_import_simplexml($inquirySimpleXml)->ownerDocument,
                $xmlUsername,
                $xmlPassword,
                $isOrderClenup
            );
        }

        return $this->_sendRequest(
            dom_import_simplexml($inquirySimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Send token update XML to Worldpay server
     *
     * @param SavedToken $tokenModel
     * @param \Magento\Customer\Model\Customer $customer
     * @param int $storeId
     * @return mixed
     */
    public function tokenUpdate(
        SavedToken $tokenModel,
        \Magento\Customer\Model\Customer $customer,
        $storeId
    ) {
        $this->_wplogger->info('########## Submitting token update. TokenId: ' . $tokenModel->getId() . ' ##########');
        $requestParameters = [
            'tokenModel'   => $tokenModel,
            'customer'     => $customer,
            'merchantCode' => $this->worldpayhelper->getMerchantCode($tokenModel->getMethod()),
        ];
        /** @var SimpleXMLElement $simpleXml */
        $this->tokenUpdateXml = new \Sapient\Worldpay\Model\XmlBuilder\TokenUpdate($requestParameters);
        $tokenUpdateSimpleXml = $this->tokenUpdateXml->build();

        return $this->_sendRequest(
            dom_import_simplexml($tokenUpdateSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($tokenModel->getMethod()),
            $this->worldpayhelper->getXmlPassword($tokenModel->getMethod())
        );
    }

    /**
     * Send token delete XML to Worldpay server
     *
     * @param SavedToken $tokenModel
     * @param \Magento\Customer\Model\Customer $customer
     * @param int $storeId
     * @return mixed
     */
    public function tokenDelete(
        SavedToken $tokenModel,
        \Magento\Customer\Model\Customer $customer,
        $storeId
    ) {
        $this->_wplogger->info('########## Submitting token Delete. TokenId: ' . $tokenModel->getId() . ' ##########');

        $requestParameters = [
            'tokenModel'   => $tokenModel,
            'customer'     => $customer,
            'merchantCode' => $this->worldpayhelper->getMerchantCode($tokenModel->getMethod()),
        ];

        $this->tokenDeleteXml = new \Sapient\Worldpay\Model\XmlBuilder\TokenDelete($requestParameters);
        $tokenDeleteSimpleXml = $this->tokenDeleteXml->build();

        return $this->_sendRequest(
            dom_import_simplexml($tokenDeleteSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($tokenModel->getMethod()),
            $this->worldpayhelper->getXmlPassword($tokenModel->getMethod())
        );
    }

    /**
     * Get Payment options based on country
     *
     * @param array $paymentOptionsParams
     * @param boolean $isMultishipping
     * @return mixed
     */
    public function paymentOptionsByCountry($paymentOptionsParams, $isMultishipping = false)
    {
        $spoofCountryId = '';
        $countryCodeSpoofs = $this->worldpayhelper->getCountryCodeSpoofs();
        if ($countryCodeSpoofs) {
            $spoofCountryId = $this->getCountryCodeSpoof($countryCodeSpoofs, $paymentOptionsParams['countryCode']);
        }

        $countryId = ($spoofCountryId)? $spoofCountryId : $paymentOptionsParams['countryCode'];
        $this->_wplogger->info('########## Submitting payment options request ##########');
        $this->xmlpaymentoptions = new \Sapient\Worldpay\Model\XmlBuilder\PaymentOptions();
        $paymentOptionsXml = $this->xmlpaymentoptions->build(
            $paymentOptionsParams['merchantCode'],
            $countryId
        );
        $merchantUn =  $this->worldpayhelper->getXmlUsername($paymentOptionsParams['paymentType']);
        $merchantPw =  $this->worldpayhelper->getXmlPassword($paymentOptionsParams['paymentType']);
        if ($isMultishipping) {
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $mcMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();
            $merchantUn = !empty($msMerchantUn) ? $msMerchantUn : $merchantUn;
            $merchantPw = !empty($mcMerchantPw) ? $mcMerchantPw : $merchantPw;
        }

        $isEnabledEftPos =  $this->worldpayhelper->isEnabledEFTPOS();
        if ($isEnabledEftPos) {
            $eftPosMerchantUn = $this->worldpayhelper->getEFTPosXmlUsername();
            $eftPoscMerchantPw = $this->worldpayhelper->getEFTPOSXmlPassword();
            $merchantUn = !empty($eftPosMerchantUn) ? $eftPosMerchantUn : $merchantUn;
            $merchantPw = !empty($eftPoscMerchantPw) ? $eftPoscMerchantPw : $merchantPw;
        }

        return $this->_sendRequest(
            dom_import_simplexml($paymentOptionsXml)->ownerDocument,
            $merchantUn,
            $merchantPw
        );
    }

    /**
     * Send wallet order XML to Worldpay server
     *
     * @param array $walletOrderParams
     * @return mixed
     */
    public function walletsOrder($walletOrderParams)
    {
        $loggerMsg = '########## Submitting wallet order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $walletOrderParams['orderCode'] . ' ##########');
        $requestConfiguration = [
            'threeDSecureConfig' => $walletOrderParams['threeDSecureConfig'],
        ];

        $xmlUsername = $this->worldpayhelper->getXmlUsername($walletOrderParams['paymentType']);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($walletOrderParams['paymentType']);
        $merchantCode = $walletOrderParams['merchantCode'];

        if (!empty($walletOrderParams['isMultishippingOrder'])) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();
            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }
        if ($this->worldpayhelper->isEnabledEFTPOS()) {
            $eFTMerchantCode = $this->worldpayhelper->getEFTPOSMerchantCode();
            $eFTMerchantUn = $this->worldpayhelper->getEFTPosXmlUsername();
            $eFTMerchantPw = $this->worldpayhelper->getEFTPOSXmlPassword();
            $xmlUsername = !empty($eFTMerchantUn) ? $eFTMerchantUn : $xmlUsername;
            $xmlPassword = !empty($eFTMerchantPw) ? $eFTMerchantPw : $xmlPassword;
            $merchantCode = !empty($eFTMerchantCode) ? $eFTMerchantCode : $merchantCode;
        }

        $captureDelay = $this->worldpayhelper->getCaptureDelayValues();
        ##### Added orderContent node for plugin tracker ######
        $walletOrderParams['orderContent'] = $this->collectPluginTrackerDetails($walletOrderParams['paymentType']);

        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\WalletOrder($requestConfiguration);
        $walletSimpleXml = $this->xmlredirectorder->build(
            $merchantCode,
            $walletOrderParams['orderCode'],
            $walletOrderParams['orderDescription'],
            $walletOrderParams['currencyCode'],
            $walletOrderParams['amount'],
            $walletOrderParams['orderContent'],
            $walletOrderParams['paymentType'],
            $walletOrderParams['shopperEmail'],
            $walletOrderParams['acceptHeader'],
            $walletOrderParams['userAgentHeader'],
            $walletOrderParams['protocolVersion'],
            $walletOrderParams['signature'],
            $walletOrderParams['signedMessage'],
            $walletOrderParams['shippingAddress'],
            $walletOrderParams['billingAddress'],
            $walletOrderParams['cusDetails'],
            $walletOrderParams['shopperIpAddress'],
            $walletOrderParams['paymentDetails'],
            $walletOrderParams['exponent'],
            $captureDelay,
            $walletOrderParams['browserFields']
        );

        return $this->_sendRequest(
            dom_import_simplexml($walletSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Send Apple Pay order XML to Worldpay server
     *
     * @param array $applePayOrderParams
     * @return mixed
     */
    public function applePayOrder($applePayOrderParams)
    {
        $loggerMsg = '########## Submitting apple pay order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $applePayOrderParams['orderCode'] . ' ##########');

        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\ApplePayOrder();

        $xmlUsername = $this->worldpayhelper->getXmlUsername($applePayOrderParams['paymentType']);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($applePayOrderParams['paymentType']);
        $merchantCode = $applePayOrderParams['merchantCode'];

        if (!empty($applePayOrderParams['isMultishippingOrder'])) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }
        $captureDelay = $this->worldpayhelper->getCaptureDelayValues();
        ##### Added orderContent node for plugin tracker ######
        $applePayOrderParams['orderContent'] = $this->collectPluginTrackerDetails($applePayOrderParams['paymentType']);

        $appleSimpleXml = $this->xmlredirectorder->build(
            $merchantCode,
            $applePayOrderParams['orderCode'],
            $applePayOrderParams['orderDescription'],
            $applePayOrderParams['currencyCode'],
            $applePayOrderParams['amount'],
            $applePayOrderParams['orderContent'],
            $applePayOrderParams['paymentType'],
            $applePayOrderParams['shopperEmail'],
            $applePayOrderParams['protocolVersion'],
            $applePayOrderParams['signature'],
            $applePayOrderParams['data'],
            $applePayOrderParams['ephemeralPublicKey'],
            $applePayOrderParams['publicKeyHash'],
            $applePayOrderParams['transactionId'],
            $applePayOrderParams['exponent'],
            $captureDelay,
            $applePayOrderParams['browserFields']
        );

        return $this->_sendRequest(
            dom_import_simplexml($appleSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Send Samsung Pay order XML to Worldpay server
     *
     * @param array $samsungPayOrderParams
     * @return mixed
     */
    public function samsungPayOrder($samsungPayOrderParams)
    {
        $loggerMsg = '########## Submitting samsung pay order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $samsungPayOrderParams['orderCode'] . ' ##########');

        $xmlUsername = $this->worldpayhelper->getXmlUsername($samsungPayOrderParams['paymentType']);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($samsungPayOrderParams['paymentType']);
        $merchantCode = $samsungPayOrderParams['merchantCode'];
        $captureDelay = $this->worldpayhelper->getCaptureDelayValues();
        if (!empty($samsungPayOrderParams['isMultishippingOrder'])) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }

        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\SamsungPayOrder();
        ##### Added orderContent node for plugin tracker ######
        $samsungPayOrderParams['orderContent'] = $this->collectPluginTrackerDetails($samsungPayOrderParams['paymentType']);

        $samsungPaySimpleXml = $this->xmlredirectorder->build(
            $merchantCode,
            $samsungPayOrderParams['orderCode'],
            $samsungPayOrderParams['orderDescription'],
            $samsungPayOrderParams['currencyCode'],
            $samsungPayOrderParams['amount'],
            $samsungPayOrderParams['orderContent'],
            $samsungPayOrderParams['paymentType'],
            $samsungPayOrderParams['shopperEmail'],
            $samsungPayOrderParams['data'],
            $samsungPayOrderParams['exponent'],
            $captureDelay,
            $samsungPayOrderParams['browserFields'],
            $samsungPayOrderParams['shopperIpAddress'],
            $samsungPayOrderParams['sessionId']
        );

        return $response =  $this->_sendRequest(
            dom_import_simplexml($samsungPaySimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    public function paypalOrder($directOrderParams)
    {
        $loggerMsg = '########## Submitting PayPal order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $directOrderParams['orderCode'] . ' ##########');

        $xmlUsername = $this->worldpayhelper->getXmlUsername($directOrderParams['paymentDetails']['paymentType']);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($directOrderParams['paymentDetails']['paymentType']);
        $merchantCode = $directOrderParams['merchantCode'];

        if (!empty($directOrderParams['isMultishippingOrder'])) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }

        $directOrderParams['paymentDetails']['sendShopperIpAddress'] = $this->isSendShopperIpAddress();
        ##### Added orderContent node for plugin tracker ######
        $directOrderParams['orderContent'] = $this->collectPluginTrackerDetails(
            $directOrderParams['paymentDetails']['paymentType']
        );

        $this->xmlPaypalOrder = new PaypalOrder($this->_urlBuilder);

        if ($this->worldpayhelper->getsubscriptionStatus()) {
            $directOrderParams['paymentDetails']['subscription_order'] = 1;
        }

        $directOrderParams['captureDelay'] = $this->worldpayhelper->getCaptureDelayValues();

        $orderSimpleXml = $this->xmlPaypalOrder->build($merchantCode, $directOrderParams);
        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword,
        );
    }

    /**
     * Send chromepay order XML to Worldpay server
     *
     * @param array $chromeOrderParams
     * @return mixed
     */
    public function chromepayOrder($chromeOrderParams)
    {
        $loggerMsg = '########## Submitting chromepay order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $chromeOrderParams['orderCode'] . ' ##########');
        $paymentType = 'worldpay_cc';
        $captureDelay = $this->worldpayhelper->getCaptureDelayValues();
        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\ChromePayOrder();
        ##### Added orderContent node for plugin tracker ######
        $chromeOrderParams['orderContent'] = $this->collectPluginTrackerDetails($paymentType);

        $chromepaySimpleXml = $this->xmlredirectorder->build(
            $chromeOrderParams['merchantCode'],
            $chromeOrderParams['orderCode'],
            $chromeOrderParams['orderDescription'],
            $chromeOrderParams['currencyCode'],
            $chromeOrderParams['amount'],
            $chromeOrderParams['orderContent'],
            $chromeOrderParams['paymentType'],
            $chromeOrderParams['paymentDetails'],
            $chromeOrderParams['shippingAddress'],
            $chromeOrderParams['billingAddress'],
            $chromeOrderParams['shopperEmail'],
            $chromeOrderParams['exponent'],
            $captureDelay,
            $chromeOrderParams['browserFields']
        );
        //echo $this->worldpayhelper->getXmlUsername($paymentType);exit;
        return $this->_sendRequest(
            dom_import_simplexml($chromepaySimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($paymentType),
            $this->worldpayhelper->getXmlPassword($paymentType)
        );
    }

    /**
     * Send 3d direct order XML to Worldpay server
     *
     * @param array $directOrderParams
     * @return mixed
     */
    public function order3Ds2Secure($directOrderParams)
    {
        $loggerMsg = '########## Submitting direct 3Ds2Secure order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . ' ##########');
        $merchantCode = $directOrderParams['merchantCode'];

        if (!empty($directOrderParams['isMultishippingOrder'])) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }

        if (isset($directOrderParams['tokenRequestConfig'])) {
            $requestConfiguration = [
                'threeDSecureConfig' => $directOrderParams['threeDSecureConfig'],
                'tokenRequestConfig' => $directOrderParams['tokenRequestConfig']
            ];
            $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectOrder(
                $this->customerSession,
                $this->worldpayhelper,
                $requestConfiguration
            );
            $paymentType = isset($directOrderParams['paymentDetails']['brand']) ?
                $directOrderParams['paymentDetails']['brand']: $directOrderParams['paymentDetails']['paymentType'];
            $orderSimpleXml = $this->xmldirectorder->build3Ds2Secure(
                $merchantCode,
                $directOrderParams['orderCode'],
                $directOrderParams['paymentDetails'],
                $directOrderParams['paymentDetails']['dfReferenceId']
            );
        } else {
            $requestConfiguration = [
                'threeDSecureConfig' => $directOrderParams['threeDSecureConfig']
            ];
            $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\WalletOrder($requestConfiguration);
            $paymentType = $directOrderParams['paymentType'];
            $orderSimpleXml = $this->xmldirectorder->build3Ds2Secure(
                $merchantCode,
                $directOrderParams['orderCode'],
                $directOrderParams['paymentDetails'],
                $directOrderParams['paymentDetails']['dfReferenceId']
            );
        }
        $xmlUsername = $this->worldpayhelper->getXmlUsername($paymentType);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($paymentType);
        if (!empty($directOrderParams['isMultishippingOrder'])) {
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
        }

        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Send token inquiry XML to Worldpay server
     *
     * @param SavedToken $tokenModel
     * @param \Magento\Customer\Model\Customer $customer
     * @param int $storeId
     * @return mixed
     */
    public function tokenInquiry(
        SavedToken $tokenModel,
        \Magento\Customer\Model\Customer $customer,
        $storeId
    ) {
        $this->_wplogger->info('########## Submitting token inquiry. TokenId: ' . $tokenModel->getId() . ' ##########');
        $requestParameters = [
            'tokenModel'   => $tokenModel,
            'customer'     => $customer,
            'merchantCode' => $this->worldpayhelper->getMerchantCode($tokenModel->getMethod()),
        ];
        $this->tokenInquiryXml = new \Sapient\Worldpay\Model\XmlBuilder\TokenInquiry($requestParameters);
        $tokenInquirySimpleXml = $this->tokenInquiryXml->build();

        return $this->_sendRequest(
            dom_import_simplexml($tokenInquirySimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($tokenModel->getMethod()),
            $this->worldpayhelper->getXmlPassword($tokenModel->getMethod())
        );
    }

    /**
     * Get country code
     *
     * @param string $cntrs
     * @param int $cntryId
     * @return mixed
     */
    private function getCountryCodeSpoof($cntrs, $cntryId)
    {
        if ($cntrs) {
            $countryList = explode(',', $cntrs);
            foreach ($countryList as $contry) {
                list($k, $v) = explode('-', $contry);
                if ($k === $cntryId) {
                    return $v;
                }
            }
        }
        return false;
    }

    /**
     * Get credit card specific exception
     *
     * @param string $exceptioncode
     * @return mixed
     */
    public function getCreditCardSpecificException($exceptioncode)
    {
        return $this->worldpayhelper->getCreditCardSpecificexception($exceptioncode);
    }

    /**
     * Void Sale
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $wp
     * @param string $paymentMethodCode
     * @return mixed
     */
    public function voidSale(\Magento\Sales\Model\Order $order, $wp, $paymentMethodCode)
    {
        $orderCode = $wp->getWorldpayOrderId();
        $this->_wplogger->info('########## Submitting void sale request. Order: '
            . $orderCode . ' Amount:' . $order->getGrandTotal() . ' ##########');
        $this->xmlvoidsale = new \Sapient\Worldpay\Model\XmlBuilder\VoidSale();
        $currencyCode = $order->getOrderCurrencyCode();
        $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);

        $xmlUsername = $this->worldpayhelper->getXmlUsername($wp->getPaymentType());
        $xmlPassword = $this->worldpayhelper->getXmlPassword($wp->getPaymentType());
        $merchantCode = $this->worldpayhelper->getMerchantCode($wp->getPaymentType());
        if ($wp->getInteractionType() === 'MOTO') {
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
        }
        if ($wp->getIsMultishippingOrder()) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode();
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername();
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword();

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }
        if ($paymentMethodCode == 'worldpay_paybylink') {
            $pblMerchantCode = $this->worldpayhelper->getPayByLinkMerchantCode();
            $pblMerchantUn = $this->worldpayhelper->getPayByLinkMerchantUsername();
            $pblMerchantPw = $this->worldpayhelper->getPayByLinkMerchantPassword();
            $merchantCode = !empty($pblMerchantCode) ? $pblMerchantCode : $merchantCode;
            $xmlUsername = !empty($pblMerchantUn) ? $pblMerchantUn : $xmlUsername;
            $xmlPassword = !empty($pblMerchantPw) ? $pblMerchantPw : $xmlPassword;
        }

        $voidSaleSimpleXml = $this->xmlvoidsale->build(
            $merchantCode,
            $orderCode,
            $order->getOrderCurrencyCode(),
            $order->getGrandTotal(),
            $exponent,
            $wp->getPaymentType()
        );

        return $this->_sendRequest(
            dom_import_simplexml($voidSaleSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Cancel the order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $wp
     * @param string $paymentMethodCode
     * @return mixed
     */
    public function cancelOrder(\Magento\Sales\Model\Order $order, $wp, $paymentMethodCode)
    {
        $orderCode = $wp->getWorldpayOrderId();
        $this->_wplogger->info('########## Submitting cancel order request. Order: '
            . $orderCode . ' Amount:' . $order->getGrandTotal() . ' ##########');
        $this->xmlcancel = new \Sapient\Worldpay\Model\XmlBuilder\CancelOrder();
        $currencyCode = $order->getOrderCurrencyCode();
        $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);
        $storeId = $order->getStoreId();
        $xmlUsername = $this->worldpayhelper->getXmlUsername($wp->getPaymentType(), $storeId);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($wp->getPaymentType(), $storeId);
        $merchantCode = $this->worldpayhelper->getMerchantCode($wp->getPaymentType(), $storeId);
        if ($wp->getInteractionType() === 'MOTO') {
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
        }
        if ($wp->getIsMultishippingOrder()) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode($storeId);
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername($storeId);
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword($storeId);

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }
        if ($paymentMethodCode == 'worldpay_paybylink') {
            $pblMerchantCode = $this->worldpayhelper->getPayByLinkMerchantCode($storeId);
            $pblMerchantUn = $this->worldpayhelper->getPayByLinkMerchantUsername($storeId);
            $pblMerchantPw = $this->worldpayhelper->getPayByLinkMerchantPassword($storeId);
            $merchantCode = !empty($pblMerchantCode) ? $pblMerchantCode : $merchantCode;
            $xmlUsername = !empty($pblMerchantUn) ? $pblMerchantUn : $xmlUsername;
            $xmlPassword = !empty($pblMerchantPw) ? $pblMerchantPw : $xmlPassword;
        }
        if ($this->worldpayhelper->isEnabledEFTPOS()) {
            $eftposMerchantCode = $this->worldpayhelper->getEFTPOSMerchantCode();
            $eftposMerchantUn = $this->worldpayhelper->getEFTPosXmlUsername();
            $eftposMerchantPw = $this->worldpayhelper->getEFTPOSXmlPassword();

            $xmlUsername = !empty($eftposMerchantUn) ? $eftposMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($eftposMerchantPw) ? $eftposMerchantPw : $xmlPassword ;
            $merchantCode = !empty($eftposMerchantCode) ? $eftposMerchantCode : $merchantCode ;
        }
        $cancelSimpleXml = $this->xmlcancel->build(
            $merchantCode,
            $orderCode,
            $order->getOrderCurrencyCode(),
            $order->getGrandTotal(),
            $exponent,
            'cancel'
        );

        return $this->_sendRequest(
            dom_import_simplexml($cancelSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Approve the order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $wp
     * @param string $paymentMethodCode
     * @return mixed
     */
    public function approveOrder(\Magento\Sales\Model\Order $order, $wp, $paymentMethodCode)
    {
        $orderCode = $wp->getWorldpayOrderId();
        $this->_wplogger->info('########## Submitting approve order request. Order: '
            . $orderCode . ' Amount:' . $order->getGrandTotal() . ' ##########');
        $this->xmlapprove = new \Sapient\Worldpay\Model\XmlBuilder\CancelOrder();
        $currencyCode = $order->getOrderCurrencyCode();
        $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);
        $storeId = $order->getStoreId();
        $xmlUsername = $this->worldpayhelper->getXmlUsername($wp->getPaymentType(), $storeId);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($wp->getPaymentType(), $storeId);
        $merchantCode = $this->worldpayhelper->getMerchantCode($wp->getPaymentType(), $storeId);

        if ($wp->getIsMultishippingOrder()) {
            $msMerchantCode = $this->worldpayhelper->getMultishippingMerchantCode($storeId);
            $msMerchantUn = $this->worldpayhelper->getMultishippingMerchantUsername($storeId);
            $msMerchantPw = $this->worldpayhelper->getMultishippingMerchantPassword($storeId);

            $xmlUsername = !empty($msMerchantUn) ? $msMerchantUn : $xmlUsername ;
            $xmlPassword = !empty($msMerchantPw) ? $msMerchantPw : $xmlPassword ;
            $merchantCode = !empty($msMerchantCode) ? $msMerchantCode : $merchantCode ;
        }

        $cancelSimpleXml = $this->xmlapprove->build(
            $merchantCode,
            $orderCode,
            $order->getOrderCurrencyCode(),
            $order->getGrandTotal(),
            $exponent,
            'approve',
        );

        return $this->_sendRequest(
            dom_import_simplexml($cancelSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Get invoice cart item details
     *
     * @param array $capturedItems
     * @param string $instorePickup
     * @return mixed
     */
    public function getInvoicedItemsDetails($capturedItems, $instorePickup)
    {
        $items = $this->getItemDetails($capturedItems);

        if ($items['is_bundle_item_present'] > 0 ||
            (count($items['invoicedItems']) == 1 &&
                (in_array("downloadable", $items['invoicedItems']['0']) ||
                    in_array("giftcard", $items['invoicedItems']['0'])))) {
            $items['trackingId'] = '';
            return $items;
        } elseif (!empty($instorePickup)) {
            $items['trackingId'] = $instorePickup;
            return $items;
        } else {
            if (array_key_exists('tracking', $capturedItems)
                && count($capturedItems['tracking']) < 2
                && !empty($capturedItems['tracking']['1']['number'])) {
                $items['trackingId'] = $capturedItems['tracking']['1']['number'];
                return $items;
            } else {
                $codeErrorMessage = 'Please create Shippment with single tracking number.';
                $camErrorMessage = $this->exceptionHelper->getConfigValue('AAKL01');
                if (array_key_exists('tracking', $capturedItems) && count($capturedItems['tracking']) > 1) {
                    $codeErrorMessage = 'Multi shipping is currently not available, please add single tracking number.';
                    $camErrorMessage = $this->exceptionHelper->getConfigValue('AAKL02');
                } elseif (array_key_exists('tracking', $capturedItems)
                    && empty($capturedItems['tracking']['1']['number'])) {
                    $codeErrorMessage = 'Tracking number can not be blank, please add.';
                    $camErrorMessage = $this->exceptionHelper->getConfigValue('AAKL03');
                }
                $errorMessage = $camErrorMessage ? $camErrorMessage : $codeErrorMessage;
                throw new \Magento\Framework\Exception\LocalizedException(__($errorMessage));
            }
        }
    }

    /**
     * Get cart item details
     *
     * @param array $capturedItems
     * @return mixed
     */
    public function getItemDetails($capturedItems)
    {
        $items = [];
        $count = 0;
        $bundleCount = 0;
        $filteredItems = array_filter($capturedItems['invoice']['items']);
        foreach ($filteredItems as $key => $val) {
            $items['invoicedItems'][] = $this->worldpayhelper->getInvoicedItemsData($key);
            $items['invoicedItems'][$count]['qty_invoiced'] = $val;
            if ($items['invoicedItems'][$count]['product_type'] == 'bundle') {
                $bundleCount++;
            }
            $count++;
        }
        $items['is_bundle_item_present'] = $bundleCount;
        return $items;
    }
    /**
     * Set Plugin Tracker Details
     *
     * @param string $paymentType
     * @return array
     */
    public function collectPluginTrackerDetails($paymentType)
    {
        $pluginTrackerDetails = $this->worldpayhelper->getPluginTrackerDetails();
        $pluginTrackerDetails['additional_details']['transaction_method'] = $paymentType;

        return json_encode($pluginTrackerDetails);
    }

    public function isSendShopperIpAddress(): bool
    {
        return !($this->worldpayhelper->isEnabledEFTPOS() && $this->worldpayhelper->getEFTPOSDebugging());
    }
}
