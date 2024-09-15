<?php

namespace Sapient\Worldpay\Model\Mapping;

use Sapient\Worldpay\Model\SavedTokenFactory;
use Magento\Framework\Session\SessionManagerInterface;

class Service
{
    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;
    /**
     * @var savedTokenFactory
     */
    protected $savedTokenFactory;
    /**
     * @var _scopeConfig
     */
    protected $_scopeConfig;
    /**
     * @var THIS_TRANSACTION
     */
    public const THIS_TRANSACTION = 'thisTransaction';
    /**
     * @var LESS_THAN_THIRTY_DAYS
     */
    public const LESS_THAN_THIRTY_DAYS = 'lessThanThirtyDays';
    /**
     * @var THIRTY_TO_SIXTY_DAYS
     */
    public const THIRTY_TO_SIXTY_DAYS = 'thirtyToSixtyDays';
    /**
     * @var MORE_THAN_SIXTY_DAYS
     */
    public const MORE_THAN_SIXTY_DAYS = 'moreThanSixtyDays';
    /**
     * @var DURING_TRANSACTION
     */
    public const DURING_TRANSACTION = 'duringTransaction';
    /**
     * @var CREATED_DURING_TRANSACTION
     */
    public const CREATED_DURING_TRANSACTION = 'createdDuringTransaction';
     /**
      * @var CHANGED_DURING_TRANSACTION
      */
    public const CHANGED_DURING_TRANSACTION = 'changedDuringTransaction';
    /**
     * @var NO_ACCOUNT
     */
    public const NO_ACCOUNT = 'noAccount';
     /**
      * @var NO_CHANGE
      */
    public const NO_CHANGE = 'noChange';

    public const KLARNA_V2_METHOD = 'KLARNA_V2-SSL';

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayHelper;

     /**
      * @var \Sapient\Worldpay\Model\SavedToken
      */
    protected $savedtoken;

     /**
      * @var \Magento\Framework\UrlInterface
      */
    protected $_urlBuilder;

     /**
      * @var \Magento\Framework\Session\SessionManagerInterface
      */
    protected $session;

