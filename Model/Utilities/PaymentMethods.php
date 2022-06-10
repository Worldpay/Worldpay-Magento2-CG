<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Utilities;

/**
 * Reading the xml
 */
class PaymentMethods
{
    
    /**
     * @var SimpleXMLElement
     */
    protected static $_xml;

    /**
     * @var string
     */
    protected $_xmlLocation;
    /**
     * @var PAYMENT_METHOD_PATH
     */
    public const PAYMENT_METHOD_PATH = '/paymentMethods/';
    /**
     * @var TYPE_PATH
     */
    public const TYPE_PATH = '/types/';

    /**
     * [__construct description]
     *
     * @param \Magento\Framework\Module\Dir\Reader               $moduleReader      [description]
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig       [description]
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager      [description]
     * @param \Sapient\Worldpay\Logger\WorldpayLogger            $wplogger          [description]
     * @param \Magento\Checkout\Model\Session                    $checkoutsession   [description]
     * @param \Magento\Backend\Model\Session\Quote               $adminsessionquote [description]
     * @param \Magento\Backend\Model\Auth\Session                $authSession       [description]
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Magento\Backend\Model\Session\Quote $adminsessionquote,
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        $etcDir = $moduleReader->getModuleDir(
            \Magento\Framework\Module\Dir::MODULE_ETC_DIR,
            'Sapient_Worldpay'
        );
        $this->_xmlLocation = $etcDir . '/paymentmethods.xml';
        $this->_storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->wplogger = $wplogger;
        $this->checkoutsession = $checkoutsession;
        $this->adminsessionquote = $adminsessionquote;
        $this->authSession = $authSession;
    }

    /**
     * Get Title of paymentMethod
     *
     * @param \SimpleXmlElement $methodNode
     * @return String
     */
    protected function _getMethod(\SimpleXmlElement $methodNode)
    {
        if ($methodNode) {
            $title = (array) $methodNode->title;
            return $title[0];
        }
    }

    /**
     * Get config code
     *
     * @param string $type
     * @return String
     */
    protected function _getConfigCode($type)
    {
        switch ($type) {
            case 'worldpay_cc':
                return 'cc_config';
            case 'worldpay_apm':
                return 'apm_config';
            case 'worldpay_wallets':
                return 'wallets_config';
            default:
                return 'apm_config';
        }
    }

    /**
     * Load enable payment type
     *
     * Variable $type is worldpay_apm $paymentType is CHINAUNIONPAY-SSL,YANDEXMONEY-SSL
     *
     * @param string $type
     * @param string $paymentType
     * @return array $methods
     */
    public function loadEnabledByType($type, $paymentType)
    {
        $methods = [];
        if ($xml = $this->_readXML()) {
            $node = $xml->xpath(self::PAYMENT_METHOD_PATH . $type . self::TYPE_PATH . $paymentType);
            if ($this->_paymentMethodExists($node) && $this->_methodAllowedForCountry($type, $node[0])) {
                return true;
            } else {
                return false;
            }
        }
        return $methods;
    }

    /**
     * Check payment method exit or not
     *
     * @param string $paymentMethodNode
     * @return Boolean
     */
    private function _paymentMethodExists($paymentMethodNode)
    {
        return $paymentMethodNode && count($paymentMethodNode);
    }

    /**
     * Get Available Methods
     *
     * @return SimpleXMLElement $methods
     */
    public function getAvailableMethods()
    {
        $methods = $this->_readXML();
        return $methods;
    }

    /**
     * Read xml
     *
     * @return SimpleXMLElement
     */
    protected function _readXML()
    {

        $validator = new \Zend\Validator\File\Exists();
        if (!self::$_xml && $validator->isValid($this->_xmlLocation)) {
             self::$_xml = simplexml_load_file($this->_xmlLocation);
        }
        return self::$_xml;
    }

    /**
     * Check if paymentmethod is allowed for country
     *
     * @param string $type
     * @param SimpleXMLElement $paymentMethodNode
     * @return bool
     */
    private function _methodAllowedForCountry($type, \SimpleXMLElement $paymentMethodNode)
    {
        if (!$this->_paymentMethodFiltersByCountry($type)) {
            return true;
        }
        return $this->_isCountryAllowed(
            $this->_getAllowedCountryIds(),
            $this->_getAvailableCountryIds($paymentMethodNode)
        );
    }

    /**
     * Check if payment is placed through worldpay
     *
     * @param string $type
     * @return bool
     */
    private function _paymentMethodFiltersByCountry($type)
    {
        return $type === 'worldpay_apm' ||
               $type === 'worldpay_cc' ||
               $type === 'worldpay_moto' ||
               $type === 'worldpay_cc_vault';
    }

