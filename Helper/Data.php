<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Helper;

use Sapient\Worldpay\Model\Config\Source\HppIntegration as HPPI;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Session\SessionManagerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var _scopeConfig
     */
    protected $_scopeConfig;
    /**
     * @var wplogger
     */
    protected $wplogger;
    /**
     * @var serializer
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
     * @var SessionManagerInterface
     */
    protected $session;
    
    /**
     * @var worldpaypaymentmodel
     */
    protected $worldpaypaymentmodel;

    /**
     * @var \Sapient\Worldpay\Model\Utilities\PaymentMethods
     */
    public $paymentlist;

    /**
     * @var \Sapient\Worldpay\Helper\Merchantprofile
     */
    public $merchantprofile;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    public $orderFactory;

    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    public $recurringHelper;

    /**
     * @var \Sapient\Worldpay\Helper\ExtendedResponseCodes
     */
    public $extendedResponseCodes;

    /**
     * @var \Sapient\Worldpay\Helper\Instalmentconfig
     */
    public $instalmentconfig;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    public $orderCollectionFactory;

    /**
     * @var \Sapient\Worldpay\Model\SavedTokenFactory
     */
    public $_savecard;

     /**
      * @var \Magento\Sales\Model\Order\ItemFactory
      */
    public $_itemFactory;

    /**
     * @var \Sapient\Worldpay\Helper\Currencyexponents
     */
    public $currencyexponents;

    /**
     * @var \Sapient\Worldpay\Helper\KlarnaCountries
     */
    public $klarnaCountries;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    public $adminsessionquote;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    public $productMetaData;

     /**
      * @var \Magento\Quote\Model\QuoteFactory
      */
    public $quoteFactory;

     /**
      * @var \Magento\Framework\Locale\CurrencyInterface
      */
    public $localecurrency;
    /**
     * @var mixed
     */
    public $quotes;

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
     * @param \Magento\Sales\Model\Order\ItemFactory $itemFactory
     * @param \Sapient\Worldpay\Helper\Currencyexponents $currencyexponents
     * @param SerializerInterface $serializer
     * @param \Sapient\Worldpay\Helper\KlarnaCountries $klarnaCountries
     * @param \Magento\Backend\Model\Session\Quote $adminsessionquote
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetaData
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel
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
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Sapient\Worldpay\Helper\Currencyexponents $currencyexponents,
        SerializerInterface $serializer,
        \Sapient\Worldpay\Helper\KlarnaCountries $klarnaCountries,
        \Magento\Backend\Model\Session\Quote $adminsessionquote,
        \Magento\Framework\App\ProductMetadataInterface $productMetaData,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel
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
        $this->_itemFactory = $itemFactory;
        $this->currencyexponents = $currencyexponents;
        $this->serializer = $serializer;
        $this->klarnaCountries = $klarnaCountries;
        $this->adminsessionquote = $adminsessionquote;
        $this->productMetaData = $productMetaData;
        $this->quoteFactory = $quoteFactory;
        $this->worldpaypaymentmodel = $worldpaypaymentmodel;
    }
    /**
     * Is WorldPay Enable or not
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
     * Get Test mode and live mode
     *
     * @return string
     */
    public function getEnvironmentMode()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/environment_mode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Test mode url
     *
     * @return string
     */
    public function getTestUrl()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/test_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Live mode url
     *
     * @return string
     */
    public function getLiveUrl()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/live_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get MerchantCode
     *
     * @param string $paymentType
     * @return string
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
     * Get Xml Username
     *
     * @param string $paymentType
     * @return string
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
     * Get Xml Password
     *
     * @param string $paymentType
     * @return string
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
     * Check isMacEnabled
     *
     * @return string
     */
    public function isMacEnabled()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/mac_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Mac Secret
     *
     * @return string
     */
    public function getMacSecret()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/mac_secret',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Check Dynamic3DEnabled
     *
     * @return string
     */
    public function isDynamic3DEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/3ds_config/enable_dynamic3DS',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Check 3DSecureEnabled
     *
     * @return string
     */
    public function is3DSecureEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/3ds_config/do_3Dsecure',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Check 3dsEnabled
     *
     * @return string
     */

    public function is3dsEnabled()
    {
        return $this->isDynamic3DEnabled() || $this->is3DSecureEnabled();
    }
    /**
     * Check LoggerEnabled
     *
     * @return string
     */
    public function isLoggerEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/general_config/enable_logging',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Check MotoEnabled
     *
     * @return string
     */
    public function isMotoEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/moto_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Check Credit Card Enabled
     *
     * @return string
     */
    public function isCreditCardEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/cc_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Check CCTitle
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
     * Get credit Card Payment Methods
     *
     * @return string
     */
    public function getCcPaymentMethods()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/cc_config/paymentmethods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Check CCType
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
    * Get ApmTypes
    *
    * @param string $code
    * @return string
    */
    public function getApmTypes($code)
    {
        $allApmMethods = [
            'CHINAUNIONPAY-SSL' => 'Union Pay',
            'IDEAL-SSL' => 'IDEAL',
            //'YANDEXMONEY-SSL' => 'Yandex.Money',
            'PAYPAL-EXPRESS' => 'PayPal',
            'SOFORT-SSL' => 'SoFort EU',
            'GIROPAY-SSL' => 'GiroPay',
            //'BOLETO-SSL' => 'Boleto Bancairo',
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
                if (isset($allApmMethods[$method])) {
                    $activeMethods[$method] = $allApmMethods[$method];
                }
            }
        }
        return $activeMethods;
    }
   /**
    * Get Wallets Types
    *
    * @param string $code
    * @return string
    */
    public function getWalletsTypes($code)
    {
        $activeMethods = [];
        if ($this->isGooglePayEnable()) {
            $activeMethods['PAYWITHGOOGLE-SSL'] = 'Google Pay';
        }
        if ($this->isApplePayEnable()) {
            $activeMethods['APPLEPAY-SSL'] = 'Apple Pay';
        }
        if ($this->isSamsungPayEnable()) {
            $activeMethods['SAMSUNGPAY-SSL'] = 'Samsung Pay';
        }
        return $activeMethods;
    }
   /**
    * Get Cse PublicKey
    *
    * @return string
    */
    public function getCsePublicKey()
    {
        return trim($this->_scopeConfig->getValue(
            'worldpay/cc_config/cse_public_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }
   /**
    * Check Cse Enabled
    *
    * @return string
    */
    public function isCseEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/cc_config/cse_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
   /**
    * Check CcRequireCVC
    *
    * @return string
    */
    public function isCcRequireCVC()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/cc_config/require_cvc',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
   /**
    * Get Cc Integration Mode
    *
    * @return string
    */
    public function getCcIntegrationMode()
    {
        return $this->_scopeConfig->getValue(
            self::INTEGRATION_MODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
   /**
    * Get Save Card
    *
    * @return string
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
     * @return string
     */

    public function getTokenization()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/save_tokenization',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Stored Credentials
     *
     * @return string
     */

    public function getStoredCredentials()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/save_stored_credentials',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Check ApmEnabled
     *
     * @return string
     */
    public function isApmEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/apm_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Apm Title
     *
     * @return string
     */

    public function getApmTitle()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/apm_config/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Apm Payment Methods
     *
     * @return string
     */
    public function getApmPaymentMethods()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/apm_config/paymentmethods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Payment Method Selection
     *
     * @return string
     */

    public function getPaymentMethodSelection()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/payment_method_selection',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Auto Capture Enabled
     *
     * @param Int $storeId
     * @return string
     */

    public function isAutoCaptureEnabled($storeId)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/capture_automatically',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Integration ModelBy Payment Method Code
     *
     * @param string $paymentMethodCode
     * @param Int $storeId
     * @return string
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
     * Check IframeIntegration
     *
     * @param Int $storeId
     * @return string
     */

    public function isIframeIntegration($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/hpp_integration',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) == HPPI::OPTION_VALUE_IFRAME;
    }
    /**
     * Check Redirect Integration Mode
     *
     * @param Int $storeId
     * @return string
     */
    public function getRedirectIntegrationMode($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/hpp_integration',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Custom Payment Enabled
     *
     * @param Int $storeId
     * @return string
     */

    public function getCustomPaymentEnabled($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get InstallationId
     *
     * @param string $paymentType
     * @return string
     */

    public function getInstallationId($paymentType = null)
    {
        if ($paymentType) {
            $merchant_detail = $this->merchantprofile->getConfigValue($paymentType);
            $merchantInstallationId = $merchant_detail ? $merchant_detail['merchant_installation_id']:'';
            if (!empty($merchantInstallationId)) {
                return $merchantInstallationId;
            }
        }

        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/installation_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get InstallationId
     *
     * @param int $storeId
     * @return string
     */
    public function getHideAddress($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/hideaddress',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Order Sync Interval
     *
     * @return string
     */

    public function getOrderSyncInterval()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/order_sync_status/order_sync_interval',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Sync Order Status
     *
     * @return string
     */

    public function getSyncOrderStatus()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/order_sync_status/order_status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Dynamic Integration Type
     *
     * @param string $paymentMethodCode
     * @return string
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
     * Get Error Message
     *
     * @param string $message
     * @param string $orderid
     * @return string
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
     * Get Time Limit Of Abandoned Orders
     *
     * @param string $paymentMethodCode
     * @return string
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
     * Get Time Limit Of Abandoned Orders
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
     * Get Locale Default
     *
     * @param int $storeId
     * @return string
     */
    public function getLocaleDefault($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'general/locale/code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Currency Symbol
     *
     * @param string $currencycode
     * @return string
     */
    public function getCurrencySymbol($currencycode)
    {
        return $this->localecurrency->getCurrency($currencycode)->getSymbol();
    }
    /**
     * Get Quantity Unit
     *
     * @param string $product
     * @return string
     */

    public function getQuantityUnit($product)
    {
        return 'product';
    }
    /**
     * Get Quantity Unit
     *
     * @param string $code
     * @param string $type
     * @return string
     */

    public function checkStopAutoInvoice($code, $type)
    {
        return $this->paymentlist->checkStopAutoInvoice($code, $type);
    }
    /**
     * Check instant Purchase Enabled
     *
     * @return string
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
     * Get Worldpay AuthCookie
     *
     * @return string
     */
    public function getWorldpayAuthCookie()
    {
        return $this->_checkoutSession->getWorldpayAuthCookie();
    }
    /**
     * Get Worldpay AuthCookie
     *
     * @param string $value
     * @return string
     */
    public function setWorldpayAuthCookie($value)
    {
        return $this->_checkoutSession->setWorldpayAuthCookie($value);
    }
    /**
     * Get Worldpay isThreeDSRequest
     *
     * @return string
     */
    public function isThreeDSRequest()
    {
        return $this->_checkoutSession->getIs3DSRequest();
    }
    /**
     * Get OrderDescription
     *
     * @return string
     */
    public function getOrderDescription()
    {
        $description = $this->_scopeConfig->getValue(
            'worldpay/general_config/order_description',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($this->isMultiShipping()) {
            return $this->getMultiShippingOrderDescription();
        }
        return $description;
    }

    /**
     * Get Multishipping OrderDescription
     *
     * @return string
     */
    public function getMultiShippingOrderDescription()
    {
        $description = $this->_scopeConfig->getValue(
            'worldpay/general_config/order_description',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return __('Multishipping - ').$description;
    }
    /**
     * Get MotoTitle
     *
     * @return string
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
     * @param array $order
     * @param string $paymentCode
     * @param object $worldpaypayment
     * @return string
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
            //Klarna sliceit check
            if (strpos($item->getPaymentType(), "KLARNA_SLICEIT-SSL") !== false
               && strlen($item->getPaymentType()) > 18) {
                return $apmTitle . "\n" . $item->getPaymentType() . "\r\n" . "Installments: "
                    . rtrim(rtrim(substr($item->getPaymentType(), 15, 5), '_'), 'MOS') . " months";
            } else {
                return $this->getApmTitle() . "\n" . $item->getPaymentType();
            }
        } elseif ($paymentCode == 'worldpay_wallets') {
            return $this->getWalletsTitle() . "\n" . $item->getPaymentType();
        } elseif ($paymentCode == 'worldpay_moto') {
            return $this->getMotoTitle() . "\n" . $item->getPaymentType();
        }
    }
    /**
     * Get Order By OrderId
     *
     * @param string $orderId
     * @return string
     */
    public function getOrderByOrderId($orderId)
    {
        return $this->orderFactory->create()->load($orderId);
    }
    /**
     * Get Wallets Enabled
     *
     * @return string
     */
    public function isWalletsEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Wallets Title
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
     * Get Samsung ServiceId
     *
     * @return string
     */
    public function getSamsungServiceId()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/samsung_pay_wallets_config/service_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Google Pay Enable
     *
     * @return string
     */
    public function isGooglePayEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get google Payment Methods
     *
     * @return string
     */
    public function googlePaymentMethods()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/paymentmethods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get google Auth Methods
     *
     * @return string
     */
    public function googleAuthMethods()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/authmethods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get google Gateway Merchantname
     *
     * @return string
     */
    public function googleGatewayMerchantname()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/gateway_merchantname',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get google Gateway Merchantid
     *
     * @return string
     */
    public function googleGatewayMerchantid()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/gateway_merchantid',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get google Merchant name
     *
     * @return string
     */
    public function googleMerchantname()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/google_merchantname',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get google Merchantid
     *
     * @return string
     */
    public function googleMerchantid()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/google_merchantid',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Check ApplePay Enable
     *
     * @return string
     */
    public function isApplePayEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/apple_pay_wallets_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get apple MerchantId
     *
     * @return string
     */
    public function appleMerchantId()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/apple_pay_wallets_config/merchant_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get is Samsung Pay Enable
     *
     * @return string
     */
    public function isSamsungPayEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/samsung_pay_wallets_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get is Dynamic 3DS2 Enabled
     *
     * @return string
     */
    public function isDynamic3DS2Enabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/3ds_config/enable_dynamic3DS2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Jwt Event Url
     *
     * @return string
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
     * Get Jwt Api Key
     *
     * @return string
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
     * Get Jwt Is suer
     *
     * @return string
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
     * Get Organisational Unit Id
     *
     * @return string
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
     * Get Test Ddc Url
     *
     * @return string
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
     * Get Production Ddc Url
     *
     * @return string
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
     * Check Risk Data
     *
     * @return string
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
     * Check is Authentication Method
     *
     * @return string
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
     * Check is Test Challenge Url
     *
     * @return string
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
     * Production Challenge Url
     *
     * @return string
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
     * Get Challenge Preference
     *
     * @return string
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
     * Get Challenge Window Size
     *
     * @return string
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
     * Get Disclaime rMessage
     *
     * @return string
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
     * Check Disclaimer Message Enable
     *
     * @return string
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
     * Get Disclaimer Message Mandatory
     *
     * @return string
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
     * Get Country Code Spoofs
     *
     * @return string
     */
    public function getCountryCodeSpoofs()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/miscellaneous/country_code_spoof',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get subscription Status
     *
     * @return string
     */
    public function getsubscriptionStatus()
    {
        if ($this->recurringHelper->quoteContainsSubscription($this->_checkoutSession->getQuote())) {
            return true;
        }
        return false;
    }
    /**
     * Get CPF Enabled
     *
     * @return string
     */
    public function isCPFEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/lat_america_payments/enable_cpf',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Check Instalment Enabled
     *
     * @return string
     */
    public function isInstalmentEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/lat_america_payments/enable_instalment',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get 3Ds2 Params From Session
     *
     * @return string
     */
    public function get3Ds2ParamsFromSession()
    {
        return $this->_checkoutSession->get3Ds2Params();
    }
    /**
     * Get 3Ds2 Config From Session
     *
     * @return string
     */
    public function get3DS2ConfigFromSession()
    {
        return $this->_checkoutSession->get3DS2Config();
    }
    /**
     * Get AuthOrderId From Session
     *
     * @return string
     */
    public function getAuthOrderIdFromSession()
    {
        return $this->_checkoutSession->getAuthOrderId();
    }
    /**
     * Get Instalment Values
     *
     * @param int $countryId
     * @return string
     */
    public function getInstalmentValues($countryId)
    {
        return $this->instalmentconfig->getConfigTypeForCountry($countryId);
    }
    /**
     * Get Config Countries
     *
     * @return string
     */
    public function getConfigCountries()
    {
        return $this->instalmentconfig->getConfigCountries();
    }
    /**
     * Get Merchant Tokenization
     *
     * @return string
     */

    public function getMerchantTokenization()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/enable_merchant_tokens',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Check isIAVEnabled
     *
     * @return string
     */
    public function isIAVEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/cc_config/enable_iav',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get MyAccount Exception
     *
     * @return string
     */
    public function getMyAccountException()
    {
                return $this->_scopeConfig->getValue(
                    'worldpay_exceptions/my_account_alert_codes/response_codes',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
    }
    /**
     * Get Credit Card Exception
     *
     * @return string
     */
    public function getCreditCardException()
    {
                return $this->_scopeConfig->getValue(
                    'worldpay_exceptions/ccexceptions/cc_exception',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
    }
     /**
      * Get General Exception
      *
      * @return string
      */
    public function getGeneralException()
    {
               return $this->_scopeConfig->getValue('worldpay_exceptions/adminexceptions/'
                       . 'general_exception', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    /**
     * Get Credit Card Specific exception
     *
     * @param string $exceptioncode
     * @return string
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
     * Get MyAccount Specific exception
     *
     * @param string $exceptioncode
     * @return string
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
     * Get Extended Response
     *
     * @param string $wpaycode
     * @param string $orderId
     * @return string
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
     * Get Order Details By Email Id
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
     * Get Orders CountBy Email Id
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
     * @param int $interval
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
     * @param int $customerId
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
     * @return string
     */

    public function getGlobalCurrencyExponent()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/miscellaneous/global_currency_exponent',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Dynamic Exponent Enabled
     *
     * @return string
     */
    public function isDynamicExponentEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/miscellaneous/enable_dynamic_exponents',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get All Currency Exponents
     *
     * @return string
     */
    public function getAllCurrencyExponents()
    {
            return $this->_scopeConfig->getValue(
                'worldpay/miscellaneous/currency_codes',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }
    /**
     * Get Currency Exponent
     *
     * @param string $currencycode
     * @return string
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
     * Get ACH Details
     *
     * @return string
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
     * Get ACH Bank Account Types
     *
     * @return string
     */
    public function getACHBankAccountTypes()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/apm_config/achaccounttypes',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Prime Routing Enabled
     *
     * @return string
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
     * Get Advanced Prime Routing Enabled
     *
     * @return string
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
     * Get Routing Preference
     *
     * @return string
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
     * Get Debit Networks
     *
     * @return string
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
     * Get MyAccount Labels
     *
     * @return string
     */
    public function getMyAccountLabels()
    {
                return $this->_scopeConfig->getValue(
                    'worldpay_custom_labels/my_account_labels/my_account_label',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
    }
    /**
     * Get Checkout Labels
     *
     * @return string
     */
    public function getCheckoutLabels()
    {
                return $this->_scopeConfig->getValue(
                    'worldpay_custom_labels/checkout_labels/checkout_label',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
    }
    /**
     * Get Admin Labels
     *
     * @return string
     */
    public function getAdminLabels()
    {
               return $this->_scopeConfig->getValue(
                   'worldpay_custom_labels/admin_labels/admin_label',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE
               );
    }
    /**
     * Get Account Label by Code
     *
     * @param string $labelCode
     * @return string
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
    * Get Checkout Label by Code
    *
    * @param string $labelCode
    * @return string
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
    * Check Klarna Enabled
    *
    * @return string
    */
    public function isKlarnaEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/klarna_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
   /**
    * Get Klarna Sliceit Type
    *
    * @return string
    */
    public function getKlarnaSliceitType()
    {
        $isKlarnaEnabled = $this->isKlarnaEnabled();
        if ($isKlarnaEnabled) {
            return $this->_scopeConfig->getValue(
                'worldpay/klarna_config/sliceit_config/klarna_sliceit',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
    }
   /**
    * Get Klarna Sliceit Contries
    *
    * @return string
    */
    public function getKlarnaSliceitContries()
    {
        $isKlarnaEnabled = $this->isKlarnaEnabled();
        if ($isKlarnaEnabled && $this->getKlarnaSliceitType() !== null) {
            $sliceitContries = $this->_scopeConfig->getValue(
                'worldpay/klarna_config/sliceit_config/sliceit_contries',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            if (strlen($sliceitContries) > 0) {
                return $sliceitContries;
            }
        }
    }
   /**
    * Get Klarna Pay Later Type
    *
    * @return string
    */
    public function getKlarnaPayLaterType()
    {
        $isKlarnaEnabled = $this->isKlarnaEnabled();
        if ($isKlarnaEnabled) {
            return $this->_scopeConfig->getValue(
                'worldpay/klarna_config/paylater_config/klarna_paylater',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
    }
   /**
    * Get Klarna Pay Later Contries
    *
    * @return string
    */
    public function getKlarnaPayLaterContries()
    {
        $isKlarnaEnabled = $this->isKlarnaEnabled();
        if ($isKlarnaEnabled && $this->getKlarnaPayLaterType() !== null) {
            $payLaterContries = $this->_scopeConfig->getValue(
                'worldpay/klarna_config/paylater_config/paylater_contries',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            if (strlen($payLaterContries) > 0) {
                return $payLaterContries;
            }
        }
    }
   /**
    * Get Klarna Pay PayNow Type
    *
    * @return string
    */
    public function getKlarnaPayNowType()
    {
        $isKlarnaEnabled = $this->isKlarnaEnabled();
        if ($isKlarnaEnabled) {
            return $this->_scopeConfig->getValue(
                'worldpay/klarna_config/paynow_config/klarna_paynow',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
    }
   /**
    * Get Klarna Pay PayNow Contries
    *
    * @return string
    */
    public function getKlarnaPayNowContries()
    {
        $isKlarnaEnabled = $this->isKlarnaEnabled();
        if ($isKlarnaEnabled && $this->getKlarnaPayNowType() !== null) {
            $paynowContries = $this->_scopeConfig->getValue(
                'worldpay/klarna_config/paynow_config/paynow_contries',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            if (strlen($paynowContries) > 0) {
                return $paynowContries;
            }
        }
    }
   /**
    * Get Klarna Subscription Days
    *
    * @param string $countryCode
    * @return string
    */
    public function getKlarnaSubscriptionDays($countryCode)
    {
        if ($countryCode) {
            $subscription_detail = $this->klarnaCountries->getConfigValue($countryCode);

            $subscriptionDays = $subscription_detail ? $subscription_detail['subscription_days'] : '';
            if (!empty($subscriptionDays)) {
                return $subscriptionDays;
            }
        }
    }
   /**
    * Check Level23 Enable
    *
    * @return string
    */
    public function isLevel23Enabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/level23_config/level23',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
   /**
    * Get CardAcceptor Tax Id
    *
    * @return string
    */
    public function getCardAcceptorTaxId()
    {
            return $this->_scopeConfig->getValue(
                'worldpay/level23_config/CardAcceptorTaxId',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }
   /**
    * Get DutyAmount
    *
    * @return string
    */
    public function getDutyAmount()
    {
           return $this->_scopeConfig->getValue(
               'worldpay/level23_config/duty_amount',
               \Magento\Store\Model\ScopeInterface::SCOPE_STORE
           );
    }
   /**
    * Get Unit Of Measure
    *
    * @return string
    */
    public function getUnitOfMeasure()
    {
           return $this->_scopeConfig->getValue(
               'worldpay/level23_config/unit_of_measure',
               \Magento\Store\Model\ScopeInterface::SCOPE_STORE
           );
    }
   /**
    * Get Invoiced Items Data
    *
    * @param int $itemId
    * @return string
    */
    public function getInvoicedItemsData($itemId)
    {
        $invoicedItems = $this->_itemFactory->create()->getCollection()
                ->addFieldToSelect(['product_id', 'name', 'product_type', 'tax_amount',
                    'parent_item_id', 'discount_amount', 'row_total', 'qty_ordered',
                    'row_total_incl_tax', 'weee_tax_applied_row_amount'])
                ->addFieldToFilter('item_id', ['eq' => $itemId]);
        return $invoicedItems->getData()[0];
    }
   /**
    * Get should Skip Same SiteNone
    *
    * @param string $directOrderParams
    * @return string
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
     * Get Moto Merchant Code
     *
     * @param string $storeId
     * @return string
     */
    public function getMerchantCodeByStoreId($storeId)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/merchant_code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    /**
     * Get Moto Username
     *
     * @param string $storeId
     * @return string
     */
    public function getMerchantUsernameByStoreId($storeId)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/xml_username',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    /**
     * Get Moto Password
     *
     * @param string $storeId
     * @return string
     */
    public function getMerchantPasswordByStoreId($storeId)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/xml_password',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

   /**
    * Get Moto Merchant Code
    *
    * @param string $storeId
    * @return string
    */
    public function getMotoMerchantCode($storeId = "")
    {
        if ($storeId) {
            return $this->_scopeConfig->getValue(
                'worldpay/moto_config/moto_merchant_code',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return $this->_scopeConfig->getValue(
            'worldpay/moto_config/moto_merchant_code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Moto Username
     *
     * @param string $storeId
     * @return string
     */
    public function getMotoUsername($storeId = "")
    {
        if ($storeId) {
            return $this->_scopeConfig->getValue(
                'worldpay/moto_config/moto_username',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return $this->_scopeConfig->getValue(
            'worldpay/moto_config/moto_username',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Moto Password
     *
     * @param string $storeId
     * @return string
     */
    public function getMotoPassword($storeId = "")
    {
        if ($storeId) {
            return $this->_scopeConfig->getValue(
                'worldpay/moto_config/moto_password',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return $this->_scopeConfig->getValue(
            'worldpay/moto_config/moto_password',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Moto Integration Mode
     *
     * @return string
     */
    public function getMotoIntegrationMode()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/moto_config/moto_integration_mode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Additional Merchant Profiles
     *
     * @return string
     */
    public function getAdditionalMerchantProfiles()
    {
        return $this->merchantprofile->getAdditionalMerchantProfiles();
    }
    /**
     *  Check if Global APM API Call configuration is enabled
     */
    public function isGlobalApmEnable()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/enable_global_apm_call',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if there is order code present in checkout session
     */
    public function getOrderCodeFromCheckoutSession()
    {
        return $this->_checkoutSession->getHppOrderCode();
    }
      /**
       *  Check if Payment Method Logo config is enabled
       */
    public function isPaymentMethodlogoEnable()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/payment_method_logo_config/payment_method_logo_config_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Credit card uploaded file value
     *
     * @param string $methodCode
     * @return string
     */
    public function getCcLogoConfigValue($methodCode)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/payment_method_logo_config/cc/'.$methodCode.'/'.'logo_config',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get APM uploaded file value
     *
     * @param string $methodCode
     * @return string
     */
    public function getApmLogoConfigValue($methodCode)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/payment_method_logo_config/apm/'.$methodCode.'/'.'logo_config',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Wallet uploaded file value
     *
     * @param string $methodCode
     * @return string
     */
    public function getWalletLogoConfigValue($methodCode)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/payment_method_logo_config/wallet/'.$methodCode.'/'.'logo_config',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if Credit Card config enable
     *
     * @param string $methodCode
     * @return bool
     */
    public function isCcLogoConfigEnabled($methodCode)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/payment_method_logo_config/cc/'.$methodCode.'/'.'enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if APM config enable
     *
     * @param string $methodCode
     * @return bool
     */
    public function isApmLogoConfigEnabled($methodCode)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/payment_method_logo_config/apm/'.$methodCode.'/'.'enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if Wallet config enable
     *
     * @param string $methodCode
     * @return bool
     */
    public function isWalletLogoConfigEnabled($methodCode)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/payment_method_logo_config/wallet/'.$methodCode.'/'.'enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
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
     * Return worldpay payment methods
     */
    public function getWpPaymentMethods()
    {
        return [
            \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod::WORLDPAY_APM_TYPE,
            \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod::WORLDPAY_CC_TYPE,
            \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod::WORLDPAY_MOTO_TYPE,
            \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod::WORLDPAY_WALLETS_TYPE,
            \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod::WORLDPAY_CC_TYPE,
            'worldpay_cc_vault'
        ];
    }
    /**
     * Return Store Base Url
     */
    public function getBaseUrl()
    {
        return $this->_scopeConfig->getValue("web/secure/base_url");
    }
    /**
     *  Check if googlepay enable on PDP
     */
    public function isGooglePayEnableonPdp()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_pdp_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get google pay customise options
     */
    public function getGooglePayButtonConfig()
    {
        return trim($this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_pdp_config/gpay_btn_configuration',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }
    /**
     * Get Place order text
     */
    public function getGooglePayPopupPlaceOrderText()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_pdp_config/gpay_place_order_button_configuration',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

     /**
      *  Check if Apple Pay on PDP is enabled
      */
    public function isApplePayEnableonPdp()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/apple_pay_wallets_pdp_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * ApplePay button color
     */
    public function getApplePayButtonColor()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/apple_pay_wallets_pdp_config/applepay_button_color',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * ApplePay button type
     */
    public function getApplePayButtonType()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/apple_pay_wallets_pdp_config/applepay_button_type',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * ApplePay button locale
     */
    public function getApplePayButtonLocale()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/apple_pay_wallets_pdp_config/applepay_button_locale',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     *  Apple Pay on PopUp Order Place Button Text
     */
    public function getApplePayPopupPlaceOrderText()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/apple_pay_wallets_pdp_config/applepay_place_order_button_configuration',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get googlepay button color
     */
    public function getGpayButtonColor()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_pdp_config/gpay_button_color',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get googlepay button type
     */
    public function getGpayButtonType()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_pdp_config/gpay_button_type',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get googlepay button locale
     */
    public function getGpayButtonLocale()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_pdp_config/gpay_button_locale',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get moto store id
     */
    public function getStoreIdFromQuoteForAdminOrder()
    {

        $adminQuote = $this->adminsessionquote->getQuote();
        $storeId = $adminQuote->getStoreId();
        if (empty($storeId)) {
            $storeId = $this->_storeManager->getStore()->getId();
        }
        return $storeId;
    }
    /**
     * Function to create jwt token
     */
    public function createJwtToken()
    {
        $jwtApiKey = $this->isJwtApiKey();
        $jwtIssuer = $this->isJwtIssuer();
        $orgUnitId = $this->isOrganisationalUnitId();
        $iat = $this->getCurrentDate();
        $jwtTokenId    = base64_encode(random_bytes(16));
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'jti' => $jwtTokenId,
            'iat' => $iat,
            'iss' => $jwtIssuer,
            'OrgUnitId' => $orgUnitId,
        ]);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $jwtApiKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        return $jwt;
    }

    /**
     * Get current date
     */
    public function getCurrentDate()
    {
        $curdate = date("Y-m-d H:i:s");
        return strtotime(date("Y-m-d H:i:s", strtotime($curdate)). " -1 min");
    }

    /**
     * Get DDC url
     */
    public function getDdcUrl()
    {
        $ddcurl = '';
        $mode = $this->getEnvironmentMode();
        if ($mode == 'Test Mode') {
            $ddcurl =  $this->isTestDdcUrl();
        } else {
            $ddcurl =  $this->isProductionDdcUrl();
        }
        return $ddcurl;
    }

    /**
     * Create Second JWT token
     *
     * @param String $redirectUrl
     * @param Array $payload
     */
    public function createSecondJWTtoken($redirectUrl, array $payload)
    {
        $jwtApiKey = $this->isJwtApiKey();
        $jwtIssuer = $this->isJwtIssuer();
        $orgUnitId = $this->isOrganisationalUnitId();
        $iat = $this->getCurrentDate();
        $jwtTokenId    = base64_encode(random_bytes(16));
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'jti' => $jwtTokenId,
            'iat' => $iat,
            'iss' => $jwtIssuer,
            'OrgUnitId' => $orgUnitId,
            'ReturnUrl' => $redirectUrl,
            'Payload' => [
                'ACSUrl' => $payload['ACSUrl'],
                'Payload'=> $payload['Payload'],
                'TransactionId'=> $payload['TransactionId'],

            ],
            'ObjectifyPayload'=> true
        ]);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $jwtApiKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        return $jwt;
    }

    /**
     * Get Current Worldpay Plugin
     */
    public function getCurrentWopayPluginVersion()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/plugin_tracker/current_wopay_plugin_version',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Current Worldpay Plugin History
     */
    public function getWopayPluginVersionHistory()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/plugin_tracker/wopay_plugin_version_history',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Plugin Upgrade Dates
     */
    public function getUpgradeDates()
    {
         return $this->_scopeConfig->getValue(
             'worldpay/general_config/plugin_tracker/upgrade_dates',
             \Magento\Store\Model\ScopeInterface::SCOPE_STORE
         );
    }
    /**
     * Get Plugin PHP Version
     */
    public function getPhpVersionUsed()
    {
        return phpversion();
    }
    /**
     * Get Current Magento Version Details
     */
    public function getCurrentMagentoVersionDetails()
    {
        $magento['Edition'] = $this->productMetaData->getEdition();
        $magento['Version'] = $this->productMetaData->getVersion();
        return $magento;
    }
    /**
     * Get Plugin Tracker Details
     *
     * @param string $username
     * @param SimpleXMLElement $xmlQuote
     */
    public function getPluginTrackerdetails($username, $xmlQuote)
    {
        $details=[];

        $xmlquoteData  = clone($xmlQuote);
        $quoteData = new \SimpleXmlElement($xmlquoteData->saveXML());
        $merchantCode = (string) $quoteData['merchantCode'];
        $details['MERCHANT_ID'] = $merchantCode;
        $details['API_USERNAME'] = $username;
        $magento = $this->getCurrentMagentoVersionDetails();
        $details['MAGENTO_EDITION'] = $magento['Edition'];
        $details['MAGENTO_VERSION'] = $magento['Version'];
        $details['PHP_VERSION'] = $this->getPhpVersionUsed();
        
        if (($this->getCurrentWopayPluginVersion()!=null) && !empty($this->getCurrentWopayPluginVersion())) {
            $details['CURRENT_WORLDPAY_PLUGIN_VERSION'] = $this->getCurrentWopayPluginVersion();
        }
        
        if (($this->getWopayPluginVersionHistory()!=null) && !empty($this->getWopayPluginVersionHistory())) {
            $details['WORLDPAY_PLUGIN_VERSION_USED_TILL_DATE'] = $this->getWopayPluginVersionHistory();
        }
        
        if (($this->getUpgradeDates()!=null) && !empty($this->getUpgradeDates())) {
            $details['UPGRADE_DATES'] = $this->getUpgradeDates();
        }
        return $details;
    }

    /**
     * Get Quote Data's
     *
     * @param int|null $quoteId
     * @return Quote
     */
    public function getQuote($quoteId = null)
    {
        $quote = $this->_checkoutSession->getQuote();
        return $quote;
    }

    /**
     *  Check if Multishipping Enable in Admin end
     *
     * @return bool
     */
    public function isMultiShippingEnabledInCc()
    {
        $multishippingEnabled = false;
        $isWorldPayEnabled = $this->isWorldPayEnable();
        $isCreditCardEnabled = $this->isCreditCardEnabled();
        $getsubscriptionStatus = $this->getsubscriptionStatus();
        if ($isWorldPayEnabled && $isCreditCardEnabled && !$getsubscriptionStatus) {
            $multishippingEnabled = $this->isMultishippingEnabled();
        }
        return $multishippingEnabled;
    }

    /**
     *  Check if Multishipping Enable in Admin end
     *
     * @return bool
     */
    public function isMultiShippingEnabledInWallets()
    {
        $multishippingEnabled = false;
        $isWorldPayEnabled = $this->isWorldPayEnable();
        $isWalletsEnabled = $this->isWalletsEnabled();
        $getsubscriptionStatus = $this->getsubscriptionStatus();
        if ($isWorldPayEnabled && $isWalletsEnabled && !$getsubscriptionStatus) {
            $multishippingEnabled = $this->isMultishippingEnabled();
        }
        return $multishippingEnabled;
    }

    /**
     *  Check if Multishipping Enable in Admin end
     *
     * @return bool
     */
    public function isMultiShippingEnabledInApm()
    {
        $multishippingEnabled = false;
        $isWorldPayEnabled = $this->isWorldPayEnable();
        $isApmEnabled = $this->isApmEnabled();
        $getsubscriptionStatus = $this->getsubscriptionStatus();
        if ($isWorldPayEnabled && $isApmEnabled && !$getsubscriptionStatus) {
            $multishippingEnabled = $this->isMultishippingEnabled();
        }
        return $multishippingEnabled;
    }
    
    /**
     * Get Multishipping Enabled
     *
     * @return string
     */
    public function isMultishippingEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/multishipping/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     *  Check if Quote is Multishipping
     *
     * @param Quote $quote
     * @return bool
     */
    public function isMultiShipping($quote = null)
    {
        if (empty($quote)) {
            $quote = $this->getQuote();
        }

        if (empty($quote)) {
            return false;
        }

        return (bool)$quote->getIsMultiShipping();
    }

    /**
     * Get Quote by quote id
     *
     * @param int $quoteId
     * @return array
     */
    public function loadQuoteById($quoteId)
    {
        if (!isset($this->quotes)) {
            $this->quotes = [];
        }

        if (!empty($this->quotes[$quoteId])) {
            return $this->quotes[$quoteId];
        }

        $this->quotes[$quoteId] = $this->quoteFactory->create()->load($quoteId);

        return $this->quotes[$quoteId];
    }

    /**
     * Check if quote is mulishipping
     *
     * @param int $quoteId
     * @return bool
     */
    public function isMultishippingOrder($quoteId)
    {
        if (empty($quoteId)) {
            return false;
        }

        $quote = $this->loadQuoteById($quoteId);
        if (!$quote || !$quote->getId()) {
            return false;
        }

        return (bool)$quote->getIsMultiShipping();
    }
     /**
      *  Check if Pay By Link enable
      */
    public function isPayByLinkEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/paybylink_config/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Pay by Link Button Name
     */
    public function getPayByLinkButtonName()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/paybylink_config/paybylink_button_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Pay by Link Expirt Lifetime
     */
    public function getPayByLinkExpiryTime()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/paybylink_config/paybylink_expiry_time',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Check if Pay By Link resend option enable
     */
    public function isPayByLinkResendEnable()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/paybylink_config/paybylink_resend_link',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * GetWorldpayPayment Model
     */
    public function getWorldpayPaymentModel()
    {
        return $this->worldpaypaymentmodel;
    }
    /**
     * Get Recurring MerchantCode
     *
     * @return string
     */
    public function getRecurringMerchantCode()
    {
        $recurringMerchantCode = $this->_scopeConfig->getValue(
            'worldpay/recurring_merchant_config/recurring_merchant_code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $recurringMerchantCode;
    }
    /**
     * Get Recurring Merchant Username
     *
     * @return string
     */
    public function getRecurringUsername()
    {
        $recurringMerchantUn = $this->_scopeConfig->getValue(
            'worldpay/recurring_merchant_config/recurring_username',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $recurringMerchantUn;
    }
    /**
     * Get Recurring Merchant Password
     *
     * @return string
     */
    public function getRecurringPassword()
    {
        $recurringMerchantPw = $this->_scopeConfig->getValue(
            'worldpay/recurring_merchant_config/recurring_password',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $recurringMerchantPw;
    }

    /**
     *  Check if Pay By Link merchant code
     */
    public function getPayByLinkMerchantCode()
    {
        $paybyLinkMC = $this->_scopeConfig->getValue(
            'worldpay/paybylink_config/pbl_merchant_code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $paybyLinkMC;
    }

    /**
     * Get Pay by Link Merchant username
     */
    public function getPayByLinkMerchantUsername()
    {
        $paybyLinkUn = $this->_scopeConfig->getValue(
            'worldpay/paybylink_config/pbl_xml_username',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $paybyLinkUn;
    }

    /**
     * Get Pay by Link merchant password
     */
    public function getPayByLinkMerchantPassword()
    {
        $paybyLinkPw = $this->_scopeConfig->getValue(
            'worldpay/paybylink_config/pbl_xml_password',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $paybyLinkPw;
    }

    /**
     *  Get multishipping merchant code
     */
    public function getMultishippingMerchantCode()
    {
        $multishippingMC = $this->_scopeConfig->getValue(
            'worldpay/multishipping/ms_merchant_code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $multishippingMC;
    }

    /**
     * Get multishipping Merchant username
     */
    public function getMultishippingMerchantUsername()
    {
        $multishippingUn = $this->_scopeConfig->getValue(
            'worldpay/multishipping/ms_xml_username',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $multishippingUn;
    }

    /**
     * Get multishipping merchant password
     */
    public function getMultishippingMerchantPassword()
    {
        $multishippingPw = $this->_scopeConfig->getValue(
            'worldpay/multishipping/ms_xml_password',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $multishippingPw;
    }
    /**
     * Get multishipping Installation Id
     */
    public function getMultishippingInstallationId()
    {
        $multishippingIID = $this->_scopeConfig->getValue(
            'worldpay/multishipping/ms_xml_installationId',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $multishippingIID;
    }

    /**
     * Get PaybyLink Installation Id
     */
    public function getPayByLinkInstallationId()
    {
        $pblIId = $this->_scopeConfig->getValue(
            'worldpay/paybylink_config/pbl_xml_installationId',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $pblIId;
    }
    
    /**
     * Calculate Pay By Link Resend expiry time
     *
     * @param int $expiryTime
     * @retrun int
     */
    public function calculatePblResendExpiryTime($expiryTime)
    {
        return $expiryTime * 2;
    }
    
    /**
     * Find Pay By Link Interval time between order date and current date
     *
     * @param string $currentDate
     * @param string $orderDate
     * @retrun int
     */
    public function findPblOrderIntervalTime($currentDate, $orderDate)
    {
        return round(abs(strtotime($currentDate) - strtotime($orderDate))/3600, 0);
    }
    
    /**
     * Find Pay By Link expiry date and time
     *
     * @param string $currentDate
     * @param string $expiryTime
     * @retrun int
     */
    public function findPblOrderExpiryTime($currentDate, $expiryTime)
    {
        return strtotime(date("Y-m-d H:i:s", strtotime($currentDate)) . " -$expiryTime hour");
    }
    
    /**
     * Find Pay By Link expiry date and time
     *
     * @param string $minDate
     * @retrun array
     */
    public function findFromToPblDateAndTime($minDate)
    {
        $dates = [];
        $cronMinDate = date('Y-m-d H:i', $minDate).':00';
        $maxDate = strtotime(date("Y-m-d H:i:s", strtotime($cronMinDate)) . " +59 seconds");
        $cronMaxDate = date('Y-m-d H:i:s', $maxDate);
        $dates['from'] = $cronMinDate;
        $dates['to'] = $cronMaxDate;
        
        return $dates;
    }
    /**
     * Get Capture Delay value
     */
    public function getCaptureDelayValues()
    {
        $captureDelay = $this->_scopeConfig->getValue(
            'worldpay/dynamic_capture_delay/capture_delay',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($captureDelay == \Sapient\Worldpay\Model\Config\Source\CaptureDelay::CUSTOM_CAPTURE_DELAY_KEY) {
            $captureDelay = $this->_scopeConfig->getValue(
                'worldpay/dynamic_capture_delay/capture_delay_custom_value',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return $captureDelay;
    }
    /**
     * Get SEPA Details
     *
     * @return string
     */
    public function getSEPADetails()
    {
        $integrationmode = $this->getCcIntegrationMode();
        $apmmethods = $this->getApmTypes('worldpay_apm');
        if (strtoupper($integrationmode) === 'DIRECT' &&
                array_key_exists("SEPA_DIRECT_DEBIT-SSL", $apmmethods)) {
            $data = $this->getSEPAMandateTypes();
            if (!empty($data)) {
                return explode(",", $data);
            }
        }
        return [];
    }
    /**
     * Get SEPA E-Mandate
     * 
     * @return string
     */
    public function getSepaEmandate(){
        return $this->_scopeConfig->getValue(
            'worldpay/apm_config/sepa_e_mandate',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get SEPA Mandate Types
     *
     * @return string
     */
    public function getSEPAMandateTypes()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/apm_config/sepa_mandate_types',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get SEPA Merchant Number
     *
     * @return string
     */

    public function getSEPAMerchantNo()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/apm_config/sepa_merchant_no',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Order By OrderIncrementId
     *
     * @param string $orderIncId
     * @return string
     */
    public function getOrderByOrderIncId($orderIncId)
    {
        return $this->orderFactory->create()->loadByIncrementId($orderIncId);
    }
}
