<?php

namespace Sapient\Worldpay\Helper;
use Sapient\Worldpay\Model\Config\Source\HppIntegration as HPPI;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $_scopeConfig;
	protected $wplogger;
	const MERCHANT_CONFIG = 'worldpay/merchant_config/';
	const INTEGRATION_MODE = 'worldpay/cc_config/integration_mode';

	public function __construct(
		\Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Locale\CurrencyInterface $localeCurrency,
		\Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentlist,
		\Sapient\Worldpay\Helper\Merchantprofile $merchantprofile,
		\Magento\Checkout\Model\Session $checkoutSession
	)
	{
		$this->_scopeConfig = $scopeConfig;
		$this->wplogger = $wplogger;
		$this->paymentlist = $paymentlist;
		$this->localecurrency = $localeCurrency;
		$this->merchantprofile = $merchantprofile;
		$this->_checkoutSession = $checkoutSession;
	}
	public function isWorldPayEnable()
	{
		return (bool) $this->_scopeConfig->getValue('worldpay/general_config/enable_worldpay', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getEnvironmentMode()
	{
		return $this->_scopeConfig->getValue('worldpay/general_config/environment_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getTestUrl()
	{
		return  $this->_scopeConfig->getValue('worldpay/general_config/test_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getLiveUrl()
	{
		return  $this->_scopeConfig->getValue('worldpay/general_config/live_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getMerchantCode($paymentType)
	{
		$merchat_detail=$this->merchantprofile->getConfigValue($paymentType);
		$merchantCodeValue = $merchat_detail['merchant_code'];
		if (!empty($merchantCodeValue)) {
			return $merchantCodeValue;
		}
		return  $this->_scopeConfig->getValue('worldpay/general_config/merchant_code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getXmlUsername($paymentType)
	{
		$merchat_detail=$this->merchantprofile->getConfigValue($paymentType);
		$merchantCodeValue = $merchat_detail['merchant_username'];
		if (!empty($merchantCodeValue)) {
			return $merchantCodeValue;
		}
		return  $this->_scopeConfig->getValue('worldpay/general_config/xml_username', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getXmlPassword($paymentType)
	{
		$merchat_detail=$this->merchantprofile->getConfigValue($paymentType);
		$merchantCodeValue = $merchat_detail['merchant_password'];
		if (!empty($merchantCodeValue)) {
			return $merchantCodeValue;
		}
		return  $this->_scopeConfig->getValue('worldpay/general_config/xml_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function isMacEnabled()
	{
		return  $this->_scopeConfig->getValue('worldpay/general_config/mac_enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getMacSecret()
	{
		return  $this->_scopeConfig->getValue('worldpay/general_config/mac_secret', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function isDynamic3DEnabled()
	{
		return (bool) $this->_scopeConfig->getValue('worldpay/general_config/enable_dynamic3DS', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function is3DSecureEnabled() {
		if ($this->isDynamic3DEnabled()) {
			return (bool) $this->_scopeConfig->getValue('worldpay/general_config/do_3Dsecure', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		}
		return (bool) false;
	}
	public function isLoggerEnable()
	{
		return (bool) $this->_scopeConfig->getValue('worldpay/general_config/enable_logging', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function isMotoEnabled()
	{
		return (bool) $this->_scopeConfig->getValue('worldpay/moto_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function isCreditCardEnabled()
	{
		return (bool) $this->_scopeConfig->getValue('worldpay/cc_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getCcTitle()
	{
		return  $this->_scopeConfig->getValue('worldpay/cc_config/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getCcTypes($paymentconfig = "cc_config")
	{
		$allCcMethods =  array(
			'AMEX-SSL'=>'American Express','VISA-SSL'=>'Visa',
			'ECMC-SSL'=>'MasterCard','DISCOVER-SSL'=>'Discover',
			'DINERS-SSL'=>'Diners','MAESTRO-SSL'=>'Maestro','AIRPLUS-SSL'=>'AirPlus',
			'AURORE-SSL'=>'Aurore','CB-SSL'=>'Carte Bancaire',
			'CARTEBLEUE-SSL'=>'Carte Bleue','DANKORT-SSL'=>'Dankort',
			'GECAPITAL-SSL'=>'GE Capital','JCB-SSL'=>'Japanese Credit Bank',
			'LASER-SSL'=>'Laser Card','UATP-SSL'=>'UATP',
		);
		$configMethods =   explode(',', $this->_scopeConfig->getValue('worldpay/'.$paymentconfig.'/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
		$activeMethods = [];
		foreach ($configMethods as  $method ) {
			$activeMethods[$method] = $allCcMethods[$method];
		}
		return $activeMethods;
	}
	public function getApmTypes($code)
	{
		$allApmMethods =  array(
			'CHINAUNIONPAY-SSL' => 'Union Pay',
			'IDEAL-SSL' => 'IDEAL',
			'QIWI-SSL' => 'Qiwi',
			'YANDEXMONEY-SSL' => 'Yandex.Money',
			'PAYPAL-EXPRESS' => 'PayPal',
			'SOFORT-SSL' => 'SoFort EU',
			'GIROPAY-SSL' => 'GiroPay',
			'BOLETO-SSL' => 'Boleto Bancairo',
			'ALIPAY-SSL' => 'AliPay',
			'SEPA_DIRECT_DEBIT-SSL' => 'SEPA (One off transactions)',
			'KlARNA-SSL' => 'Klarna',
			'PRZELEWY-SSL' => 'P24',
			'MISTERCASH-SSL' => 'Mistercash/Bancontact'
		);
		$configMethods =   explode(',', $this->_scopeConfig->getValue('worldpay/apm_config/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
		$activeMethods = [];
		foreach ($configMethods as  $method ) {
			if ($this->paymentlist->CheckCurrency($code, $method)) {
				$activeMethods[$method] = $allApmMethods[$method];
			}
		}
		return $activeMethods;
	}
	public function getCsePublicKey(){
		return trim($this->_scopeConfig->getValue('worldpay/cc_config/cse_public_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
	}
	public function isCseEnabled()
	{
		return  (bool) $this->_scopeConfig->getValue('worldpay/cc_config/cse_enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function isCcRequireCVC()
	{
		return (bool) $this->_scopeConfig->getValue('worldpay/cc_config/require_cvc', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getCcIntegrationMode()
	{
		return  $this->_scopeConfig->getValue(self::INTEGRATION_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getSaveCard()
	{
		return (bool) $this->_scopeConfig->getValue('worldpay/cc_config/saved_card', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function isApmEnabled()
	{
		return (bool) $this->_scopeConfig->getValue('worldpay/apm_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getApmTitle()
	{
		return  $this->_scopeConfig->getValue('worldpay/apm_config/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getApmPaymentMethods()
	{
		return  $this->_scopeConfig->getValue('worldpay/apm_config/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getPaymentMethodSelection()
	{
		return  $this->_scopeConfig->getValue('worldpay/general_config/payment_method_selection', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function isAutoCaptureEnabled($storeId)
	{
		return $this->_scopeConfig->getValue('worldpay/general_config/capture_automatically', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getIntegrationModelByPaymentMethodCode($paymentMethodCode, $storeId)
	{

		if($paymentMethodCode == 'worldpay_cc' || $paymentMethodCode == 'worldpay_moto' || $paymentMethodCode == 'worldpay_cc_vault'){
			return $this->_scopeConfig->getValue(self::INTEGRATION_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		}else{
			return 'redirect';
		}

	}

	public function isIframeIntegration($storeId = null)
	{
		return $this->_scopeConfig->getValue('worldpay/cc_config/hpp_integration', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == HPPI::OPTION_VALUE_IFRAME;
	}

	public function getRedirectIntegrationMode($storeId = null){
		return $this->_scopeConfig->getValue('worldpay/cc_config/hpp_integration', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getCustomPaymentEnabled($storeId = null){
		return $this->_scopeConfig->getValue('worldpay/custom_paymentpages/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getInstallationId($storeId = null){
		return $this->_scopeConfig->getValue('worldpay/custom_paymentpages/installation_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getHideAddress($storeId = null){
		return $this->_scopeConfig->getValue('worldpay/custom_paymentpages/hideaddress', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function isOrderCleanUp()
	{
		return (bool) $this->_scopeConfig->getValue('worldpay/order_cleanup/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function orderCleanUpInterval()
	{
		return  $this->_scopeConfig->getValue('worldpay/order_cleanup/order_cleanup_interval', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function cleanOrderStatus()
	{
		return  $this->_scopeConfig->getValue('worldpay/order_cleanup/clean_order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
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

	public function updateErrorMessage($message,$orderid){
		$updatemessage = array(
			'Payment REFUSED' => sprintf('Order %s has been declined, please check your details and try again', $orderid),
			'Gateway error' => 'An unexpected error occurred, Please try to place the order again.',
			'Token does not exist' => 'There appears to be an issue with your stored data, please review in your account and update details as applicable.'
		);
		if (array_key_exists($message, $updatemessage)) {
			return $updatemessage[$message];
		}

		if (empty($message)) {

			$message = 'An error occurred on the server. Please try to place the order again.';
		}
		return $message;
	}

	public function getTimeLimitOfAbandonedOrders($paymentMethodCode)
	{
		$path = sprintf('worldpay/order_cleanup/%s_payment_method', str_replace("-", "_", $paymentMethodCode));
		return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getDefaultCountry($storeId = null)
	{
		return $this->_scopeConfig->getValue('shipping/origin/country_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getLocaleDefault($storeId = null)
	{
		return $this->_scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getCurrencySymbol($currencycode)
	{
		 return $this->localecurrency->getCurrency($currencycode)->getSymbol();
	}

	public function getQuantityUnit($product)
	{
		return 'product';
	}

	public function CheckStopAutoInvoice($code, $type)
	{
		return $this->paymentlist->CheckStopAutoInvoice($code, $type);
	}

	public function instantPurchaseEnabled()
	{
		return  (bool) $this->_scopeConfig->getValue('worldpay/cc_config/instant_purchase', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getWorldpayAuthCookie()
	{
		return $this->_checkoutSession->getWorldpayAuthCookie();
	}

	public function setWorldpayAuthCookie($value)
	{
	 	return $this->_checkoutSession->setWorldpayAuthCookie($value);
	}

	public function IsThreeDSRequest()
	{
	 	return $this->_checkoutSession->getIs3DSRequest();
	}

	public function getOrderDescription()
	{
		return $this->_scopeConfig->getValue('worldpay/general_config/order_description', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

}