    /**
     * Get allowed country Id
     *
     * @return array
     */
    private function _getAllowedCountryIds()
    {
        $quote = $this->checkoutsession->getQuote();
        if ($this->authSession->isLoggedIn()) {
            $adminQuote = $this->adminsessionquote->getQuote();
            if (empty($quote->getReservedOrderId()) && !empty($adminQuote->getReservedOrderId())) {
                $quote = $adminQuote;
            }
        }
        $address = $quote->getBillingAddress();
        $countryid = $address->getCountryId();

        return [$countryid, 'GLOBAL'];
    }

    /**
     * Get available country ids
     *
     * @param \SimpleXMLElement $paymentMethodNode
     * @return array
     */
    private function _getAvailableCountryIds(\SimpleXMLElement $paymentMethodNode)
    {
        $areas = (array) $paymentMethodNode->areas;

        return is_array($areas['area']) ? $areas['area'] : [$areas['area']];
    }

    /**
     * Is Country Allowed
     *
     * @param array $allowedCountryIds
     * @param array $availableCountryIds
     * @return bool
     */
    private function _isCountryAllowed($allowedCountryIds, $availableCountryIds)
    {
        $matchingCountries = array_intersect($allowedCountryIds, $availableCountryIds);

        return !empty($matchingCountries);
    }

