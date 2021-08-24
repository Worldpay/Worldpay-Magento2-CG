<?php

namespace Sapient\Worldpay\Helper;

use Sapient\Worldpay\Model\Config\Source\HppIntegration as HPPI;
use Magento\Framework\Serialize\SerializerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_scopeConfig;
    protected $wplogger;
    
    /**
     * @var SerializerInterface
     */
    private $serializer;

    const MERCHANT_CONFIG = 'worldpay/merchant_config/';
    const INTEGRATION_MODE = 'worldpay/cc_config/integration_mode';

    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
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
        \Sapient\Worldpay\Helper\KlarnaCountries $klarnaCountries
    ) {
        $this->_scopeConfig = $scopeConfig;
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
    }

    public function isWorldPayEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/general_config/enable_worldpay',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getEnvironmentMode()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/environment_mode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getTestUrl()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/test_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getLiveUrl()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/live_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

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

    public function isMacEnabled()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/mac_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMacSecret()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/mac_secret',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isDynamic3DEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/3ds_config/enable_dynamic3DS',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function is3DSecureEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/3ds_config/do_3Dsecure',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function is3dsEnabled()
    {
        return $this->isDynamic3DEnabled() || $this->is3DSecureEnabled();
    }

    public function isLoggerEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/general_config/enable_logging',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isMotoEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/moto_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isCreditCardEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/cc_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCcTitle()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/cc_config/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

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

    public function getApmTypes($code)
    {
        $allApmMethods = [
            'CHINAUNIONPAY-SSL' => 'Union Pay',
            'IDEAL-SSL' => 'IDEAL',
            'QIWI-SSL' => 'Qiwi',
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
                $activeMethods[$method] = $allApmMethods[$method];
            }
        }
        return $activeMethods;
    }

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

    public function getCsePublicKey()
    {
        return trim($this->_scopeConfig->getValue(
            'worldpay/cc_config/cse_public_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    public function isCseEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/cc_config/cse_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isCcRequireCVC()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/cc_config/require_cvc',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCcIntegrationMode()
    {
        return $this->_scopeConfig->getValue(
            self::INTEGRATION_MODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSaveCard()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/saved_card',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getTokenization()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/save_tokenization',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getStoredCredentials()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/save_stored_credentials',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isApmEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/apm_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getApmTitle()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/apm_config/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getApmPaymentMethods()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/apm_config/paymentmethods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymentMethodSelection()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/payment_method_selection',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isAutoCaptureEnabled($storeId)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/capture_automatically',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

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

    public function isIframeIntegration($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/hpp_integration',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) == HPPI::OPTION_VALUE_IFRAME;
    }

    public function getRedirectIntegrationMode($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/hpp_integration',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCustomPaymentEnabled($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getInstallationId($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/installation_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getHideAddress($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'worldpay/hpp_config/hideaddress',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getOrderSyncInterval()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/order_sync_status/order_sync_interval',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSyncOrderStatus()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/order_sync_status/order_status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDynamicIntegrationType($paymentMethodCode)
    {
        switch ($paymentMethodCode) {
            case 'worldpay_moto':
                return 'MOTO';
            default:
                return 'ECOMMERCE';
        }
    }

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

    public function getTimeLimitOfAbandonedOrders($paymentMethodCode)
    {
        $path = sprintf(
            'worldpay/order_cleanup/%s_payment_method',
            str_replace("-", "_", $paymentMethodCode)
        );
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getDefaultCountry($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'shipping/origin/country_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getLocaleDefault($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'general/locale/code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCurrencySymbol($currencycode)
    {
        return $this->localecurrency->getCurrency($currencycode)->getSymbol();
    }

    public function getQuantityUnit($product)
    {
        return 'product';
    }

    public function checkStopAutoInvoice($code, $type)
    {
        return $this->paymentlist->checkStopAutoInvoice($code, $type);
    }

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

    public function getWorldpayAuthCookie()
    {
        return $this->_checkoutSession->getWorldpayAuthCookie();
    }

    public function setWorldpayAuthCookie($value)
    {
        return $this->_checkoutSession->setWorldpayAuthCookie($value);
    }

    public function isThreeDSRequest()
    {
        return $this->_checkoutSession->getIs3DSRequest();
    }

    public function getOrderDescription()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/general_config/order_description',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMotoTitle()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/moto_config/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

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

    public function getOrderByOrderId($orderId)
    {
        return $this->orderFactory->create()->load($orderId);
    }

    public function isWalletsEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getWalletsTitle()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    public function getSamsungServiceId()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/samsung_pay_wallets_config/service_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isGooglePayEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function googlePaymentMethods()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/paymentmethods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function googleAuthMethods()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/authmethods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function googleGatewayMerchantname()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/gateway_merchantname',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function googleGatewayMerchantid()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/gateway_merchantid',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function googleMerchantname()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/google_merchantname',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function googleMerchantid()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/google_pay_wallets_config/google_merchantid',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isApplePayEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/apple_pay_wallets_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function appleMerchantId()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/wallets_config/apple_pay_wallets_config/merchant_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isSamsungPayEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/wallets_config/samsung_pay_wallets_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isDynamic3DS2Enabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/3ds_config/enable_dynamic3DS2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

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

    public function getDisclaimerMessage()
    {
        if ($this->getStoredCredentials()) {
            return $this->_scopeConfig->getValue('worldpay/tokenization/configure_disclaimer/'
                    . 'stored_credentials_disclaimer_message', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        return (bool) false;
    }

    public function isDisclaimerMessageEnable()
    {
        if ($this->getStoredCredentials()) {
            return (bool) $this->_scopeConfig->getValue('worldpay/tokenization/configure_disclaimer'
                    . '/stored_credentials_message_enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        return (bool) false;
    }

    public function isDisclaimerMessageMandatory()
    {
        if ($this->getStoredCredentials()) {
            return (bool) $this->_scopeConfig->getValue('worldpay/tokenization/configure_disclaimer/'
                    . 'stored_credentials_flag', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        return (bool) false;
    }

    public function getCountryCodeSpoofs()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/miscellaneous/country_code_spoof',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getsubscriptionStatus()
    {
        if ($this->recurringHelper->quoteContainsSubscription($this->_checkoutSession->getQuote())) {
            return true;
        }
        return false;
    }

    public function isCPFEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/lat_america_payments/enable_cpf',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isInstalmentEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/lat_america_payments/enable_instalment',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function get3Ds2ParamsFromSession()
    {
        return $this->_checkoutSession->get3Ds2Params();
    }
    
    public function get3DS2ConfigFromSession()
    {
        return $this->_checkoutSession->get3DS2Config();
    }
    
    public function getAuthOrderIdFromSession()
    {
        return $this->_checkoutSession->getAuthOrderId();
    }
    
    public function getInstalmentValues($countryId)
    {
        return $this->instalmentconfig->getConfigTypeForCountry($countryId);
    }
    
    public function getConfigCountries()
    {
        return $this->instalmentconfig->getConfigCountries();
    }
    public function getMerchantTokenization()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/enable_merchant_tokens',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function isIAVEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/cc_config/enable_iav',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMyAccountException()
    {
                return $this->_scopeConfig->getValue(
                    'worldpay_exceptions/my_account_alert_codes/response_codes',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
    }
    
    public function getCreditCardException()
    {
                return $this->_scopeConfig->getValue(
                    'worldpay_exceptions/ccexceptions/cc_exception',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
    }
    
    public function getGeneralException()
    {
               return $this->_scopeConfig->getValue('worldpay_exceptions/adminexceptions/'
                       . 'general_exception', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
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
     * @param $customerId
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
    public function getGlobalCurrencyExponent()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/miscellaneous/global_currency_exponent',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    public function isDynamicExponentEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/miscellaneous/enable_dynamic_exponents',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    public function getAllCurrencyExponents()
    {
            return $this->_scopeConfig->getValue(
                'worldpay/miscellaneous/currency_codes',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }

    public function getCurrencyExponent($currencycode)
    {
        $globalexponent = $this->getGlobalCurrencyExponent();
        $specificexponent = $this->currencyexponents->getConfigValue($currencycode);
        if ($this->isDynamicExponentEnabled() && $specificexponent!=null) {
            return $specificexponent ;
        }
        
        return $globalexponent;
    }
    
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

    public function getACHBankAccountTypes()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/apm_config/achaccounttypes',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
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
    
    public function getMyAccountLabels()
    {
                return $this->_scopeConfig->getValue(
                    'worldpay_custom_labels/my_account_labels/my_account_label',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
    }
    
    public function getCheckoutLabels()
    {
                return $this->_scopeConfig->getValue(
                    'worldpay_custom_labels/checkout_labels/checkout_label',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
    }
    
    public function getAdminLabels()
    {
               return $this->_scopeConfig->getValue(
                   'worldpay_custom_labels/admin_labels/admin_label',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE
               );
    }
    
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
    
    public function isKlarnaEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/klarna_config/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

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
    
    public function isLevel23Enabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/level23_config/level23',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    public function getCardAcceptorTaxId()
    {
            return $this->_scopeConfig->getValue(
                'worldpay/level23_config/CardAcceptorTaxId',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }
    
    public function getDutyAmount()
    {
           return $this->_scopeConfig->getValue(
               'worldpay/level23_config/duty_amount',
               \Magento\Store\Model\ScopeInterface::SCOPE_STORE
           );
    }
    
    public function getUnitOfMeasure()
    {
           return $this->_scopeConfig->getValue(
               'worldpay/level23_config/unit_of_measure',
               \Magento\Store\Model\ScopeInterface::SCOPE_STORE
           );
    }
    
    public function getInvoicedItemsData($itemId)
    {
        $invoicedItems = $this->_itemFactory->create()->getCollection()
                ->addFieldToSelect(['product_id', 'name', 'product_type', 'tax_amount',
                    'parent_item_id', 'discount_amount', 'row_total', 'qty_ordered',
                    'row_total_incl_tax', 'weee_tax_applied_row_amount'])
                ->addFieldToFilter('item_id', ['eq' => $itemId]);
        return $invoicedItems->getData()[0];
    }
    
    public function shouldSkipSameSiteNone($directOrderParams)
    {
         if(isset($directOrderParams)) {
         $useragent = $directOrderParams['userAgentHeader'] ;
           $iosDeviceRegex = "/\(iP.+; CPU .*OS (\d+)[_\d]*.*\) AppleWebKit\//";
           $macDeviceRegex = "/\(Macintosh;.*Mac OS X (\d+)_(\d+)[_\d]*.*\) AppleWebKit\//";
           $iosVersionRegex = '/OS 12./';
           $macVersionRegex ='/OS X 10./';
           $macLatestVersionRegex = '/OS X 10_15_7/';
           if (preg_match($iosDeviceRegex,$useragent) && preg_match($iosVersionRegex,$useragent) ) {
               $this->wplogger->info('Passed regex check for ios');
              return true; 
           }elseif ((preg_match($macDeviceRegex,$useragent) && preg_match($macVersionRegex,$useragent)) 
                   &&(!preg_match($macLatestVersionRegex,$useragent))) {
              $this->wplogger->info('Passed regex check for mac'); 
              return true;
           }
           $this->wplogger->info(print_r($useragent,true));
           $this->wplogger->info('Outside regex check');
           return false;
         }
         return false;
    }
}