     /**
      * @var \Magento\Customer\Model\Session
      */
    protected $customerSession;

    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    protected $recurringHelper;
    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     * @param SavedTokenFactory $savedTokenFactory
     * @param \Sapient\Worldpay\Model\SavedToken $savedtoken
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     * @param SessionManagerInterface $session
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */

    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        SavedTokenFactory $savedTokenFactory,
        \Sapient\Worldpay\Model\SavedToken $savedtoken,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Customer\Model\Session $customerSession,
        \Sapient\Worldpay\Helper\Recurring $recurringHelper,
        SessionManagerInterface $session,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->wplogger = $wplogger;
        $this->savedTokenFactory = $savedTokenFactory;
        $this->worldpayHelper = $worldpayHelper;
        $this->customerSession = $customerSession;
        $this->recurringHelper = $recurringHelper;
        $this->savedtoken = $savedtoken;
        $this->_urlBuilder = $urlBuilder;
        $this->session = $session;
        $this->_scopeConfig = $scopeConfig;
    }
    /**
     * Collect Vault Order Parameters
     *
     * @param string $orderCode
     * @param int $quote
     * @param string $orderStoreId
     * @param array $paymentDetails
     */

    public function collectVaultOrderParameters(
        $orderCode,
        $quote,
        $orderStoreId,
        $paymentDetails
    ) {
        $reservedOrderId = $quote->getReservedOrderId();
        $currencyCode = $quote->getQuoteCurrencyCode();
        $exponent = $this->worldpayHelper->getCurrencyExponent($currencyCode);
        
        //Level23 data changes
        $billingAddr = $this->_getBillingAddress($quote);
        $orderLineItems = null;
        $vaultPaymentDetails = $this->_getVaultPaymentDetails($paymentDetails);
        if ($this->worldpayHelper->isLevel23Enabled()
           && ($vaultPaymentDetails['brand'] === 'ECMC' || $vaultPaymentDetails['brand'] === 'VISA')
           && ($billingAddr['countryCode'] === 'US' || $billingAddr['countryCode'] === 'CA')) {
            $orderLineItems = $this->_getL23OrderLineItems($quote, $vaultPaymentDetails['brand'].'-SSL');
            $vaultPaymentDetails['salesTax'] = $quote->getShippingAddress()->getData('tax_amount');
        }
        $isMultiShippingOrder = 0;
        if ((bool)$quote->getIsMultiShipping()) {
            $isMultiShippingOrder = 1;
        }
        
        $browserFields = [
            'browser_screenHeight' => isset($paymentDetails['browser_screenheight']) ?
            $paymentDetails['browser_screenheight'] : "",
            'browser_screenWidth' => isset($paymentDetails['browser_screenwidth']) ?
            $paymentDetails['browser_screenwidth'] : "",
            'browser_colorDepth' => isset($paymentDetails['browser_colordepth']) ?
            $paymentDetails['browser_colordepth'] : ""
        ];
        $telephoneNumber = $quote->getBillingAddress()->getTelephone();
        return [
            'orderCode' => $orderCode,
            'merchantCode' => $this->worldpayHelper->getMerchantCode($paymentDetails['cc_type']),
            'orderDescription' => $this->_getOrderDescription($reservedOrderId),
            'currencyCode' => $quote->getQuoteCurrencyCode(),
            'amount' => $quote->getGrandTotal(),
            'paymentDetails' => $vaultPaymentDetails,
            'cardAddress' => $this->_getCardAddress($quote),
            'shopperEmail' => $quote->getCustomerEmail(),
            'threeDSecureConfig' => $this->_getThreeDSecureConfig($paymentDetails['method']),
            'tokenRequestConfig' => 0,
            'acceptHeader' => php_sapi_name() !== "cli" ? filter_input(INPUT_SERVER, 'HTTP_ACCEPT') : '',
            'userAgentHeader' => php_sapi_name() !== "cli" ? filter_input(
                INPUT_SERVER,
                'HTTP_USER_AGENT',
                FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                FILTER_FLAG_STRIP_LOW
            ) : '',
            'shippingAddress' => $this->_getShippingAddress($quote),
            'billingAddress' => $this->_getBillingAddress($quote),
            'cusDetails' => $this->getCustomerDetailsfor3DS2($quote),
            'exemptionEngine' => $this->getExemptionEngineDetails(),
            'method' => $paymentDetails['method'],
            'orderStoreId' => $orderStoreId,
            'shopperId' => $quote->getCustomerId(),
            'exponent' => $exponent,
            'primeRoutingData' => $this->getPrimeRoutingDetails($paymentDetails, $quote),
            'orderLineItems' => $orderLineItems,
            'isMultishippingOrder' => $isMultiShippingOrder,
            'browserFields'=> $browserFields,
            'telephoneNumber' => $telephoneNumber
        ];
    }
    /**
     * Collect Direct Order Parameters
     *
     * @param string $orderCode
     * @param int $quote
     * @param string $orderStoreId
     * @param array $paymentDetails
     * @return array
     */
    public function collectDirectOrderParameters(
        $orderCode,
        $quote,
        $orderStoreId,
        $paymentDetails
    ) {
        
        $reservedOrderId = $quote->getReservedOrderId();
        $savemyCard = isset($paymentDetails['additional_data']['save_my_card'])
                ? $paymentDetails['additional_data']['save_my_card'] : '';
        $tokenizationEnabled = isset($paymentDetails['additional_data']['tokenization_enabled'])
                ? $paymentDetails['additional_data']['tokenization_enabled'] : '';
        $storedCredentialsEnabled = isset($paymentDetails['additional_data']['stored_credentials_enabled'])
                ? $paymentDetails['additional_data']['stored_credentials_enabled'] : '';
        $paymentDetails['additional_data']['disclaimerFlag'] =
                isset($paymentDetails['additional_data']['disclaimerFlag'])
                ? $paymentDetails['additional_data']['disclaimerFlag'] : 0;
        //level23 data
        $billingAddr = $this->_getBillingAddress($quote);
        $orderLineItems = null;
        $paymentType = $paymentDetails['additional_data']['cc_type'];
        if ($this->worldpayHelper->isLevel23Enabled()
           && ($paymentType === 'ECMC-SSL' || $paymentType === 'VISA-SSL')
           && ($billingAddr['countryCode'] === 'US' || $billingAddr['countryCode'] === 'CA')) {
            $orderLineItems = $this->_getL23OrderLineItems($quote, $paymentType);
            $paymentDetails['salesTax'] = $quote->getShippingAddress()->getData('tax_amount');

        }
        
        $thirdPartyData = '';
        $shippingFee = '';
        if ((isset($paymentDetails['additional_data']['cpf']) && $paymentDetails['additional_data']['cpf'] == true)
                || (isset($paymentDetails['additional_data']['instalment'])
                && ($paymentDetails['additional_data']['instalment'] == true))) {
            $thirdPartyData = $this->getThirdPartyDetails($paymentDetails, $quote);
            $shippingFee = $this->getShippingFeeForBrazil($paymentDetails, $quote);
        }
        $currencyCode = $quote->getQuoteCurrencyCode();
        $exponent = $this->worldpayHelper->getCurrencyExponent($currencyCode);
        $isMultiShippingOrder = 0;
        if ((bool)$quote->getIsMultiShipping()) {
            $isMultiShippingOrder = 1;
        }
        $browserFields = [
            'browser_colorDepth' => isset($paymentDetails['additional_data']['browser_colordepth']) ?
                $paymentDetails['additional_data']['browser_colordepth'] : "",
            'browser_screenWidth' => isset($paymentDetails['additional_data']['browser_screenwidth']) ?
                $paymentDetails['additional_data']['browser_screenwidth'] : "",
            'browser_screenHeight' => isset($paymentDetails['additional_data']['browser_screenheight']) ?
                $paymentDetails['additional_data']['browser_screenheight'] : ""
        ];

        $telephoneNumber = $quote->getBillingAddress()->getTelephone();
        return [
                'orderCode' => $orderCode,
                'merchantCode' => $this->worldpayHelper->getMerchantCode($paymentDetails['additional_data']['cc_type']),
                'orderDescription' => $this->_getOrderDescription($reservedOrderId),
                'currencyCode' => $quote->getQuoteCurrencyCode(),
                'amount' => $quote->getGrandTotal(),
                'paymentDetails' => $this->_getPaymentDetails($paymentDetails),
                'cardAddress' => $this->_getCardAddress($quote),
                'shopperEmail' => $quote->getCustomerEmail(),
                'threeDSecureConfig' => $this->_getThreeDSecureConfig($paymentDetails['method']),
                'tokenRequestConfig' => $this->_getTokenRequestConfig($paymentDetails),
                'acceptHeader' => php_sapi_name() !== "cli" ? filter_input(INPUT_SERVER, 'HTTP_ACCEPT') : '',
                'userAgentHeader' => php_sapi_name() !== "cli" ? filter_input(
                    INPUT_SERVER,
                    'HTTP_USER_AGENT',
                    FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                    FILTER_FLAG_STRIP_LOW
                ) : '',
                'shippingAddress' => $this->_getShippingAddress($quote),
                'billingAddress' => $this->_getBillingAddress($quote),
                'method' => $paymentDetails['method'],
                'orderStoreId' => $orderStoreId,
                'shopperId' => $quote->getCustomerId(),
                'saveCardEnabled' => $savemyCard,
                'tokenizationEnabled' => $tokenizationEnabled,
                'storedCredentialsEnabled' => $storedCredentialsEnabled,
                'cusDetails' => $this->getCustomerDetailsfor3DS2($quote),
                'exemptionEngine' => $this->getExemptionEngineDetails(),
                'thirdPartyData' => $thirdPartyData,
                'shippingfee' => $shippingFee,
                'exponent' => $exponent,
                'primeRoutingData' => $this->getPrimeRoutingDetails($paymentDetails, $quote),
                'orderLineItems' => $orderLineItems,
                'isMultishippingOrder' => $isMultiShippingOrder,
                'browserFields'=> $browserFields,
                'telephoneNumber' => $telephoneNumber
            ];
    }
    /**
     * Customer Details for 3DS2
     *
     * @param int $quote
     * @return array
     */

    public function getCustomerDetailsfor3DS2($quote)
    {
        $cusDetails = [];
        $now = new \DateTime();
        $cusDetails['created_at'] = !empty($this->customerSession->getCustomer()->getCreatedAt())
                ? $this->customerSession->getCustomer()->getCreatedAt() : $now->format('Y-m-d H:i:s');
        $cusDetails['updated_at'] = !empty($this->customerSession->getCustomer()->getUpdatedAt())
                ? $this->customerSession->getCustomer()->getUpdatedAt() : $now->format('Y-m-d H:i:s');
        $orderDetails = $this->worldpayHelper->getOrderDetailsByEmailId($quote->getCustomerEmail());
        
        $cusDetails['shipping_amount'] = $quote->getShippingAddress()->getShippingAmount();
        $cusDetails['discount_amount'] = ($quote->getOrigData()['subtotal']) -
            ($quote->getOrigData()['subtotal_with_discount']);
        
        $orderDetails['created_at'] = !empty($orderDetails['created_at'])
                ? $orderDetails['created_at'] : $now->format('Y-m-d H:i:s');
        $orderDetails['updated_at'] = !empty($orderDetails['updated_at'])
                ? $orderDetails['updated_at'] : $now->format('Y-m-d H:i:s');
        $orderDetails['previous_purchase'] = !empty($orderDetails['updated_at'])
                ? 'true' : 'false';
        
        $orderCount = $this->worldpayHelper->getOrdersCountByEmailId($quote->getCustomerEmail());
        if ($quote->getCustomerId()) {
            $savedCardCount = $this->worldpayHelper->getSavedCardsCount($quote->getCustomerId());
        } else {
            $savedCardCount = 0;
        }
        
        $cusDetails['shopperAccountAgeIndicator'] = $this->
                getshopperAccountAgeIndicator($cusDetails['created_at'], $now->format('Y-m-d H:i:s'));
        $cusDetails['shopperAccountChangeIndicator'] = $this->
                getShopperAccountChangeIndicator($cusDetails['updated_at'], $now->format('Y-m-d H:i:s'));
        $cusDetails['shopperAccountPasswordChangeIndicator'] = $this->
                getShopperAccountPasswordChangeIndicator($cusDetails['updated_at'], $now->format('Y-m-d H:i:s'));
        $cusDetails['shopperAccountShippingAddressUsageIndicator'] = $this->
           getShopperAccountShippingAddressUsageIndicator($orderDetails['created_at'], $now->format('Y-m-d H:i:s'));
        $cusDetails['shopperAccountPaymentAccountIndicator'] = $this->
           getShopperAccountPaymentAccountIndicator($orderDetails['created_at'], $now->format('Y-m-d H:i:s'));
        
        $cusDetails['order_details'] = $orderDetails;
        $cusDetails['order_count'] = $orderCount;
        $cusDetails['card_count'] = $savedCardCount;
        $cusDetails['shipping_method'] = $quote->getShippingAddress()->getShippingMethod();
        
        //Fraudsight
        $cusDetails['shopperName'] = $quote->getBillingAddress()->getFirstname();
        $cusDetails['shopperId'] = $quote->getCustomerId();
        $cusDetails['birthDate']= $this ->getCustomerDOB($quote->getCustomer());
        
        return $cusDetails;
    }
    /**
     * Get ExemptionEngineDetails
     */

    public function getExemptionEngineDetails()
    {
        $exemptionEngine = [];
        $exemptionEngine['enabled'] = $this->_scopeConfig->
                getValue(
                    'worldpay/exemption_config/exemption_engine',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
        $exemptionEngine['placement'] = $this->_scopeConfig->
                getValue(
                    'worldpay/exemption_config/exemption_engine_placement',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
        $exemptionEngine['type'] = $this->_scopeConfig->
                getValue(
                    'worldpay/exemption_config/exemption_engine_type',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
        return $exemptionEngine;
    }
    /**
     * Collect Redirect Order Parameters
     *
     * @param string $orderCode
     * @param int $quote
     * @param string $orderStoreId
     * @param array $paymentDetails
     * @return array
     */
    public function collectRedirectOrderParameters(
        $orderCode,
        $quote,
        $orderStoreId,
        $paymentDetails
    ) {
        $updatedPaymentDetails = '';
        $reservedOrderId = $quote->getReservedOrderId();
        if ($paymentDetails['additional_data']['cc_type'] == 'savedcard') {
            $updatedPaymentDetails = $this->_getPaymentDetailsUsingToken($paymentDetails, $quote);
            $paymentType = $updatedPaymentDetails['cardMethod'];
        } else {
            $paymentType = $this->_getRedirectPaymentType($paymentDetails);
            $updatedPaymentDetails = ['token_type' => $this->worldpayHelper->getMerchantTokenization()];
        }
        $savemyCard = isset($paymentDetails['additional_data']['save_my_card'])
        ? $paymentDetails['additional_data']['save_my_card'] : '';
        $storedCredentialsEnabled = isset($paymentDetails['additional_data']['stored_credentials_enabled'])
        ? $paymentDetails['additional_data']['stored_credentials_enabled'] : '';

        //level23 data
        $billingAddr = $this->_getBillingAddress($quote);
        $orderLineItems = null;
        if ($this->worldpayHelper->isLevel23Enabled()
           && ($paymentType === 'ECMC-SSL' || $paymentType === 'VISA-SSL')
           && ($billingAddr['countryCode'] === 'US' || $billingAddr['countryCode'] === 'CA')) {
            $orderLineItems = $this->_getL23OrderLineItems($quote, $paymentType);
            $updatedPaymentDetails['salesTax'] = $quote->getShippingAddress()->getData('tax_amount');
        }

        if (!empty($paymentDetails['is_paybylink_order'])) {
            $orderLineItems = $this->_getOrderLineItems($quote, 'null');
        }
             
        if ($paymentDetails['additional_data']['cc_type'] !== 'savedcard') {
            $updatedPaymentDetails['cardType'] = $paymentType;
        }
                    
        $thirdPartyData = '';
        $shippingFee = '';
        if ((isset($paymentDetails['additional_data']['cpf'])
                && $paymentDetails['additional_data']['cpf'] == true)
                || (isset($paymentDetails['additional_data']['instalment'])
                        && ($paymentDetails['additional_data']['instalment'] == true))) {
            $thirdPartyData = $this->getThirdPartyDetails($paymentDetails, $quote);
            $shippingFee = $this->getShippingFeeForBrazil($paymentDetails, $quote);
        }
        $stmtNarrative = '';
        $apmPaymentTypes = $this->worldpayHelper->getApmTypes('worldpay_apm');
        if (array_key_exists($paymentDetails['additional_data']['cc_type'], $apmPaymentTypes) &&
                (isset($paymentDetails['additional_data']['statementNarrative']))) {
            $stmtNarrative = $paymentDetails['additional_data']['statementNarrative'];
        }
        $currencyCode = $quote->getQuoteCurrencyCode();
        $exponent = $this->worldpayHelper->getCurrencyExponent($currencyCode);
        $isMultiShippingOrder = 0;
        if ((bool)$quote->getIsMultiShipping()) {
            $isMultiShippingOrder = 1;
        }
        return [
                'orderCode' => $orderCode,
                'merchantCode' => $this->worldpayHelper->
                                   getMerchantCode($paymentType),
                'orderDescription' => $this->_getOrderDescription($reservedOrderId),
                'currencyCode' => $quote->getQuoteCurrencyCode(),
                'amount' => $quote->getGrandTotal(),
                'paymentType' => $paymentType,
                'shopperEmail' => $quote->getCustomerEmail(),
                'statementNarrative' => $stmtNarrative,
                'threeDSecureConfig' => $this->_getThreeDSecureConfig(),
                'tokenRequestConfig' => $this->_getTokenRequestConfig($paymentDetails),
                'acceptHeader' => php_sapi_name() !== "cli" ? filter_input(INPUT_SERVER, 'HTTP_ACCEPT') : '',
                'userAgentHeader' => php_sapi_name() !== "cli" ?
                                        filter_input(
                                            INPUT_SERVER,
                                            'HTTP_USER_AGENT',
                                            FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                                            FILTER_FLAG_STRIP_LOW
                                        ) : '',
                'shippingAddress' => $this->_getShippingAddress($quote),
                'billingAddress' => $this->_getBillingAddress($quote),
                'method' => $paymentDetails['method'],
                'paymentPagesEnabled' => $this->worldpayHelper->getCustomPaymentEnabled(),
                'installationId' => $this->worldpayHelper->getInstallationId($paymentType),
                'hideAddress' => $this->worldpayHelper->getHideAddress(),
                'shopperId' => $quote->getCustomerId(),
                'orderStoreId' => $orderStoreId,
                'paymentDetails' => $updatedPaymentDetails,
                'thirdPartyData' => $thirdPartyData,
                'shippingfee' => $shippingFee,
                'exponent' => $exponent,
                'cusDetails' => $this->getCustomerDetailsfor3DS2($quote),
                'orderLineItems' => $orderLineItems,
                'saveCardEnabled' => $savemyCard,
                'storedCredentialsEnabled' => $storedCredentialsEnabled,
                'isMultishippingOrder' => $isMultiShippingOrder
                
            ];
    }
    /**
     * Collect Klarna Order Parameters
     *
     * @param string $orderCode
     * @param int $quote
     * @param string $orderStoreId
     * @param array $paymentDetails
     * @return array
     */
    public function collectKlarnaOrderParameters(
        $orderCode,
        $quote,
        $orderStoreId,
        $paymentDetails
    ) {
        $reservedOrderId = $quote->getReservedOrderId();
        $stmtNarrative = '';
        $orderContent = '';
        
        $apmPaymentTypes = $this->worldpayHelper->getApmTypes('worldpay_apm');
        if ($paymentDetails['additional_data']['cc_type'] === "KLARNA-SSL"
                && (isset($paymentDetails['additional_data']['statementNarrative']))) {
            $stmtNarrative = $paymentDetails['additional_data']['statementNarrative'];
        }
        $currencyCode = $quote->getQuoteCurrencyCode();
        $exponent = $this->worldpayHelper->getCurrencyExponent($currencyCode);
        $countryCode = $quote->getBillingAddress()->getCountryId();
        $isMultiShippingOrder = 0;
        if ((bool)$quote->getIsMultiShipping()) {
            $isMultiShippingOrder = 1;
        }
        return [
            'orderCode' => $orderCode,
            'merchantCode' => $this->worldpayHelper->getMerchantCode($paymentDetails['additional_data']['cc_type']),
            'orderDescription' => $this->_getOrderDescription($reservedOrderId),
            'currencyCode' => $quote->getQuoteCurrencyCode(),
            'amount' => $quote->getGrandTotal(),
            'paymentType' => $this->_getRedirectPaymentType($paymentDetails),
            'shopperEmail' => $quote->getCustomerEmail(),
            'statementNarrative' => $stmtNarrative,
            'threeDSecureConfig' => $this->_getThreeDSecureConfig(),
            'tokenRequestConfig' => $this->_getTokenRequestConfig($paymentDetails),
            'acceptHeader' => php_sapi_name() !== "cli" ? filter_input(INPUT_SERVER, 'HTTP_ACCEPT') : '',
            'userAgentHeader' => php_sapi_name() !== "cli" ? filter_input(
                INPUT_SERVER,
                'HTTP_USER_AGENT',
                FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                FILTER_FLAG_STRIP_LOW
            ) : '',
            'shippingAddress' => $this->_getKlarnaShippingAddress($quote),
            'billingAddress' => $this->_getKlarnaBillingAddress($quote),
            'method' => $paymentDetails['method'],
            'paymentPagesEnabled' => $this->worldpayHelper->getCustomPaymentEnabled(),
            'installationId' => $this->worldpayHelper->getInstallationId($paymentDetails['additional_data']['cc_type']),
            'hideAddress' => $this->worldpayHelper->getHideAddress(),
            'orderLineItems' => $this->_getOrderLineItems($quote, 'KLARNA-SSL'),
            'orderStoreId' => $orderStoreId,
            'shopperId' => $quote->getCustomerId(),
            'exponent' => $exponent,
            'sessionData' => $this->_getSessionDetails($paymentDetails, $countryCode),
            'orderContent' => $orderContent,
            'isMultishippingOrder' => $isMultiShippingOrder
        ];
    }
    /**
     * Collect Token Order Parameters
     *
     * @param string $orderCode
     * @param int $quote
     * @param string $orderStoreId
     * @param array $paymentDetails
     * @return array
     */
    public function collectTokenOrderParameters(
        $orderCode,
        $quote,
        $orderStoreId,
        $paymentDetails
    ) {
        $reservedOrderId = $quote->getReservedOrderId();
        $updatedPaymentDetails = $this->_getPaymentDetailsUsingToken($paymentDetails, $quote);
        
        $id = '';
        if ($this->recurringHelper->quoteContainsSubscription($quote)) {
             $id = isset($updatedPaymentDetails['id'])? $updatedPaymentDetails['id'] : '';
        }
        $savemyCard = isset($paymentDetails['additional_data']['save_my_card'])
                ? $paymentDetails['additional_data']['save_my_card'] : '';
        $tokenizationEnabled = isset($paymentDetails['additional_data']['tokenization_enabled'])
                ? $paymentDetails['additional_data']['tokenization_enabled'] : '';
        $storedCredentialsEnabled = isset($paymentDetails['additional_data']['stored_credentials_enabled'])
                ? $paymentDetails['additional_data']['stored_credentials_enabled'] : '';
        $thirdPartyData = '';
        $shippingFee = '';
        $browserFields = [
            'browser_colorDepth' => isset($paymentDetails['additional_data']['browser_colordepth']) ?
                $paymentDetails['additional_data']['browser_colordepth'] : "",
            'browser_screenWidth' => isset($paymentDetails['additional_data']['browser_screenwidth']) ?
                $paymentDetails['additional_data']['browser_screenwidth'] : "",
            'browser_screenHeight' => isset($paymentDetails['additional_data']['browser_screenheight']) ?
                $paymentDetails['additional_data']['browser_screenheight'] : ""
        ];
        $telephoneNumber = $quote->getBillingAddress()->getTelephone();

        if ((isset($paymentDetails['additional_data']['cpf'])
                && $paymentDetails['additional_data']['cpf'] == true)
                || (isset($paymentDetails['additional_data']['instalment'])
                        && ($paymentDetails['additional_data']['instalment'] == true))) {
            $thirdPartyData = $this->getThirdPartyDetails($paymentDetails, $quote);
            $shippingFee = $this->getShippingFeeForBrazil($paymentDetails, $quote);
        }
        $currencyCode = $quote->getQuoteCurrencyCode();
        $exponent = $this->worldpayHelper->getCurrencyExponent($currencyCode);
        /* Level 23 DATA */
        $paymentType = $updatedPaymentDetails['cardType'];
        $billingAddr = $this->_getBillingAddress($quote);
        $orderLineItems = null;
        if ($this->worldpayHelper->isLevel23Enabled()
           && ($paymentType === 'ECMC-SSL' || $paymentType === 'VISA-SSL')
           && ($billingAddr['countryCode'] === 'US' || $billingAddr['countryCode'] === 'CA')) {
            $orderLineItems = $this->_getL23OrderLineItems($quote, $paymentType);
            $updatedPaymentDetails['salesTax'] = $quote->getShippingAddress()->getData('tax_amount');
        }
        $merchantCode = $this->worldpayHelper->getMerchantCode($paymentType);
        if (isset($updatedPaymentDetails['isRecurringOrder'])) {
            if ($updatedPaymentDetails['isRecurringOrder'] == 1) {
                $merchantCode = $this->worldpayHelper->getRecurringMerchantCode($paymentType);
            }
        }
        $isMultiShippingOrder = 0;
        if ((bool)$quote->getIsMultiShipping()) {
            $isMultiShippingOrder = 1;
        }
        return [
            'orderCode' => $orderCode,
                'merchantCode' => $this->worldpayHelper->getMerchantCode($paymentType),
                'id' => $id,
                'orderDescription' => $this->_getOrderDescription($reservedOrderId),
                'currencyCode' => $quote->getQuoteCurrencyCode(),
                'amount' => $quote->getGrandTotal(),
                'paymentDetails' => $updatedPaymentDetails,
                'cardAddress' => $this->_getCardAddress($quote),
                'shopperEmail' => $quote->getCustomerEmail(),
                'threeDSecureConfig' => $this->_getThreeDSecureConfig($paymentDetails['method']),
                'tokenRequestConfig' => $this->_getTokenRequestConfig($paymentDetails),
                'acceptHeader' => filter_input(INPUT_SERVER, 'HTTP_ACCEPT'),
                'userAgentHeader' => filter_input(
                    INPUT_SERVER,
                    'HTTP_USER_AGENT',
                    FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                    FILTER_FLAG_STRIP_LOW
                ),
                'shippingAddress' => $this->_getShippingAddress($quote),
                'billingAddress' => $this->_getBillingAddress($quote),
                'method' => $paymentDetails['method'],
                'orderStoreId' => $orderStoreId,
                'shopperId' => $quote->getCustomerId(),
                'saveCardEnabled' => $savemyCard,
                'tokenizationEnabled' => $tokenizationEnabled,
                'storedCredentialsEnabled' => $storedCredentialsEnabled,
                'cusDetails' => $this->getCustomerDetailsfor3DS2($quote),
                'exemptionEngine' => $this->getExemptionEngineDetails(),
                'thirdPartyData' => $thirdPartyData,
                'shippingfee' => $shippingFee,
                'exponent' => $exponent,
                'primeRoutingData' => $this->getPrimeRoutingDetails($paymentDetails, $quote),
                'orderLineItems' => $orderLineItems,
                'isMultishippingOrder' => $isMultiShippingOrder,
                'browserFields'=> $browserFields,
                'telephoneNumber' => $telephoneNumber
            ];
    }
    /**
     * Collect ACH Order Parameters
     *
     * @param string $orderCode
     * @param int $quote
     * @param string $orderStoreId
     * @param array $paymentDetails
     * @return array
     */
    public function collectACHOrderParameters(
        $orderCode,
        $quote,
        $orderStoreId,
        $paymentDetails
    ) {
        $reservedOrderId = $quote->getReservedOrderId();
        $stmtNarrative = '';
        $achEmailAddress = '';
        $apmPaymentTypes = $this->worldpayHelper->getApmTypes('worldpay_apm');
        if (array_key_exists($paymentDetails['additional_data']['cc_type'], $apmPaymentTypes)
                && (isset($paymentDetails['additional_data']['statementNarrative']))) {
            $stmtNarrative = $paymentDetails['additional_data']['statementNarrative'];
            $stmtNarrative = strlen($stmtNarrative)>15?substr($stmtNarrative, 0, 15):$stmtNarrative;
        }
       
        if (array_key_exists($paymentDetails['additional_data']['cc_type'], $apmPaymentTypes)
                && (isset($paymentDetails['additional_data']['ach_emailaddress']))) {
            $achEmailAddress = $paymentDetails['additional_data']['ach_emailaddress'];
        }
        $currencyCode = $quote->getQuoteCurrencyCode();
        $exponent = $this->worldpayHelper->getCurrencyExponent($currencyCode);
        
        $isMultiShippingOrder = 0;
        if ((bool)$quote->getIsMultiShipping()) {
            $isMultiShippingOrder = 1;
        }
        return [
            'orderCode' => $orderCode,
            'merchantCode' => $this->worldpayHelper->getMerchantCode($paymentDetails['additional_data']['cc_type']),
            'orderDescription' => $this->_getOrderDescription($reservedOrderId),
            'currencyCode' => $quote->getQuoteCurrencyCode(),
            'amount' => $quote->getGrandTotal(),
            'paymentDetails' => $this->_getPaymentDetails($paymentDetails),
            'shopperEmail' => $achEmailAddress?$achEmailAddress:$quote->getCustomerEmail(),
            'acceptHeader' => php_sapi_name() !== "cli" ? filter_input(INPUT_SERVER, 'HTTP_ACCEPT') : '',
            'userAgentHeader' => php_sapi_name() !== "cli" ? filter_input(
                INPUT_SERVER,
                'HTTP_USER_AGENT',
                FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                FILTER_FLAG_STRIP_LOW
            ) : '',
            'shippingAddress' => $this->_getShippingAddress($quote),
            'billingAddress' => $this->_getBillingAddress($quote),
            'method' => $paymentDetails['method'],
            'orderStoreId' => $orderStoreId,
            'shopperId' => $quote->getCustomerId(),
            'statementNarrative' => $stmtNarrative,
            'exponent' => $exponent,
            'isMultishippingOrder' => $isMultiShippingOrder
        ];
    }
    /**
     * Collect SEPA Order Parameters
     *
     * @param string $orderCode
     * @param int $quote
     * @param string $orderStoreId
     * @param array $paymentDetails
     * @return array
     */
    public function collectSEPAOrderParameters(
        $orderCode,
        $quote,
        $orderStoreId,
        $paymentDetails
    ) {
        $reservedOrderId = $quote->getReservedOrderId();
        $stmtNarrative = '';
        $apmPaymentTypes = $this->worldpayHelper->getApmTypes('worldpay_apm');
        if (array_key_exists($paymentDetails['additional_data']['cc_type'], $apmPaymentTypes)
                && (isset($paymentDetails['additional_data']['statementNarrative']))) {
            $stmtNarrative = $paymentDetails['additional_data']['statementNarrative'];
            $stmtNarrative = strlen($stmtNarrative)>15?substr($stmtNarrative, 0, 15):$stmtNarrative;
        }
        $currencyCode = $quote->getQuoteCurrencyCode();
        $exponent = $this->worldpayHelper->getCurrencyExponent($currencyCode);
        
        $isMultiShippingOrder = 0;
        if ((bool)$quote->getIsMultiShipping()) {
            $isMultiShippingOrder = 1;
        }
        return [
            'orderCode' => $orderCode,
            'merchantCode' => $this->worldpayHelper->getMerchantCode($paymentDetails['additional_data']['cc_type']),
            'orderDescription' => $this->_getOrderDescription($reservedOrderId),
            'currencyCode' => $quote->getQuoteCurrencyCode(),
            'amount' => $quote->getGrandTotal(),
            'paymentDetails' => $this->_getPaymentDetails($paymentDetails),
            'shopperEmail' => $quote->getCustomerEmail(),
            'acceptHeader' => php_sapi_name() !== "cli" ? filter_input(INPUT_SERVER, 'HTTP_ACCEPT') : '',
            'userAgentHeader' => php_sapi_name() !== "cli" ? filter_input(
                INPUT_SERVER,
                'HTTP_USER_AGENT',
                FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                FILTER_FLAG_STRIP_LOW
            ) : '',
            'shippingAddress' => $this->_getShippingAddress($quote),
            'billingAddress' => $this->_getSepaBillingAddress($quote),
            'method' => $paymentDetails['method'],
            'orderStoreId' => $orderStoreId,
            'shopperId' => $quote->getCustomerId(),
            'statementNarrative' => $stmtNarrative,
            'exponent' => $exponent,
            'isMultishippingOrder' => $isMultiShippingOrder
        ];
    }
    /**
     * Collect Payment Options Order Parameters
     *
     * @param Int $countryId
     * @param string $paymenttype
     * @param boolean $ismultishipping
     * @return array
     */
    public function collectPaymentOptionsParameters(
        $countryId,
        $paymenttype,
        $ismultishipping = false
    ) {
        $merchantCode = $this->worldpayHelper->getMerchantCode($paymenttype);
        if ($ismultishipping) {
            $msMC = $this->worldpayHelper->getMultishippingMerchantCode();
            $merchantCode = !empty($msMC) ? $msMC : $merchantCode;
        }
        $isEnabledEftPos = $this->worldpayHelper->isEnabledEFTPOS();
        if ($isEnabledEftPos) {
            $eftPosMC = $this->worldpayHelper->getEFTPOSMerchantCode();
            $merchantCode = !empty($eftPosMC) ? $eftPosMC : $merchantCode;
        }

        return [
            'merchantCode' => $merchantCode,
            'countryCode' => $countryId,
            'paymentType' => $paymenttype
        ];
    }
    /**
     * Collect TokenRequest Config
     *
     * @param array $paymentDetails
     * @return array
     */
    private function _getTokenRequestConfig($paymentDetails)
    {
        if (isset($paymentDetails['additional_data']['save_my_card'])) {
            return $paymentDetails['additional_data']['save_my_card'];
        }
    }
    /**
     * Collect ThreeDSecure Config
     *
     * @param string|bool|null $method
     * @return array
     */
    private function _getThreeDSecureConfig($method = null)
    {
        if ($method == 'worldpay_moto') {
            return [
                'isDynamic3D' => false,
                'is3DSecure' => false
            ];
        } elseif ($method == 'worldpay_cc_vault') {
            return [
                'isDynamic3D' => (bool) $this->worldpayHelper->isDynamic3DEnabled(),
                'is3DSecure' => (bool) $this->worldpayHelper->is3DSecureEnabled()
            ];
        } else {
            return [
                'isDynamic3D' => (bool) $this->worldpayHelper->isDynamic3DEnabled(),
                'is3DSecure' => (bool) $this->worldpayHelper->is3DSecureEnabled()
            ];
        }
    }
    /**
     * Collect ShippingAddress
     *
     * @param int $quote
     * @return array
     */
    private function _getShippingAddress($quote)
    {
        $shippingaddress = $this->_getAddress($quote->getShippingAddress());
        if (!array_filter($shippingaddress)) {
            $shippingaddress = $this->_getAddress($quote->getBillingAddress());
        }
        return $shippingaddress;
    }
    /**
     * Collect Klarna Shipping Address
     *
     * @param int $quote
     * @return array
     */
    private function _getKlarnaShippingAddress($quote)
    {
        $shippingaddress = $this->_getKlarnaAddress($quote->getShippingAddress());
        if (!array_filter($shippingaddress)) {
            $shippingaddress = $this->_getKlarnaAddress($quote->getBillingAddress());
        }
        return $shippingaddress;
    }
    /**
     * Collect Billing Address
     *
     * @param int $quote
     * @return array
     */
    private function _getBillingAddress($quote)
    {
        return $this->_getAddress($quote->getBillingAddress());
    }
    /**
     * Collect Klarna Billing Address
     *
     * @param int $quote
     * @return array
     */
    private function _getKlarnaBillingAddress($quote)
    {
        return $this->_getKlarnaAddress($quote->getBillingAddress());
    }
    /**
     * Collect Sepa Billing Address - This function is used for SEPA alone
     *
     * @param int $quote
     * @return array
     */
    private function _getSepaBillingAddress($quote)
    {
        return $this->_getSepaAddress($quote->getBillingAddress());
    }
    /**
     * Collect Order Line Items
     *
     * @param int $quote
     * @param int|bool|null $paymentType
     * @return array
     */
    private function _getOrderLineItems($quote, $paymentType = null)
    {
        $isMultiShipping = (bool)$quote->getIsMultiShipping();
        $orderitems = [];
        $orderitems['orderTaxAmount'] = $quote->getShippingAddress()->getData('tax_amount');
        if ($paymentType == 'KLARNA-SSL' && $isMultiShipping) {
            $totals = $quote->getTotals();
            $msTax = (isset($totals['tax'])) ? $totals['tax']->getValue(): 0;
            $orderitems['orderTaxAmount'] = $msTax;
        }
        $orderitems['termsURL'] = $this->_urlBuilder->getUrl();
        $lineitem = [];
        $orderItems = $quote->getItemsCollection();
        foreach ($orderItems as $_item) {
            $lineitem = [];
            if ($_item->getParentItem()) {
                continue;
            } else {
                $rowtotal = $_item->getRowTotal();
                $totalamount = $rowtotal - $_item->getDiscountAmount();
                $totaltax = $_item->getTaxAmount() + $_item->getHiddenTaxAmount()
                        + $_item->getWeeeTaxAppliedRowAmount();
                $discountamount = $_item->getDiscountAmount();
                $lineitem['reference'] = $_item->getProductId();
                $lineitem['name'] = $_item->getName();
                $lineitem['productType'] = $_item->getProductType();
                $lineitem['quantity'] = (int) $_item->getQty();
                $lineitem['quantityUnit'] = $this->worldpayHelper->getQuantityUnit($_item->getProduct());
                if ($paymentType == 'KLARNA-SSL' && $isMultiShipping) {
                        $lineitem['unitPrice'] = $_item->getProduct()->getPrice();
                } else {
                        $lineitem['unitPrice'] = $rowtotal / $_item->getQty();
                }
                $lineitem['taxRate'] = $totaltax;
                $lineitem['totalAmount'] = $totalamount + $totaltax;
                $lineitem['totalTaxAmount'] = $totaltax;
                if ($discountamount > 0) {
                    $lineitem['totalDiscountAmount'] = $discountamount;
                }
                if ($orderitems['orderTaxAmount'] == 0) {
                    $orderitems['orderTaxAmount'] = $totaltax;
                }
                $orderitems['lineItem'][] = $lineitem;
            }
        }

        $lineitem = [];
        if ($paymentType == 'KLARNA-SSL' && $isMultiShipping) {
            $shippingAddresses = $quote->getAllShippingAddresses();
            $shippingPrice = 0;
            foreach ($shippingAddresses as $key => $address) {
                $shippingMethodCode = $address->getShippingMethod();
                if ($shippingMethodCode) {
                    $rate = $address->getShippingRateByCode($shippingMethodCode);
                    $shippingPrice += $rate->getPrice();
                } else {
                    $shippingPrice += $order->getShippingAmount();
                }
            }
            $lineitem['reference'] = 'Shipid';
            $lineitem['name'] = 'Shipping amount';
            $lineitem['productType'] = 'shipping';
            $lineitem['quantity'] = 1;
            $lineitem['quantityUnit'] = 'shipping';
            $lineitem['unitPrice'] = $shippingPrice + $msTax;
            $lineitem['totalAmount'] = $shippingPrice + $msTax;
            $lineitem['totalTaxAmount'] = $msTax;

            $lineItemTaxRate = 0;
            if ($msTax > 0) {
                if ($shippingPrice > 0) {
                    $lineItemTaxRate = (int) (($msTax * 100) / $shippingPrice);
                }
            }

            $lineitem['taxRate'] = $lineItemTaxRate;
            $orderitems['lineItem'][] = $lineitem;
        } else {
            $address = $quote->getShippingAddress();
            if ($address->getShippingAmount() > 0) {
                $totalAmount = $address->getShippingAmount() - $address->getShippingDiscountAmount();
                $totaltax = $address->getShippingTaxAmount() + $address->getShippingHiddenTaxAmount();
                $lineitem['reference'] = 'Shipid';
                $lineitem['name'] = 'Shipping amount';
                $lineitem['productType'] = 'shipping';
                $lineitem['quantity'] = 1;
                $lineitem['quantityUnit'] = 'shipping';
                $lineitem['unitPrice'] = $address->getShippingAmount() + $totaltax;
                $lineitem['totalAmount'] = $totalAmount + $totaltax;
                $lineitem['totalTaxAmount'] = $totaltax;
                $lineitem['taxRate'] = (int) (($totaltax * 100) / $address->getShippingAmount());
                if ($address->getShippingDiscountAmount() > 0) {
                    $lineitem['totalDiscountAmount'] = $address->getShippingDiscountAmount();
                }
                $orderitems['lineItem'][] = $lineitem;
            }
        }
        
        if ($quote->getBaseCustomerBalAmountUsed() > 0) {
            $storelineitem = [];
            $storelineitem['reference'] = 0;
            $storelineitem['name'] = 'Store Credit Amount';
            $storelineitem['productType'] = 'Store Credit';
            $storelineitem['quantity'] = 1;
            $storelineitem['quantityUnit'] = 'credit';
            $storelineitem['unitPrice'] = 0 - (double) $quote->getBaseCustomerBalAmountUsed();
            $storelineitem['taxRate'] = 0;
            $storelineitem['totalAmount'] = 0 - (double) $quote->getBaseCustomerBalAmountUsed();
            $storelineitem['totalTaxAmount'] = 0;
            $orderitems['lineItem'][] = $storelineitem;
        }

        $giftCards = [];
        $getGiftCards = $quote->getGiftCards();
        if (!empty($getGiftCards)) {
            $giftCards = json_decode($getGiftCards, true);
        }
        if (!empty($giftCards) && count($giftCards) > 0) {
            $giftcardlineitem = [];
            foreach ($giftCards as $giftCard) {
                $giftcardlineitem['reference'] = $giftCard['i'];
                $giftcardlineitem['name'] = $giftCard['c'];
                $giftcardlineitem['productType'] = 'Gift Card';
                $giftcardlineitem['quantity'] = 1;
                $giftcardlineitem['quantityUnit'] = 'credit';
                $giftcardlineitem['unitPrice'] = 0 - $giftCard['ba'];
                $giftcardlineitem['taxRate'] = 0;
                $giftcardlineitem['totalAmount'] = 0 - $giftCard['ba'];
                $giftcardlineitem['totalTaxAmount'] = 0;
                $orderitems['lineItem'][] = $giftcardlineitem;
            }
        }
        $orderitems['urls'] = $this->getReturnUrls();
        $orderitems['locale_code'] = $this->worldpayHelper->getLocaleDefault();
        return $orderitems;
    }
    /**
     * Collect Address
     *
     * @param array $address
     * @return array
     */
    private function _getAddress($address)
    {
        return [
            'firstName' => $address->getFirstname(),
            'lastName' => $address->getLastname(),
            'street' => $address->getData('street'),
            'postalCode' => $address->getPostcode(),
            'city' => $address->getCity(),
            'countryCode' => $address->getCountryId(),
        ];
    }
    /**
     * Collect Address - This function is used for SEPA alone
     *
     * @param array $address
     * @return array
     */
    private function _getSepaAddress($address)
    {
        return [
            'firstName' => $address->getFirstname(),
            'lastName' => $address->getLastname(),
            'street' => $address->getData('street'),
            'postalCode' => $address->getPostcode(),
            'city' => $address->getCity(),
            'state' => $address->getData('region'),
            'countryCode' => $address->getCountryId(),
            'telephone' => $address->getData('telephone'),
        ];
    }
    /**
     * Collect Klarna Address
     *
     * @param array $address
     * @return array
     */
    private function _getKlarnaAddress($address)
    {
        return [
            'firstName' => $address->getFirstname(),
            'lastName' => $address->getLastname(),
            'address1' => $address->getData('street'),
            'postalCode' => $address->getPostcode(),
            'city' => $address->getCity(),
            'state' => $address->getData('region'),
            'countryCode' => $address->getCountryId(),
            'telephoneNumber' => $address->getData('telephone'),
        ];
    }
    /**
     * Collect Card Address
     *
     * @param int $quote
     * @return array
     */
    private function _getCardAddress($quote)
    {
        return $this->_getAddress($quote->getBillingAddress());
    }
    /**
     * Collect Payment Details
     *
     * @param array $paymentDetails
     * @return array
     */
    private function _getPaymentDetails($paymentDetails)
    {
        $method = $paymentDetails['method'];
        if ($paymentDetails['additional_data']['cc_type'] == "PAYWITHGOOGLE-SSL") {
            return $paymentDetails['additional_data']['cc_type'];
        }

        if ($paymentDetails['additional_data']['cc_type'] == "ACH_DIRECT_DEBIT-SSL") {
            
            $details = [
               'paymentType' => $paymentDetails['additional_data']['cc_type'],
               'achaccount' => $paymentDetails['additional_data']['ach_account'],
               'achAccountNumber' => $paymentDetails['additional_data']['ach_accountNumber'],
               'achRoutingNumber' => $paymentDetails['additional_data']['ach_routingNumber']
            ];
            if (isset($paymentDetails['additional_data']['ach_checknumber'])) {
                $details['achCheckNumber'] = $paymentDetails['additional_data']['ach_checknumber'];
            }
            if (isset($paymentDetails['additional_data']['ach_companyname'])) {
                $details['achCompanyName'] = $paymentDetails['additional_data']['ach_companyname'];
            }
            $details['sessionId'] = $this->session->getSessionId();
            $details['shopperIpAddress'] = $this->_getClientIPAddress();
            return $details;
        }

        if ($paymentDetails['additional_data']['cc_type'] == "SEPA_DIRECT_DEBIT-SSL") {
            $details = [
               'paymentType' => $paymentDetails['additional_data']['cc_type'],
               'sepaMandateType' => $this->worldpayHelper->getSEPADetails(),
               'sepaIban' => $paymentDetails['additional_data']['sepa_iban'],
               'sepaAccountHolderName' => $paymentDetails['additional_data']['sepa_accountHolderName']
            ];
            $details['sepaMerchantNumber'] = $this->worldpayHelper->getSEPAMerchantNo();
            return $details;
        }
        
        if ($paymentDetails['additional_data']['cse_enabled']) {
            $details = [
                'cseEnabled' => $paymentDetails['additional_data']['cse_enabled'],
                'encryptedData' => $paymentDetails['additional_data']['encryptedData'],
                'paymentType' => $paymentDetails['additional_data']['cc_type'],
            ];
        } else {
            $details = [
                'paymentType' => $paymentDetails['additional_data']['cc_type'],
                'cardNumber' => $paymentDetails['additional_data']['cc_number'],
                'expiryMonth' => $paymentDetails['additional_data']['cc_exp_month'],
                'expiryYear' => $paymentDetails['additional_data']['cc_exp_year'],
                'cardHolderName' => $paymentDetails['additional_data']['cc_name'],
                'cseEnabled' => $paymentDetails['additional_data']['cse_enabled'],
            ];

            if (isset($paymentDetails['additional_data']['cc_cid'])) {
                $details['cvc'] = $paymentDetails['additional_data']['cc_cid'];
            }
        }
        $this->customerSession->setIsSavedCardRequested(false);
        if (isset($paymentDetails['additional_data']['save_my_card'])
                && $paymentDetails['additional_data']['save_my_card']) {
            $this->customerSession->setIsSavedCardRequested(true);
        }
        $details['sessionId'] = $this->session->getSessionId();
        $details['shopperIpAddress'] = $this->_getClientIPAddress();
        $details['dynamicInteractionType'] = $this->worldpayHelper->getDynamicIntegrationType($method);
        $details['salesTax'] = '';
        
        if (isset($paymentDetails['salesTax'])) {
            $details['salesTax'] = $paymentDetails['salesTax'];
        }

        // 3DS2 value
        if (isset($paymentDetails['additional_data']['dfReferenceId'])) {
            $details['dfReferenceId'] = $paymentDetails['additional_data']['dfReferenceId'];
        }
        // Check for Merchant Token
        $details['token_type'] = $this->worldpayHelper->getMerchantTokenization();
        //level23 data
        $details['cardType'] = $paymentDetails['additional_data']['cc_type'];
        return $details;
    }
    /**
     * Collect Redirect Payment Type
     *
     * @param array $paymentDetails
     * @return array
     */
    private function _getRedirectPaymentType($paymentDetails)
    {
        if ('CARTEBLEUE-SSL' == $paymentDetails['additional_data']['cc_type']) {
            return 'ECMC-SSL';
        }
        if ($paymentDetails['additional_data']['cc_type'] == 'KLARNA-SSL') {
            return self::KLARNA_V2_METHOD;
        }
        return $paymentDetails['additional_data']['cc_type'];
    }
    /**
     * Collect Orde rDescription
     *
     * @param string $reservedOrderId
     * @return array
     */
    private function _getOrderDescription($reservedOrderId)
    {
        return $this->worldpayHelper->getOrderDescription();
    }
    /**
     * Collect Payment Details Using Token
     *
     * @param array $paymentDetails
     * @param int $quote
     * @return array
     */
    private function _getPaymentDetailsUsingToken($paymentDetails, $quote)
    {
        $savedCardData = $this->savedtoken->loadByTokenCode($paymentDetails['additional_data']['tokenCode']);
        if (isset($paymentDetails['encryptedData'])) {
            $details = [
                'encryptedData' => $paymentDetails['encryptedData'],
                'transactionIdentifier' => $savedCardData->getTransactionIdentifier()
            ];
        } else {
            $details = [
                'brand' => $savedCardData->getCardBrand(),
                'paymentType' => 'TOKEN-SSL',
                'customerId' => $quote->getCustomerId(),
                'tokenCode' => $savedCardData->getTokenCode(),
                'transactionIdentifier' => $savedCardData->getTransactionIdentifier(),
                'cardMethod' => $savedCardData->getMethod()
            ];
            if (isset($paymentDetails['additional_data']['saved_cc_cid'])
                    && !empty($paymentDetails['additional_data']['saved_cc_cid'])) {
                $details['cvc'] = $paymentDetails['additional_data']['saved_cc_cid'];
            }
        }
        $details['sessionId'] = $this->session->getSessionId();
        $details['id'] = $savedCardData->getId();
        $details['shopperIpAddress'] = $this->_getClientIPAddress();
        $details['dynamicInteractionType'] = $this->worldpayHelper->
                getDynamicIntegrationType($paymentDetails['method']);
        // 3DS2 value
        if (isset($paymentDetails['additional_data']['dfReferenceId'])) {
            $details['dfReferenceId'] = $paymentDetails['additional_data']['dfReferenceId'];
        }
        // CVV through HPP
        $details['installationId'] = $this->worldpayHelper->getInstallationId($savedCardData->getCardBrand().'-SSL');
        $details['ccIntegrationMode'] = $this->worldpayHelper->getCcIntegrationMode();
        $details['paymentPagesEnabled'] = $this->worldpayHelper->getCustomPaymentEnabled();
        // Check for Merchant Token
        $details['token_type'] = $this->worldpayHelper->getMerchantTokenization();
        //Level23 data
        $details['cardType'] = $savedCardData->getCardBrand().'-SSL';
        if (isset($paymentDetails['salesTax'])) {
            $details['salesTax'] = $paymentDetails['salesTax'];
        }
        if (isset($paymentDetails['additional_data']['isRecurringOrder'])
           && !empty($paymentDetails['additional_data']['isRecurringOrder'])) {
            $details['isRecurringOrder'] = $paymentDetails['additional_data']['isRecurringOrder'];
        }
        return $details;
    }
    /**
     * Collect Vault Payment Details
     *
     * @param array $paymentDetails
     * @return array
     */
    private function _getVaultPaymentDetails($paymentDetails)
    {
        $details = [
            'brand' => $paymentDetails['card_brand'],
            'paymentType' => 'TOKEN-SSL',
            'customerId' => $paymentDetails['customer_id'],
            'tokenCode' => $paymentDetails['token'],
            'cardType' => $paymentDetails['card_brand'].'-SSL'
        ];
        $details['sessionId'] = $this->session->getSessionId();
        $details['shopperIpAddress'] = $this->_getClientIPAddress();
        $details['dynamicInteractionType'] = $this->worldpayHelper->
                                                getDynamicIntegrationType($paymentDetails['method']);
        // Check for Merchant Token
        $details['token_type'] = $this->worldpayHelper->getMerchantTokenization();
        //3ds changes for vault
        if (isset($paymentDetails['dfReferenceId'])) {
            $details['dfReferenceId'] = $paymentDetails['dfReferenceId'];
        }
        //Level23 data
        if (isset($paymentDetails['salesTax'])) {
            $details['salesTax'] = $paymentDetails['salesTax'];
        }
        return $details;
    }
    /**
     * Collect client IP Address
     *
     * @param string $quote
     * @return array
     */
    private function _getClientIPAddress()
    {
        $REMOTE_ADDR = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
        $remoteAddresses = explode(',', $REMOTE_ADDR);
        return trim($remoteAddresses[0]);
    }
    /**
     * Collect Wallet Order Parameters
     *
     * @param string $orderCode
     * @param int $quote
     * @param string $orderStoreId
     * @param array $paymentDetails
     * @return array
     */
    public function collectWalletOrderParameters(
        $orderCode,
        $quote,
        $orderStoreId,
        $paymentDetails
    ) {
        $reservedOrderId = $quote->getReservedOrderId();
        $currencyCode = $quote->getQuoteCurrencyCode();
        $exponent = $this->worldpayHelper->getCurrencyExponent($currencyCode);
        $isMultiShippingOrder = 0;
        if ((bool)$quote->getIsMultiShipping()) {
            $isMultiShippingOrder = 1;
        }
        //Google Pay
        if ($paymentDetails['additional_data']['cc_type'] == 'PAYWITHGOOGLE-SSL') {
            if ($paymentDetails['additional_data']['walletResponse']) {
                $walletResponse = (array) json_decode($paymentDetails['additional_data']['walletResponse']);
                $paymentMethodData = (array) $walletResponse['paymentMethodData'];
                $tokenizationData = (array) $paymentMethodData['tokenizationData'];
                $token = (array) json_decode($tokenizationData['token']);
                // 3DS2 value
                $sessionId = $this->session->getSessionId();
                $paymentDetails['sessionId'] = $sessionId;
                $dfReferenceId = '';
                $browserfields = [
                    'browserScreenHeight' => $paymentDetails['additional_data']
                        ['browser_screenheight'],
                    'browserScreenWidth' => $paymentDetails['additional_data']
                        ['browser_screenwidth'],
                    'browserColourDepth' => $paymentDetails['additional_data']
                        ['browser_colordepth']
                ];
                $orderDescription = $this->_getOrderDescription($reservedOrderId);
                if (isset($paymentDetails['additional_data']['dfReferenceId'])) {
                    $paymentDetails['dfReferenceId'] = $paymentDetails['additional_data']['dfReferenceId'];
                    $environmentMode = $this->_scopeConfig->
                        getValue(
                            'worldpay/general_config/environment_mode',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        );
                    // if ($environmentMode == 'Test Mode') {
                    //     $orderDescription =   $this->_scopeConfig->getValue(
                    //         'worldpay/wallets_config/google_pay_wallets_config/test_cardholdername',
                    //         \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    //     );
                    // }
                    $this->wplogger->info("Gpay 3Ds value". $orderDescription);

                } else {
                    $orderDescription = $this->_getOrderDescription($reservedOrderId);
                }
                return [
                    'orderCode' => $orderCode,
                    'merchantCode' => $this->worldpayHelper->
                        getMerchantCode($paymentDetails['additional_data']['cc_type']),
                    'orderDescription' => $orderDescription,
                    'currencyCode' => $quote->getQuoteCurrencyCode(),
                    'amount' => $quote->getGrandTotal(),
                    'paymentType' => $this->_getRedirectPaymentType($paymentDetails),
                    'shopperEmail' => $quote->getCustomerEmail(),
                    'acceptHeader' => php_sapi_name() !== "cli" ? filter_input(INPUT_SERVER, 'HTTP_ACCEPT') : '',
                    'userAgentHeader' => php_sapi_name() !== "cli" ? filter_input(
                        INPUT_SERVER,
                        'HTTP_USER_AGENT',
                        FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                        FILTER_FLAG_STRIP_LOW
                    ) : '',
                    'method' => $paymentDetails['method'],
                    'orderStoreId' => $orderStoreId,
                    'protocolVersion' => $token['protocolVersion'],
                    'signature' => $token['signature'],
                    'signedMessage' => $token['signedMessage'],
                    'shippingAddress' => $this->_getShippingAddress($quote),
                    'billingAddress' => $this->_getBillingAddress($quote),
                    'cusDetails' => $this->getCustomerDetailsfor3DS2($quote),
                    'paymentDetails' => $paymentDetails,
                    'threeDSecureConfig' => $this->_getThreeDSecureConfig($paymentDetails['method']),
                    'shopperIpAddress' => $this->_getClientIPAddress(),
                    'exponent' => $exponent,
                    'isMultishippingOrder' => $isMultiShippingOrder,
                    'browserFields'=>$browserfields
                ];
            }
        }

        //Apple Pay
        if ($paymentDetails['additional_data']['cc_type'] == 'APPLEPAY-SSL') {
            if ($paymentDetails['additional_data']['appleResponse']) {
                $appleResponse = (array) json_decode($paymentDetails['additional_data']['appleResponse']);
                $paymentMethodData = (array) $appleResponse['paymentData'];

                $version = $paymentMethodData['version'];

                $data = $paymentMethodData['data'];
                $signature = $paymentMethodData['signature'];

                $headerObject = $paymentMethodData['header'];

                $ephemeralPublicKey = $headerObject->ephemeralPublicKey;
                $publicKeyHash = $headerObject->publicKeyHash;
                $transactionId = $headerObject->transactionId;
                $browserfields = [
                    'browserScreenHeight' => $paymentDetails['additional_data']
                        ['browser_screenheight'],
                    'browserScreenWidth' => $paymentDetails['additional_data']
                        ['browser_screenwidth'],
                    'browserColourDepth' => $paymentDetails['additional_data']
                        ['browser_colordepth'],
                ];
                return [
                    'orderCode' => $orderCode,
                    'merchantCode' => $this->worldpayHelper->
                                        getMerchantCode($paymentDetails['additional_data']['cc_type']),
                    'orderDescription' => $this->_getOrderDescription($reservedOrderId),
                    'currencyCode' => $quote->getQuoteCurrencyCode(),
                    'amount' => $quote->getGrandTotal(),
                    'paymentType' => $this->_getRedirectPaymentType($paymentDetails),
                    'shopperEmail' => $quote->getCustomerEmail(),
                    'method' => $paymentDetails['method'],
                    'orderStoreId' => $orderStoreId,
                    'protocolVersion' => $version,
                    'signature' => $signature,
                    'data' => $data,
                    'ephemeralPublicKey' => $ephemeralPublicKey,
                    'publicKeyHash' => $publicKeyHash,
                    'transactionId' => $transactionId,
                    'exponent' => $exponent,
                    'isMultishippingOrder' => $isMultiShippingOrder,
                    'browserFields'=> $browserfields
                ];
            }
        }
    }
    /**
     * Collect Third Party Details
     *
     * @param array $paymentDetails
     * @param int $quote
     * @return array
     */
    public function getThirdPartyDetails($paymentDetails, $quote)
    {

        //Latin America Payment
        if ($this->belongsToLACountries($paymentDetails, $quote)) {
            if ($paymentDetails['additional_data']['cpf_enabled'] == true) {
                $details = [
                    'cpf' => $paymentDetails['additional_data']['cpf']
                ];
            }
            if (($paymentDetails['additional_data']['instalment_enabled']) == true) {
                $details['instalment'] = $paymentDetails['additional_data']['instalment'];
            }
            if (!empty($paymentDetails['additional_data']['statement'])) {
                $details['statement'] = $paymentDetails['additional_data']['statement'];
            }

            return $details;
        }
    }
    /**
     * Collect Shipping Fee For Brazil
     *
     * @param array $paymentDetails
     * @param int $quote
     * @return array
     */
    public function getShippingFeeForBrazil($paymentDetails, $quote)
    {
        if ($this->belongsToLACountryBr($paymentDetails, $quote)) {
            if ($paymentDetails['method'] == 'worldpay_moto') {
                $details = [
                    'shippingfee' => $quote->getShippingAddress()->getData('shipping_amount')
                ];
            } else {
                $details = [
                    'shippingfee' => $paymentDetails['additional_data']['shippingfee']
                ];
            }
            return $details;
        }
    }
    /**
     * Collect belongs To LACountryBr
     *
     * @param array $paymentDetails
     * @param int $quote
     * @return array
     */
    public function belongsToLACountryBr($paymentDetails, $quote)
    {
        $billingAdress = $this->_getBillingAddress($quote);
        $isCpf = $paymentDetails['additional_data']['cpf_enabled'];
        if ($isCpf && $billingAdress['countryCode'] == 'BR') {
            return true;
        }
        return false;
    }
    /**
     * Collect belongs To LACountries
     *
     * @param array $paymentDetails
     * @param int $quote
     * @return array
     */
    public function belongsToLACountries($paymentDetails, $quote)
    {
        $billingAdress = $this->_getBillingAddress($quote);
        $ccountryCd = $billingAdress['countryCode'];
        $instalmentEnabled = $paymentDetails['additional_data']['instalment_enabled'];
        $isCpf = $paymentDetails['additional_data']['cpf_enabled'];
        if ($instalmentEnabled) {
            $instalment = $this->worldpayHelper->getInstalmentValues($ccountryCd);
            $lacountries = ['AR', 'BZ', 'BR', 'CL', 'CO', 'CR', 'SV', 'GT', 'HN', 'MX', 'NI', 'PA', 'PE'];
            if ((in_array($billingAdress['countryCode'], $lacountries)) && $instalment !== null) {
                return true;
            }
        } elseif ($isCpf && $billingAdress['countryCode'] == 'BR') {
            return true;
        }
        return false;
    }
    /**
     * Collect Shopper Account Age Indicator
     *
     * @param string $fromDate
     * @param string $toDate
     * @param srting $differenceFormat
     * @return string
     */
    public function getShopperAccountAgeIndicator($fromDate, $toDate, $differenceFormat = '%a')
    {
        $datetime1 = date_create($fromDate);
        $datetime2 = date_create($toDate);
        $interval = date_diff($datetime1, $datetime2);
        $days = $interval->format($differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            $indicator = !empty($this->customerSession->getCustomer()->getId())
                    ? self::CREATED_DURING_TRANSACTION : self::NO_ACCOUNT;
            return $indicator;
        }
    }
    /**
     * Collect Shopper Account Change Indicator
     *
     * @param string $fromDate
     * @param string $toDate
     * @param srting $differenceFormat
     * @return string
     */
    public function getShopperAccountChangeIndicator($fromDate, $toDate, $differenceFormat = '%a')
    {
        $datetime1 = date_create($fromDate);
        $datetime2 = date_create($toDate);
        $interval = date_diff($datetime1, $datetime2);
        $days = $interval->format($differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            return self::CHANGED_DURING_TRANSACTION;
        }
    }
    /**
     * Collect Shopper Account Password Change Indicator
     *
     * @param string $fromDate
     * @param string $toDate
     * @param srting $differenceFormat
     * @return string
     */
    public function getShopperAccountPasswordChangeIndicator($fromDate, $toDate, $differenceFormat = '%a')
    {
        $datetime1 = date_create($fromDate);
        $datetime2 = date_create($toDate);
        $interval = date_diff($datetime1, $datetime2);
        $days = $interval->format($differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            $indicator = !empty($this->customerSession->getCustomer()->getId())
                            ? self::CHANGED_DURING_TRANSACTION : self::NO_CHANGE;
            return $indicator;
        }
    }
    /**
     * Collect Shopper Account Shipping Address Usage Indicator
     *
     * @param string $fromDate
     * @param string $toDate
     * @param srting $differenceFormat
     * @return string
     */
    public function getShopperAccountShippingAddressUsageIndicator(
        $fromDate,
        $toDate,
        $differenceFormat = '%a'
    ) {
        $datetime1 = date_create($fromDate);
        $datetime2 = date_create($toDate);
        $interval = date_diff($datetime1, $datetime2);
        $days = $interval->format($differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            return self::THIS_TRANSACTION;
        }
    }
    /**
     * Collect Shopper Account Payment AccountIndicator
     *
     * @param string $fromDate
     * @param string $toDate
     * @param srting $differenceFormat
     * @return string
     */
    public function getShopperAccountPaymentAccountIndicator($fromDate, $toDate, $differenceFormat = '%a')
    {
        $datetime1 = date_create($fromDate);
        $datetime2 = date_create($toDate);
        $interval = date_diff($datetime1, $datetime2);
        $days = $interval->format($differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            $indicator = !empty($this->customerSession->getCustomer()->getId())
                                ? self::DURING_TRANSACTION : self::NO_ACCOUNT;
            return $indicator;
        }
    }
    /**
     * Collect Prime Routing Details
     *
     * @param array $paymentDetails
     * @param int $quote
     * @return array
     */
    public function getPrimeRoutingDetails($paymentDetails, $quote)
    {
        $billingAdress = $this->_getBillingAddress($quote);
        if ($billingAdress['countryCode']==='US') {
            if ($this->worldpayHelper->isMultiShipping($quote)) {
                return;
            }
            if ($this->worldpayHelper->isPrimeRoutingEnabled()
                && $this->worldpayHelper->isAdvancedPrimeRoutingEnabled()) {
                $details['primerouting'] = $this->worldpayHelper->isPrimeRoutingEnabled();
                $details['advanced_primerouting'] = $this->worldpayHelper->isAdvancedPrimeRoutingEnabled();
                $details['routing_preference'] = $this->worldpayHelper->getRoutingPreference();
                $details['debit_networks'] = $this->worldpayHelper->getDebitNetworks();
            
                return $details;
            } elseif ($this->worldpayHelper->isPrimeRoutingEnabled()) {
                $details['primerouting'] = $this->worldpayHelper->isPrimeRoutingEnabled();
            
                return $details;
            }
        }
    }
    
    /**
     * Get Return Urls to return from the payment page
     *
     * @return array
     */
    public function getReturnUrls()
    {
        $urls = [];
        $urls['successURL'] = $this->_urlBuilder->getUrl('worldpay/redirectresult/success');
        $urls['pendingURL'] = $this->_urlBuilder->getUrl('worldpay/redirectresult/pending');
        $urls['cancelURL'] = $this->_urlBuilder->getUrl('worldpay/redirectresult/cancel');
        $urls['failureURL'] = $this->_urlBuilder->getUrl('worldpay/redirectresult/failure');
        return $urls;
    }
    /**
     * Collect Session Details
     *
     * @param array $paymentDetails
     * @param string $countryCode
     * @return array
     */
    private function _getSessionDetails($paymentDetails, $countryCode)
    {
        $sessionDetails = [];
        if ($paymentDetails['additional_data']['cc_type'] == 'KLARNA-SSL') {
            $sessionDetails['sessionId'] = $this->session->getSessionId();
            $sessionDetails['shopperIpAddress'] = $this->_getClientIPAddress();
            $sessionDetails['subscriptionDays'] = $this->worldpayHelper->getKlarnaSubscriptionDays($countryCode);
            return $sessionDetails;
        }
        return $sessionDetails;
    }
    /**
     * Collect Third Party Details
     *
     * @param string $customer
     * @return array
     */
    public function getCustomerDOB($customer)
    {
        $now = new \DateTime();
        $dob = $customer->getDob();
        if (isset($dob)) {
            $dob = date('Y-m-d', strtotime($dob));
            return $dob;
        }
    }
     /**
      * Collect Third Party Details
      *
      * @param int $quote
      * @param int|bool|null $paymentType
      * @return array
      */
    private function _getL23OrderLineItems($quote, $paymentType = null)
    {
        $orderitems = [];
        $lineitem = [];
        $orderItems = $quote->getAllItems();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        
        foreach ($orderItems as $_item) {
            $lineitem = [];
            $_product ='';
            
            if ($_item->getParentItem()) {
                continue;
            }

            $_product = $objectManager->create(\Magento\Catalog\Model\Product::class)->load($_item->getProductId());

                $rowtotal = $_item->getRowTotal();
                $totalamount = $rowtotal - $_item->getDiscountAmount();
                $totaltax = $_item->getTaxAmount() + $_item->getHiddenTaxAmount()
                        + $_item->getWeeeTaxAppliedRowAmount();
                $discountamount = $_item->getDiscountAmount();
                
                $unitOfMeasure = $_product->getData('unit_of_measure');
            if ($unitOfMeasure == '') {
                $unitOfMeasure = $this->worldpayHelper->getUnitOfMeasure();
            }
                $lineitem['description'] = substr($_item->getName(), 0, 12);
                $lineitem['productCode'] = substr($_item->getProductId(), 0, 12);
                $commodityCode = $_product->getData('commodity_code');
            if (isset($commodityCode)) {
                $lineitem['commodityCode'] = substr($commodityCode, 0, 12);
            } else {
                $lineitem['commodityCode'] = $commodityCode;
            }
                $lineitem['quantity'] = (int) substr($_item->getQty(), 0, 12);
                $lineitem['unitCost'] = $rowtotal / $_item->getQty();
                $lineitem['unitOfMeasure'] = substr($unitOfMeasure, 0, 12);
                $lineitem['itemTotal'] = $totalamount;
                $lineitem['itemTotalWithTax'] = $totalamount + $totaltax;
                $lineitem['itemDiscountAmount'] = $discountamount;
                $lineitem['taxAmount'] = $totaltax;
                $orderitems['lineItem'][] = $lineitem;
                
        }
        
        return $orderitems;
    }
}
