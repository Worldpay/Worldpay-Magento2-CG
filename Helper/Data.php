<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\Worldpay\Helper;

use Sapient\Worldpay\Model\Config\Source\HppIntegration as HPPI;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * MinSaleQty value manipulation helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;
    
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var _storeManager
     */
    protected $_storeManager;
    /**
     * @var _filesystem
     */
    protected $_filesystem;
    /**
     * @var MERCHANT_CONFIG
     */
    public const MERCHANT_CONFIG = 'worldpay/merchant_config/';
     /**
      * @var INTEGRATION_MODE
      */
    public const INTEGRATION_MODE = 'worldpay/cc_config/integration_mode';

    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentlist
     * @param \Sapient\Worldpay\Helper\Merchantprofile $merchantprofile
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     * @param \Sapient\Worldpay\Helper\ExtendedResponseCodes $extendedResponseCodes
     * @param \Sapient\Worldpay\Helper\Instalmentconfig $instalmentconfig
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Sapient\Worldpay\Model\SavedTokenFactory $savecard
     * @param \Sapient\Worldpay\Helper\Currencyexponents $currencyexponents
     * @param SerializerInterface $serializer
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentlist,
        \Sapient\Worldpay\Helper\Merchantprofile $merchantprofile,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Sapient\Worldpay\Helper\Recurring $recurringHelper,
        \Sapient\Worldpay\Helper\ExtendedResponseCodes $extendedResponseCodes,
        \Sapient\Worldpay\Helper\Instalmentconfig $instalmentconfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Sapient\Worldpay\Model\SavedTokenFactory $savecard,
        \Sapient\Worldpay\Helper\Currencyexponents $currencyexponents,
        SerializerInterface $serializer
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_filesystem = $filesystem;
        $this->wplogger = $wplogger;
        $this->paymentlist = $paymentlist;
        $this->localecurrency = $localeCurrency;
        $this->merchantprofile = $merchantprofile;
        $this->instalmentconfig = $instalmentconfig;
        $this->_checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->recurringHelper = $recurringHelper;
        $this->extendedResponseCodes = $extendedResponseCodes;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->_savecard = $savecard;
        $this->currencyexponents = $currencyexponents;
        $this->serializer = $serializer;
    }

    /**
     * Check if worldPay enable or not
     *
     * @return bool
     */
    public function isWorldPayEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/general_config/enable_worldpay',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get  environment mode
     *
     * @return array|string
     */
    public function getEnvironmentMode()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/environment_mode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get  test url
     *
     * @return array|string
     */
    public function getTestUrl()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/test_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     * IsIAVEnabled
     *
     * @return bool
     */
    public function isIAVEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/cc_config/enable_iav',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get  live url
     *
     * @return array|string
     */
    public function getLiveUrl()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/live_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get  merchant code based on payment type
     *
     * @param int|string $paymentType
     * @return array|string
     */
    public function getMerchantCode($paymentType)
    {
        if ($paymentType) {
            $merchat_detail = $this->merchantprofile->getConfigValue($paymentType);
            $merchantCodeValue = $merchat_detail?$merchat_detail['merchant_code']: '';
            if (!empty($merchantCodeValue)) {
                return $merchantCodeValue;
            }
        }
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/merchant_code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get  xml username based on payment type
     *
     * @param int|string $paymentType
     * @return array|string
     */
    public function getXmlUsername($paymentType)
    {
        if ($paymentType) {
            $merchat_detail = $this->merchantprofile->getConfigValue($paymentType);
            $merchantCodeValue = $merchat_detail?$merchat_detail['merchant_username']:'';
            if (!empty($merchantCodeValue)) {
                return $merchantCodeValue;
            }
        }
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/xml_username',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get  xml password based on payment type
     *
     * @param int|string $paymentType
     * @return array|string
     */
    public function getXmlPassword($paymentType)
    {
        if ($paymentType) {
            $merchat_detail = $this->merchantprofile->getConfigValue($paymentType);
            $merchantCodeValue = $merchat_detail?$merchat_detail['merchant_password']:'';
            if (!empty($merchantCodeValue)) {
                return $merchantCodeValue;
            }
        }
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/xml_password',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get  GetMyAccountSpecificexception
     *
     * @param int|string $exceptioncode
     * @return array|string
     */
    public function getMyAccountSpecificexception($exceptioncode)
    {

        $ccdata=$this->serializer->unserialize($this->getMyAccountException());
        if (is_array($ccdata) || is_object($ccdata)) {
            foreach ($ccdata as $key => $valuepair) {
                if ($key == $exceptioncode) {
                    return $valuepair['exception_module_messages']?$valuepair['exception_module_messages']:
                        $valuepair['exception_messages'];
                }
            }
        }
    }

    /**
     * Check if mac enable
     *
     * @return bool|string
     */
    public function isMacEnabled()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/mac_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get  mac secret
     *
     * @return bool|string
     */
    public function getMacSecret()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/mac_secret',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if dynamic3DEnabled
     *
     * @return bool
     */
    public function isDynamic3DEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/3ds_config/enable_dynamic3DS',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check is3DSecureEnabled
     *
     * @return bool
     */
    public function is3DSecureEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/3ds_config/do_3Dsecure',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check is3dsEnabled
     *
     * @return bool
     */
    public function is3dsEnabled()
    {
        return $this->isDynamic3DEnabled() || $this->is3DSecureEnabled();
    }

    /**
     * Check isLoggerEnable
     *
     * @return bool
     */
    public function isLoggerEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/general_config/enable_logging',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check isMotoEnabled
     *
     * @return bool
     */
    public function isMotoEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/moto_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check isCreditCardEnabled
     *
     * @return bool
     */
    public function isCreditCardEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/cc_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get  ccTitle
     *
     * @return string
     */
    public function getCcTitle()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/cc_config/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get CcTypes
     *
     * @param string $paymentconfig
     * @return string
     */
    public function getCcTypes($paymentconfig = "cc_config")
    {
        $allCcMethods = [
            'AMEX-SSL' => 'American Express', 'VISA-SSL' => 'Visa',
            'ECMC-SSL' => 'MasterCard', 'DISCOVER-SSL' => 'Discover',
            'DINERS-SSL' => 'Diners', 'MAESTRO-SSL' => 'Maestro', 'AIRPLUS-SSL' => 'AirPlus',
            'AURORE-SSL' => 'Aurore', 'CB-SSL' => 'Carte Bancaire',
            'CARTEBLEUE-SSL' => 'Carte Bleue', 'DANKORT-SSL' => 'Dankort',
            'GECAPITAL-SSL' => 'GE Capital', 'JCB-SSL' => 'Japanese Credit Bank',
            'LASER-SSL' => 'Laser Card', 'UATP-SSL' => 'UATP',
        ];
        $configMethods = explode(',', $this->_scopeConfig->getValue('worldpay/' .
                $paymentconfig . '/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        $activeMethods = [];
        foreach ($configMethods as $method) {
            $activeMethods[$method] = $allCcMethods[$method];
        }
        return $activeMethods;
    }

    /**
     * Get  apmTypes
     *
     * @param int|string $code
     * @return string|array
     */
    public function getApmTypes($code)
    {
        $allApmMethods = [
            'CHINAUNIONPAY-SSL' => 'Union Pay',
            'IDEAL-SSL' => 'IDEAL',
            'QIWI-SSL' => 'Qiwi',
           // 'YANDEXMONEY-SSL' => 'Yandex.Money',
            'PAYPAL-EXPRESS' => 'PayPal',
            'SOFORT-SSL' => 'SoFort EU',
            'GIROPAY-SSL' => 'GiroPay',
          //  'BOLETO-SSL' => 'Boleto Bancairo',
            'ALIPAY-SSL' => 'AliPay',
            'SEPA_DIRECT_DEBIT-SSL' => 'SEPA (One off transactions)',
            'KLARNA-SSL' => 'Klarna',
            'PRZELEWY-SSL' => 'P24',
            'MISTERCASH-SSL' => 'Mistercash/Bancontact',
            'ACH_DIRECT_DEBIT-SSL' => 'ACH Pay'
        ];
        $configMethods = explode(',', $this->_scopeConfig->getValue(
            'worldpay/apm_config/paymentmethods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
        $activeMethods = [];
        foreach ($configMethods as $method) {
            if ($this->paymentlist->checkCurrency($code, $method)) {
                $activeMethods[$method] = $allApmMethods[$method];
            }
        }
        return $activeMethods;
    }

    /**
     * Get  walletsTypes
     *
     * @param int|string $code
     * @return string
     */
    public function getWalletsTypes($code)
    {
        $activeMethods = [];
        if ($this->isGooglePayEnable()) {
            $activeMethods['PAYWITHGOOGLE-SSL'] = 'Google Pay';
        }
        if ($this->isSamsungPayEnable()) {
            $activeMethods['SAMSUNGPAY-SSL'] = 'Samsung Pay';
        }
        if ($this->isApplePayEnable()) {
            $activeMethods['APPLEPAY-SSL'] = 'Apple Pay';
        }
        return $activeMethods;
    }

    /**
     * Get CsePublicKey
     *
     * @return string|array
     */
    public function getCsePublicKey()
    {
        return trim($this->_scopeConfig->getValue(
            'worldpay/cc_config/cse_public_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * Is cse enabled
     *
     * @return bool
     */
    public function isCseEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/cc_config/cse_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Is ccRequireCVC
     *
     * @return string|array
     */
    public function isCcRequireCVC()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/cc_config/require_cvc',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get CcIntegrationMode
     *
     * @return string|array
     */
    public function getCcIntegrationMode()
    {
        return $this->_scopeConfig->getValue(
            self::INTEGRATION_MODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get SaveCard
     *
     * @return string|array
     */
    public function getSaveCard()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/saved_card',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Tokenization
     *
     * @return string|array
     */
    public function getTokenization()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/save_tokenization',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get StoredCredentials
     *
     * @return string|array
     */
    public function getStoredCredentials()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/save_stored_credentials',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * IsApmEnabled
     *
     * @return string|array
     */
    public function isApmEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/apm_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get ApmTitle
     *
     * @return string|array
     */
    public function getApmTitle()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/apm_config/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get ApmPaymentMethods
     *
     * @return string|array
     */
    public function getApmPaymentMethods()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/apm_config/paymentmethods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get PaymentMethodSelection
     *
     * @return string|array
     */
    public function getPaymentMethodSelection()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/payment_method_selection',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Is AutoCaptureEnabled
     *
     * @param int|string $storeId
     * @return string|array
     */
    public function isAutoCaptureEnabled($storeId)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/capture_automatically',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get IntegrationModelByPaymentMethodCode
     *
     * @param int|string $paymentMethodCode
     * @param int|string $storeId
     * @return string|array
     */
    public function getIntegrationModelByPaymentMethodCode($paymentMethodCode, $storeId)
    {
        if ($paymentMethodCode == 'worldpay_cc' || $paymentMethodCode == 'worldpay_moto'
                || $paymentMethodCode == 'worldpay_cc_vault' ||
                $paymentMethodCode == 'worldpay_wallets') {
            return $this->_scopeConfig->getValue(
                self::INTEGRATION_MODE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        } else {
            return 'redirect';
        }
    }

    /**
     * IsIframeIntegration
     *
     * @param int|string $storeId
     * @return string|array
     */
    public function isIframeIntegration($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/hpp_integration',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) == HPPI::OPTION_VALUE_IFRAME;
    }

    /**
     * Get RedirectIntegrationMode
     *
     * @param int|string $storeId
     * @return string|array
     */
    public function getRedirectIntegrationMode($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/hpp_integration',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get CustomPaymentEnabled
     *
     * @param int|string $storeId
     * @return string|array
     */
    public function getCustomPaymentEnabled($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get GetInstallationId
     *
     * @param int|string $storeId
     * @return string|array
     */
    public function getInstallationId($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/installation_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get HideAddress
     *
     * @param int|string $storeId
     * @return string|array
     */
    public function getHideAddress($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/hideaddress',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get OrderSyncInterval
     *
     * @return string|array
     */
    public function getOrderSyncInterval()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/order_sync_status/order_sync_interval',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get SyncOrderStatus
     *
     * @return string|array
     */
    public function getSyncOrderStatus()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/order_sync_status/order_status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get DynamicIntegrationType
     *
     * @param int|string $paymentMethodCode
     * @return string|array
     */
    public function getDynamicIntegrationType($paymentMethodCode)
    {
        switch ($paymentMethodCode) {
            case 'worldpay_moto':
                return 'MOTO';
            default:
                return 'ECOMMERCE';
        }
    }

    /**
     * UpdateErrorMessage
     *
     * @param string $message
     * @param int $orderid
     * @return string|array
     */
    public function updateErrorMessage($message, $orderid)
    {
        $updatemessage = [
                'Payment REFUSED' => sprintf($this->getCreditCardSpecificexception('CCAM17'), $orderid),
                'Gateway error' => $this->getCreditCardSpecificexception('CCAM18'),
                'Token does not exist' => $this->getCreditCardSpecificexception('CCAM19')
        ];
        if (is_numeric($message)) {
            $responseMessage = $this->extendedResponseCodes->getConfigValue($message);
            if (!empty($responseMessage)) {
                $responseMessage = sprintf('Order %s has been declined, '
                        . 'Gateway Error: '.$responseMessage, $orderid);
                return $responseMessage;
            }
        }
        if (array_key_exists($message, $updatemessage)) {
            return $updatemessage[$message];
        }

        if (empty($message)) {

            $message = $this->getCreditCardSpecificexception('CCAM11');
        }
        if (strpos($message, "prime routing")!==false) {
            $cammessage = $this->getCreditCardSpecificException('CPR01');
            $message = $cammessage?$cammessage:$message;
        }
        return $message;
    }

    /**
     * Get  getTimeLimitOfAbandonedOrders
     *
     * @param int $paymentMethodCode
     * @return string|array
     */
    public function getTimeLimitOfAbandonedOrders($paymentMethodCode)
    {
        $path = sprintf(
            'worldpay/order_cleanup/%s_payment_method',
            str_replace("-", "_", $paymentMethodCode)
        );
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get  DefaultCountry
     *
     * @param int $storeId
     * @return string
     */
    public function getDefaultCountry($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'shipping/origin/country_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get LocaleDefault
     *
     * @param int  $storeId
     */
    public function getLocaleDefault($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'general/locale/code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get CurrencySymbol
     *
     * @param int  $currencycode
     */
    public function getCurrencySymbol($currencycode)
    {
        return $this->localecurrency->getCurrency($currencycode)->getSymbol();
    }

    /**
     * Get QuantityUnit
     *
     * @param int|string  $product
     */
    public function getQuantityUnit($product)
    {
        return 'product';
    }

    /**
     * CheckStopAutoInvoice
     *
     * @param int $code
     * @param string $type
     */
    public function checkStopAutoInvoice($code, $type)
    {
        return $this->paymentlist->checkStopAutoInvoice($code, $type);
    }

    /**
     * InstantPurchaseEnabled
     *
     * Get  Blank
     */
    public function instantPurchaseEnabled()
    {
        $instantPurchaseEnabled = false;
        $caseSensitiveVal = trim($this->getCcIntegrationMode());
        $caseSensVal  = strtoupper($caseSensitiveVal);
        $isSavedCardEnabled = $this->getSaveCard();
        if ($caseSensVal === 'DIRECT' && $isSavedCardEnabled) {
            $instantPurchaseEnabled = (bool) $this->_scopeConfig->
                getValue(
                    'worldpay/quick_checkout_config/instant_purchase',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
        }
        return $instantPurchaseEnabled;
    }

    /**
     * Get WorldpayAuthCookie
     *
     * Get  Blank
     */
    public function getWorldpayAuthCookie()
    {
        return $this->_checkoutSession->getWorldpayAuthCookie();
    }

    /**
     * SetWorldpayAuthCookie
     *
     * @param int|float|string|array $value
     * @return bool
     */
    public function setWorldpayAuthCookie($value)
    {
        return $this->_checkoutSession->setWorldpayAuthCookie($value);
    }

   /**
    * Is ThreeDSRequest
    *
    * @return bool
    */
    public function isThreeDSRequest()
    {
        return $this->_checkoutSession->getIs3DSRequest();
    }

    /**
     * Get OrderDescription
     *
     * @param int|float|string|array $value
     * @return array|string
     */
    public function getOrderDescription()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/order_description',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get OrderDescription
     *
     * @return array|string
     */
    public function getMotoTitle()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/moto_config/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get PaymentTitleForOrders
     *
     * @param int|float|string|array $order
     * @param int|string $paymentCode
     * @param \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment
     * @return array|string
     */
    public function getPaymentTitleForOrders(
        $order,
        $paymentCode,
        \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment
    ) {
        $order_id = $order->getIncrementId();
        $wpp = $worldpaypayment->create();
        $item = $wpp->loadByPaymentId($order_id);
        if ($paymentCode == 'worldpay_cc' || $paymentCode == 'worldpay_cc_vault') {
            return $this->getCcTitle() . "\n" . $item->getPaymentType();
        } elseif ($paymentCode == 'worldpay_apm') {
            return $this->getApmTitle() . "\n" . $item->getPaymentType();
        } elseif ($paymentCode == 'worldpay_wallets') {
            return $this->getWalletsTitle() . "\n" . $item->getPaymentType();
        } elseif ($paymentCode == 'worldpay_moto') {
            return $this->getMotoTitle() . "\n" . $item->getPaymentType();
        }
    }

    /**
     * Get OrderByOrderId
     *
     * @param int $orderId
     * @return array|string
     */
    public function getOrderByOrderId($orderId)
    {
        return $this->orderFactory->create()->load($orderId);
    }

    /**
     * IsWalletsEnabled
     *
     * @return bool
     */
    public function isWalletsEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get WalletsTitle
     *
     * @return string
     */
    public function getWalletsTitle()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     * Get SamsungServiceId
     *
     * @return string|int
     */
    public function getSamsungServiceId()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/samsung_pay_wallets_config/service_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if google pay enable or not
     *
     * @return bool
     */
    public function isGooglePayEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * GooglePaymentMethods
     *
     * @return array |string
     */
    public function googlePaymentMethods()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/paymentmethods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * GoogleAuthMethods
     *
     * @return array |string
     */
    public function googleAuthMethods()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/authmethods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * GoogleGatewayMerchantname
     *
     * @return array|string
     */
    public function googleGatewayMerchantname()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/gateway_merchantname',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * GoogleGatewayMerchantid
     *
     * @return array|string|int
     */
    public function googleGatewayMerchantid()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/gateway_merchantid',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * GoogleMerchantname
     *
     * @return array|string|int
     */
    public function googleMerchantname()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/google_merchantname',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * GoogleMerchantid
     *
     * @return array|string|int
     */
    public function googleMerchantid()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/google_merchantid',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * IsApplePayEnable
     *
     * @return bool
     */
    public function isApplePayEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/apple_pay_wallets_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * AppleMerchantId
     *
     * @return array|string|int
     */
    public function appleMerchantId()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/apple_pay_wallets_config/merchant_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if samsung pay enable or not
     *
     * @return bool
     */
    public function isSamsungPayEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/samsung_pay_wallets_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if isDynamic3DS2Enabled enable or not
     *
     * @return bool
     */
    public function isDynamic3DS2Enabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/3ds_config/enable_dynamic3DS2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get  jwt event url
     *
     * @return bool|string
     */
    public function getJwtEventUrl()
    {
        if ($this->isDynamic3DS2Enabled()) {
            return $this->_scopeConfig->getValue(
                'worldpay/3ds_config/3ds2_config/jwt_event_url',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool) false;
    }

    /**
     * IsJwtApiKey
     *
     * @return bool
     */
    public function isJwtApiKey()
    {
        if ($this->isDynamic3DS2Enabled()) {
            return $this->_scopeConfig->getValue(
                'worldpay/3ds_config/3ds2_config/jwt_api_key',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool) false;
    }

    /**
     * IsJwtIssuer
     *
     * @return bool
     */
    public function isJwtIssuer()
    {
        if ($this->isDynamic3DS2Enabled()) {
            return $this->_scopeConfig->getValue(
                'worldpay/3ds_config/3ds2_config/jwt_issuer',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool) false;
    }

    /**
     * IsOrganisationalUnitId
     *
     * @return bool
     */
    public function isOrganisationalUnitId()
    {
        if ($this->isDynamic3DS2Enabled()) {
            return $this->_scopeConfig->getValue(
                'worldpay/3ds_config/3ds2_config/organisational_unit_id',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool) false;
    }

    /**
     * IsTestDdcUrl
     *
     * @return bool
     */
    public function isTestDdcUrl()
    {
        if ($this->isDynamic3DS2Enabled()) {
            return $this->_scopeConfig->getValue(
                'worldpay/3ds_config/3ds2_config/test_ddc_url',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool) false;
    }

    /**
     * IsProductionDdcUrl
     *
     * @return bool
     */
    public function isProductionDdcUrl()
    {
        if ($this->isDynamic3DS2Enabled()) {
            return $this->_scopeConfig->getValue(
                'worldpay/3ds_config/3ds2_config/production_ddc_url',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool) false;
    }

    /**
     * IsRiskData
     *
     * @return bool
     */
    public function isRiskData()
    {
        if ($this->isDynamic3DS2Enabled()) {
            return (bool) $this->_scopeConfig->getValue(
                'worldpay/3ds_config/3ds2_config/risk_data',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool) false;
    }

    /**
     * IsAuthenticationMethod
     *
     * @return bool
     */
    public function isAuthenticationMethod()
    {
        if ($this->isDynamic3DS2Enabled()) {
            return $this->_scopeConfig->getValue(
                'worldpay/3ds_config/3ds2_config/authentication_method',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool) false;
    }

    /**
     * IsTestChallengeUrl
     *
     * @return bool
     */
    public function isTestChallengeUrl()
    {
        if ($this->isDynamic3DS2Enabled()) {
            return $this->_scopeConfig->getValue(
                'worldpay/3ds_config/3ds2_config/test_challenge_url',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool) false;
    }

    /**
     * IsProductionChallengeUrl
     *
     * @return bool
     */
    public function isProductionChallengeUrl()
    {
        if ($this->isDynamic3DS2Enabled()) {
            return $this->_scopeConfig->getValue(
                'worldpay/3ds_config/3ds2_config/production_challenge_url',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool) false;
    }

    /**
     * IsChallengePreference
     *
     * @return bool
     */
    public function isChallengePreference()
    {
        if ($this->isDynamic3DS2Enabled()) {
            return $this->_scopeConfig->getValue(
                'worldpay/3ds_config/3ds2_config/challenge_preference',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool) false;
    }

    /**
     * Get ChallengeWindowSize
     *
     * @return bool|string
     */
    public function getChallengeWindowSize()
    {
        if ($this->isDynamic3DS2Enabled()) {
            return $this->_scopeConfig->getValue(
                'worldpay/3ds_config/3ds2_config/challenge_window_size',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool) false;
    }

    /**
     * Get DisclaimerMessage
     *
     * @return bool|string
     */
    public function getDisclaimerMessage()
    {
        if ($this->getStoredCredentials()) {
            return $this->_scopeConfig->getValue('worldpay/tokenization/configure_disclaimer/'
                    . 'stored_credentials_disclaimer_message', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        return (bool) false;
    }

    /**
     * IsDisclaimerMessageEnable
     *
     * @return bool
     */
    public function isDisclaimerMessageEnable()
    {
        if ($this->getStoredCredentials()) {
            return (bool) $this->_scopeConfig->getValue('worldpay/tokenization/configure_disclaimer'
                    . '/stored_credentials_message_enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        return (bool) false;
    }

    /**
     * IsDisclaimerMessageMandatory
     *
     * @return bool
     */
    public function isDisclaimerMessageMandatory()
    {
        if ($this->getStoredCredentials()) {
            return (bool) $this->_scopeConfig->getValue('worldpay/tokenization/configure_disclaimer/'
                    . 'stored_credentials_flag', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        return (bool) false;
    }

    /**
     * Get CountryCodeSpoofs
     *
     * @return int|string
     */
    public function getCountryCodeSpoofs()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/miscellaneous/country_code_spoof',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get subscriptionStatus
     *
     * @return bool
     */
    public function getsubscriptionStatus()
    {
        if ($this->recurringHelper->quoteContainsSubscription($this->_checkoutSession->getQuote())) {
            return true;
        }
        return false;
    }

    /**
     * IsCPFEnabled
     *
     * @return bool
     */
    public function isCPFEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/lat_america_payments/enable_cpf',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * IsInstalmentEnabled
     *
     * @return bool
     */
    public function isInstalmentEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/lat_america_payments/enable_instalment',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get 3Ds2ParamsFromSession
     *
     * @return array|string
     */
    public function get3Ds2ParamsFromSession()
    {
        return $this->_checkoutSession->get3Ds2Params();
    }
        
    /**
     * Get 3DS2ConfigFromSession
     *
     * @return array|string
     */
    public function get3DS2ConfigFromSession()
    {
        return $this->_checkoutSession->get3DS2Config();
    }
    
    /**
     * Get AuthOrderIdFromSession
     *
     * @return array|string
     */
    public function getAuthOrderIdFromSession()
    {
        return $this->_checkoutSession->getAuthOrderId();
    }
    
    /**
     * Get InstalmentValues
     *
     * @param int $countryId
     * @return array|string
     */
    public function getInstalmentValues($countryId)
    {
        return $this->instalmentconfig->getConfigTypeForCountry($countryId);
    }
    
    /**
     * Get ConfigCountries
     *
     * @return array|string
     */
    public function getConfigCountries()
    {
        return $this->instalmentconfig->getConfigCountries();
    }

    /**
     * Get MerchantTokenization
     *
     * @return bool|string
     */
    public function getMerchantTokenization()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/enable_merchant_tokens',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get MyAccountException
     *
     * @return string|array
     */
    public function getMyAccountException()
    {
                return $this->_scopeConfig->getValue(
                    'worldpay_exceptions/my_account_alert_codes/response_codes',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
    }
    
    /**
     * Get CreditCardException
     *
     * @return string|array
     */
    public function getCreditCardException()
    {
                return $this->_scopeConfig->getValue(
                    'worldpay_exceptions/ccexceptions/cc_exception',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
    }
        
    /**
     * Get GeneralException
     *
     * @return string|array
     */
    public function getGeneralException()
    {
               return $this->_scopeConfig->getValue('worldpay_exceptions/adminexceptions/'
                       . 'general_exception', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * Get CreditCardSpecificexception
     *
     * @param int|string $exceptioncode
     * @return string|array
     */
    public function getCreditCardSpecificexception($exceptioncode)
    {

        $ccdata=$this->serializer->unserialize($this->getCreditCardException());
        if (is_array($ccdata) || is_object($ccdata)) {
            foreach ($ccdata as $key => $valuepair) {
                if ($key == $exceptioncode) {
                    return $valuepair['exception_module_messages']?$valuepair['exception_module_messages']:
                        $valuepair['exception_messages'];
                }
            }
        }
    }
    
    /**
     * Get ExtendedResponse
     *
     * @param int|string $wpaycode
     * @param int $orderId
     * @return string|array
     */
    public function getExtendedResponse($wpaycode, $orderId)
    {
        $responseMessage = '';
        if (is_numeric($wpaycode)) {
            $responseMessage = $this->extendedResponseCodes->getConfigValue($wpaycode);
            if (!empty($responseMessage)) {
                $responseMessage = sprintf('Order %s has been declined, Gateway Error: '.$responseMessage, $orderId);
                return $responseMessage;
            }
        }
        return $responseMessage;
    }
    
    /**
     * Get the first order details of customer by email
     *
     * @param string $customerEmailId
     * @return array Order Item data
     */
    public function getOrderDetailsByEmailId($customerEmailId)
    {
        $itemData = $this->orderCollectionFactory->create()->addAttributeToFilter(
            'customer_email',
            $customerEmailId
        )->getFirstItem()->getData();
        return $itemData;
    }
    
    /**
     * Get the orders count of customer by email
     *
     * @param string $customerEmailId
     * @return array List of order data
     */
    public function getOrdersCountByEmailId($customerEmailId)
    {
        $lastDayInterval = new \DateTime('yesterday');
        $lastYearInterval = new  \DateTime('-12 months');
        $lastSixMonthsInterval = new  \DateTime('-6 months');
        $ordersCount = [];
        
        $ordersCount['last_day_count'] = $this->getOrderIdsCount($customerEmailId, $lastDayInterval);
        $ordersCount['last_year_count'] = $this->getOrderIdsCount($customerEmailId, $lastYearInterval);
        $ordersCount['last_six_months_count'] = $this->getOrderIdsCount($customerEmailId, $lastSixMonthsInterval);
        return $ordersCount;
    }
    
    /**
     * Get the list of orders of customer by email
     *
     * @param string $customerEmailId
     * @param string $interval
     * @return array List of order IDs
     */
    public function getOrderIdsCount($customerEmailId, $interval)
    {
        $orders = $this->orderCollectionFactory->create();
        $orders->distinct(true);
        $orders->addFieldToSelect(['entity_id','increment_id','created_at']);
        $orders->addFieldToFilter('main_table.customer_email', $customerEmailId);
        $orders->addFieldToFilter('main_table.created_at', ['gteq' => $interval->format('Y-m-d H:i:s')]);
        $orders->join(['wp' => 'worldpay_payment'], 'wp.order_id=main_table.increment_id', ['payment_type']);
        $orders->join(['og' => 'sales_order_grid'], 'og.entity_id=main_table.entity_id', '');

        return count($orders);
    }
    
    /**
     * Returns cards count that are saved within 24 hrs
     *
     * @param int|string $customerId
     *
     * @return array count of saved cards
     */
    public function getSavedCardsCount($customerId)
    {
        $now = new \DateTime();
        $lastDay = new  \DateInterval(sprintf('P%dD', 1));
        $savedCards = $this->_savecard->create()->getCollection()
                        ->addFieldToSelect(['id'])
                        ->addFieldToFilter('customer_id', ['eq' => $customerId])
                        ->addFieldToFilter('created_at', ['lteq' => $now->format('Y-m-d H:i:s')])
                       // ->addFieldToFilter('created_at', ['gteq' => $lastDay->format('Y-m-d H:i:s')]
                       ;
        return count($savedCards->getData());
    }

    /**
     * Get GlobalCurrencyExponent
     *
     * @return array Order Item data
     */
    public function getGlobalCurrencyExponent()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/miscellaneous/global_currency_exponent',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     * Is DynamicExponentEnabled
     *
     * @return bool
     */
    public function isDynamicExponentEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/miscellaneous/enable_dynamic_exponents',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     * Get AllCurrencyExponents
     *
     * @return string|array
     */
    public function getAllCurrencyExponents()
    {
            return $this->_scopeConfig->getValue(
                'worldpay/miscellaneous/currency_codes',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }

    /**
     * Get CurrencyExponent
     *
     * @param int|float|string $currencycode
     * @return string|array
     */
    public function getCurrencyExponent($currencycode)
    {
        $globalexponent = $this->getGlobalCurrencyExponent();
        $specificexponent = $this->currencyexponents->getConfigValue($currencycode);
        if ($this->isDynamicExponentEnabled() && $specificexponent!=null) {
            return $specificexponent ;
        }
        
        return $globalexponent;
    }
    
    /**
     * Get ACHDetails
     *
     * @return array
     */
    public function getACHDetails()
    {
        $integrationmode = $this->getCcIntegrationMode();
        $apmmethods = $this->getApmTypes('worldpay_apm');
        if (strtoupper($integrationmode) === 'DIRECT' &&
                array_key_exists("ACH_DIRECT_DEBIT-SSL", $apmmethods)) {
            $data = $this->getACHBankAccountTypes();
            return explode(",", $data);
        }
        return [];
    }

    /**
     * Get ACHBankAccountTypes
     *
     * @return array|string
     */
    public function getACHBankAccountTypes()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/apm_config/achaccounttypes',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     * Is PrimeRoutingEnabled
     *
     * @return bool|array|string
     */
    public function isPrimeRoutingEnabled()
    {
        $integrationmode = $this->getCcIntegrationMode();
        if (strtoupper($integrationmode) === 'DIRECT') {
            return $this->_scopeConfig->getValue(
                'worldpay/prime_routing/enable_prime_routing',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
    }
    
    /**
     * IsAdvancedPrimeRoutingEnabled
     *
     * @return bool|array|string
     */
    public function isAdvancedPrimeRoutingEnabled()
    {
        $isPrimeRoutingEnabled = $this->isPrimeRoutingEnabled();
        if ($isPrimeRoutingEnabled) {
            return $this->_scopeConfig->getValue(
                'worldpay/prime_routing/enable_advanced_prime_routing',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
           
        }
    }
    
    /**
     * Get RoutingPreference
     *
     * @return bool|array|string
     */
    public function getRoutingPreference()
    {
        $isAdvancedPrimeRoutingEnabled = $this->isAdvancedPrimeRoutingEnabled();
        if ($isAdvancedPrimeRoutingEnabled) {
            return $this->_scopeConfig->getValue(
                'worldpay/prime_routing/routing_preference',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
    }
    
    /**
     * Get DebitNetworks
     *
     * @return bool|array|string
     */
    public function getDebitNetworks()
    {
        $isAdvancedPrimeRoutingEnabled = $this->isAdvancedPrimeRoutingEnabled();
        if ($isAdvancedPrimeRoutingEnabled) {
            $debitNetworks = $this->_scopeConfig->getValue(
                'worldpay/prime_routing/debit_networks',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            if (strlen($debitNetworks)>0) {
                return explode(",", $debitNetworks);
            }
        }
        return [];
    }
    
    /**
     * Get MyAccountLabels
     *
     * @return bool|array|string
     */
    public function getMyAccountLabels()
    {
                return $this->_scopeConfig->getValue(
                    'worldpay_custom_labels/my_account_labels/my_account_label',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
    }
    
    /**
     * Get CheckoutLabels
     *
     * @return bool|array|string
     */
    public function getCheckoutLabels()
    {
                return $this->_scopeConfig->getValue(
                    'worldpay_custom_labels/checkout_labels/checkout_label',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
    }
    
    /**
     * Get AdminLabels
     *
     * @return bool|array|string
     */
    public function getAdminLabels()
    {
               return $this->_scopeConfig->getValue(
                   'worldpay_custom_labels/admin_labels/admin_label',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE
               );
    }
    
    /**
     * Get AccountLabelbyCode
     *
     * @param int|float|string $labelCode
     * @return bool|array|string
     */
    public function getAccountLabelbyCode($labelCode)
    {
        $aLabels = $this->serializer->unserialize($this->_scopeConfig->getValue(
            'worldpay_custom_labels/my_account_labels/my_account_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
        if (is_array($aLabels) || is_object($aLabels)) {
            foreach ($aLabels as $key => $valuepair) {
                if ($key == $labelCode) {
                    return $valuepair['wpay_custom_label']?$valuepair['wpay_custom_label']:
                    $valuepair['wpay_label_desc'];
                }
            }
        }
    }
    
    /**
     * Get CheckoutLabelbyCode
     *
     * @param int|float|string $labelCode
     * @return bool|array|string
     */
    public function getCheckoutLabelbyCode($labelCode)
    {
        $aLabels = $this->serializer->unserialize($this->_scopeConfig->getValue(
            'worldpay_custom_labels/checkout_labels/checkout_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
        if (is_array($aLabels) || is_object($aLabels)) {
            foreach ($aLabels as $key => $valuepair) {
                if ($key == $labelCode) {
                    return $valuepair['wpay_custom_label']?$valuepair['wpay_custom_label']:
                    $valuepair['wpay_label_desc'];
                }
            }
        }
    }

    /**
     * Get should Skip Same SiteNone
     *
     * @param string $directOrderParams
     * @return false;
     */
    public function shouldSkipSameSiteNone($directOrderParams)
    {
        if (isset($directOrderParams)) {
            $useragent = $directOrderParams['userAgentHeader'] ;
            $iosDeviceRegex = "/\(iP.+; CPU .*OS (\d+)[_\d]*.*\) AppleWebKit\//";
            $macDeviceRegex = "/\(Macintosh;.*Mac OS X (\d+)_(\d+)[_\d]*.*\) AppleWebKit\//";
            $iosVersionRegex = '/OS 12./';
            $macVersionRegex ='/OS X 10./';
            $macLatestVersionRegex = '/OS X 10_15_7/';
            if (preg_match($iosDeviceRegex, $useragent) && preg_match($iosVersionRegex, $useragent)) {
                $this->wplogger->info('Passed regex check for ios');
                return true;
            } elseif ((preg_match($macDeviceRegex, $useragent) && preg_match($macVersionRegex, $useragent))
                  && (!preg_match($macLatestVersionRegex, $useragent))) {
                $this->wplogger->info('Passed regex check for mac');
                return true;
            }
            $this->wplogger->info(json_encode($useragent));
            $this->wplogger->info('Outside regex check');
            return false;
        }
         return false;
    }

    /**
     * Get Media Directory with path
     *
     * @param string $path
     */
    public function getMediaDirectory($path)
    {
        return $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath(). $path;
    }
    
    /**
     * Get base url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->_scopeConfig->getValue("web/secure/base_url");
    }
    /**
     * Get Media url with Path
     *
     * @param string $path
     */
    public function getBaseUrlMedia($path)
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path;
    }
}
