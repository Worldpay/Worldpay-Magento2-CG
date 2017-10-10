<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Utilities;

class PaymentMethods
{
    protected static $_xml;
    protected $_xmlLocation;
    protected static $_enabledMethods;

    public function __construct( \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutsession
    ) {
        $etcDir = $moduleReader->getModuleDir(
            \Magento\Framework\Module\Dir::MODULE_ETC_DIR,
            'Sapient_Worldpay'
        );
        $this->_xmlLocation = $etcDir . '/paymentmethods.xml';
        $this->scopeConfig = $scopeConfig;
        $this->checkoutsession = $checkoutsession;
    }

    protected function _getMethod(\SimpleXmlElement $methodNode)
    {
        if ($methodNode) {
            $title = (array) $methodNode->title;
            return $title[0];
        }
    }

    protected function _getConfigCode($type)
    {
        switch ($type) {
            case 'worldpay_cc':
                return 'cc_config';
            case 'worldpay_apm':
                return 'apm_config';
            default:
                return 'apm_config';
        }
    }

    public function loadEnabledByType($type)
    {
        $methods = array();

        if ($xml = $this->_readXML()) {

            $configcode = $this->_getConfigCode($type);

            if ($enabled = $this->scopeConfig->getValue('worldpay/'.$configcode.'/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {

                foreach (explode(',', $enabled) as $methodName) {
                    $node = $xml->xpath('/paymentMethods/' . $type . '/types/' . $methodName);

                    if ($this->_paymentMethodExists($node) && $this->_methodAllowedForCountry($type, $node[0])) {
                        $methods[$methodName] = $this->_getMethod($node[0]);
                    }
                }
            }
        }

        return $methods;
    }

    private function _paymentMethodExists($paymentMethodNode)
    {
        return $paymentMethodNode && sizeof($paymentMethodNode);
    }

    public function getAvailableMethods()
    {
        $methods = $this->_readXML();
        return $methods;
    }

    protected function _readXML()
    {
        if (!self::$_xml) {
            if (file_exists($this->_xmlLocation)) {
                self::$_xml = simplexml_load_file($this->_xmlLocation);
            }
        }

        return self::$_xml;
    }

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

    private function _paymentMethodFiltersByCountry($type)
    {
        return $type === 'worldpay_apm' || $type === 'worldpay_cc';
    }

    private function _getAllowedCountryIds()
    {
        $quote = $this->checkoutsession->getQuote();
        $address = $quote->getBillingAddress(); 
        $countryid = $address->getCountryId();

        return array($countryid, 'GLOBAL');
    }

    private function _getAvailableCountryIds(\SimpleXMLElement $paymentMethodNode)
    {
        $areas = (array) $paymentMethodNode->areas;

        return is_array($areas['area']) ? $areas['area'] : array($areas['area']);
    }

    private function _isCountryAllowed($allowedCountryIds, $availableCountryIds)
    {
        $matchingCountries = array_intersect($allowedCountryIds, $availableCountryIds);

        return !empty($matchingCountries);
    }

    public function CheckCaptureRequest($type,$method)
    {

        if ($xml = $this->_readXML()) {
            $node = $xml->xpath('/paymentMethods/' . $type . '/types/' . $method );
            if ($node) {
                $capture_request = $this->_getCaptureRequest($node[0]);
                if ($capture_request==1) {
                   return true;
                }
            }
        }
        return false;
    }

    private function _getCaptureRequest(\SimpleXMLElement $paymentMethodNode)
    {
        $capturerequest = ($paymentMethodNode->capture_request) ? (string) $paymentMethodNode->capture_request : false;

        return $capturerequest;
    }
}