    /**
     * Check capture request is enabled or not
     *
     * @param string $type
     * @param string $method
     * @return bool
     */
    public function checkCaptureRequest($type, $method)
    {

        if ($xml = $this->_readXML()) {
            $node = $xml->xpath(self::PAYMENT_METHOD_PATH . $type . self::TYPE_PATH . $method);
            if ($node) {
                $capture_request = $this->_getCaptureRequest($node[0]);
                if ($capture_request==1) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get capture request
     *
     * @param \SimpleXMLElement $paymentMethodNode
     * @return string|bool
     */
    private function _getCaptureRequest(\SimpleXMLElement $paymentMethodNode)
    {
        $capturerequest = ($paymentMethodNode->capture_request) ? (string) $paymentMethodNode->capture_request : false;

        return $capturerequest;
    }

    /**
     * Check currency
     *
     * @param string $code
     * @param string $type
     * @return boolean
     */
    public function checkCurrency($code, $type)
    {
        if ($xml = $this->_readXML()) {
             $node = $xml->xpath(self::PAYMENT_METHOD_PATH . $code . self::TYPE_PATH . $type .'/currencies');
            if (!$this->_currencyNodeExists($node) || $this->_typeAllowedForCurrency($node[0])) {
                return true;
            } else {
                return false;
            }

        }
        return true;
    }
    /**
     * [_currencyNodeExists description]
     *
     * @param  [type] $node [description]
     * @return [type]       [description]
     */
    private function _currencyNodeExists($node)
    {
        return $node && count($node);
    }
    /**
     * [_typeAllowedForCurrency description]
     *
     * @param  \SimpleXMLElement $node [description]
     * @return [type]                  [description]
     */
    private function _typeAllowedForCurrency(\SimpleXMLElement $node)
    {
        return $this->_isCurrencyAllowed(
            $this->_getAllowedCurrencies(),
            $this->_getAvailableCurrencyCodes($node)
        );
    }
    /**
     * [_getAllowedCurrencies description]
     *
     * @return [type] [description]
     */
    private function _getAllowedCurrencies()
    {
        $currencyCode = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        return [$currencyCode];
    }
    /**
     * [_getAvailableCurrencyCodes description]
     *
     * @param  \SimpleXMLElement $node [description]
     * @return [type]                  [description]
     */
    private function _getAvailableCurrencyCodes(\SimpleXMLElement $node)
    {
        $currencies = (array) $node;

        return is_array($currencies['currency']) ? $currencies['currency'] : [$currencies['currency']];
    }
    /**
     * [_isCurrencyAllowed description]
     *
     * @param  [type]  $allowedCurrencyCodes   [description]
     * @param  [type]  $availableCurrencyCodes [description]
     * @return boolean                         [description]
     */
    private function _isCurrencyAllowed($allowedCurrencyCodes, $availableCurrencyCodes)
    {
        $matchingCurrencies = array_intersect($allowedCurrencyCodes, $availableCurrencyCodes);

        return !empty($matchingCurrencies);
    }
    /**
     * [checkShipping description]
     *
     * @param  [type] $code [description]
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    public function checkShipping($code, $type)
    {
        if ($xml = $this->_readXML()) {
             $node = $xml->xpath(self::PAYMENT_METHOD_PATH . $code . self::TYPE_PATH . $type .'/shippingareas');
            if (!$this->_shippingNodeExists($node) || $this->_typeAllowedForShipping($node[0])) {
                return true;
            } else {
                return false;
            }

        }
        return true;
    }
    /**
     * [_shippingNodeExists description]
     *
     * @param  [type] $node [description]
     * @return [type]       [description]
     */
    private function _shippingNodeExists($node)
    {
        return $node && count($node);
    }
    /**
     * [_typeAllowedForShipping description]
     *
     * @param  \SimpleXMLElement $node [description]
     * @return [type]                  [description]
     */
    private function _typeAllowedForShipping(\SimpleXMLElement $node)
    {
        return $this->_isShippingAllowed(
            $this->_getAllowedShippingCountries(),
            $this->_getAvailableShippingCountries($node)
        );
    }

    /**
     * Get Allowed shipping Countries
     *
     * @return array
     */
    private function _getAllowedShippingCountries()
    {
        $quote = $this->checkoutsession->getQuote();
        $address = $quote->getShippingAddress();
        $countryid = $address->getCountryId();

        return [$countryid,'GLOBAL'];
    }

    /**
     * Get Available ShippingCountries
     *
     * @param \SimpleXMLElement $node
     * @return bool
     */
    private function _getAvailableShippingCountries(\SimpleXMLElement $node)
    {
        $areas = (array) $node;

        return is_array($areas['ship']) ? $areas['ship'] : [$areas['ship']];
    }
    /**
     * [_isShippingAllowed description]
     *
     * @param  [type]  $allowedShippingCountries   [description]
     * @param  [type]  $availableShippingCountries [description]
     * @return boolean                             [description]
     */
    private function _isShippingAllowed($allowedShippingCountries, $availableShippingCountries)
    {
        $matchingCountries = array_intersect($allowedShippingCountries, $availableShippingCountries);

        return !empty($matchingCountries);
    }
    /**
     * [checkStopAutoInvoice description]
     *
     * @param  [type] $code [description]
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    public function checkStopAutoInvoice($code, $type)
    {
        if ($xml = $this->_readXML()) {
             $node = $xml->xpath(self::PAYMENT_METHOD_PATH . $code . self::TYPE_PATH . $type .'/stop_auto_invoice');
            if ($this->_autoInvoiceNodeExists($node) && $this->_getStopAutoInvoice($node[0]) == 1) {
                return true;
            } else {
                return false;
            }

        }
        return false;
    }
    /**
     * [_autoInvoiceNodeExists description]
     *
     * @param  [type] $node [description]
     * @return [type]       [description]
     */
    private function _autoInvoiceNodeExists($node)
    {
        return $node && count($node);
    }

    /**
     * [_getStopAutoInvoice description]
     *
     * @param  \SimpleXMLElement $node [description]
     * @return [type]                  [description]
     */
    private function _getStopAutoInvoice(\SimpleXMLElement $node)
    {
        $stopautoinvoice = (string) $node;
        return $stopautoinvoice;
    }
    
    /**
     * List of ideal banks
     *
     * @return array
     */
    public function idealBanks()
    {
        $banks = [];
        if ($xml = $this->_readXML()) {
            $node = $xml->xpath('/paymentMethods/' . 'worldpay_apm' . '/types/' . 'IDEAL-SSL'. '/banks');
            if ($this->_paymentMethodExists($node)) {
                 $bankinfos = $node[0];
                $bankdetails = [];
                foreach ($bankinfos->bank as $bankinfo) {
                    $bankcode = (string) $bankinfo->code;
                    $bankvalue = (string) $bankinfo->value;
                    $bankdetails[$bankcode] = $bankvalue;
                }
                return $bankdetails;
            }
        }
    }

    /**
     * Payment Type Countries
     *
     * @return string
     */
    public function getPaymentTypeCountries()
    {
        $codes = ['worldpay_cc','worldpay_apm','worldpay_moto', 'worldpay_cc_vault'];
        $paymenttypecountries = [];
        foreach ($codes as $code) {
            if ($xml = $this->_readXML()) {
                 $nodes = $xml->xpath('/paymentMethods/' . $code . '/types');
            }
             $typearray =  [];
            foreach ($nodes[0] as $key => $value) {
                $key = (string) $key;
                $area =  (array) $value->areas[0]->area;
                $typearray[$key] = $area;
            }
             $paymenttypecountries[$code] = $typearray;
        }
        return json_encode($paymenttypecountries);
    }
}
