<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Block;

class Addnewcard extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Sapient\Worldpay\Model\SavedTokenFactory
     */
    protected $_savecard;
     /**
      * @var \Magento\Customer\Model\Session
      */
    protected $_customerSession;
    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayHelper;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomerAddress
     */
    protected $currentCustomerAddress;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $_addressConfig;
    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;
     /**
      * @var \Magento\Framework\App\Config\ScopeConfigInterface
      */
    protected $scopeConfig;

     /**
      * @var \Magento\Framework\Message\ManagerInterface
      */
    protected $_messageManager;

     /**
      * @var \Magento\Integration\Model\Oauth\TokenFactory
      */
    protected $_tokenModelFactory;

    /**
     * @var array
     */
    protected static $_months;
    /**
     * @var array
     */
    protected static $_expiryYears;
    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     * @param \Magento\Customer\Helper\Session\CurrentCustomerAddress $currentCustomerAddress
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Integration\Model\Oauth\TokenFactory $tokenModelFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        \Magento\Customer\Helper\Session\CurrentCustomerAddress $currentCustomerAddress,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Integration\Model\Oauth\TokenFactory $tokenModelFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {

        $this->_customerSession = $customerSession;
        $this->worldpayHelper = $worldpayHelper;
        $this->currentCustomerAddress = $currentCustomerAddress;
        $this->_addressConfig = $addressConfig;
        $this->addressMapper = $addressMapper;
        $this->scopeConfig = $scopeConfig;
        $this->_messageManager = $messageManager;
        $this->_tokenModelFactory = $tokenModelFactory;
        $this->_storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Get Cvc Enabled
     *
     * @return string
     */

    public function requireCvcEnabled()
    {
        return $this->worldpayHelper->isCcRequireCVC();
    }

    /**
     * Get Store code
     *
     * @return string
     */
    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }

    /**
     * Get Session Id
     *
     * @return string
     */

    public function getSessionId()
    {
        return $this->_customerSession->getSessionId();
    }

    /**
     * Get Customer Token
     *
     * @return string
     */

    public function getCustomerToken()
    {
        $customerId = $this->_customerSession->getCustomer()->getId();
        $customerToken = $this->_tokenModelFactory->create();
        return $customerToken->createCustomerToken($customerId)->getToken();
    }
    /**
     * Render an address as HTML and return the result
     *
     * @param AddressInterface $address
     * @return string
     */

    public function getPrimaryBillingAddressHtml()
    {
        $address = $this->currentCustomerAddress->getDefaultBillingAddress();
        if ($address) {
            $renderer = $this->_addressConfig->getFormatByCode('html')->getRenderer();
            return $renderer->renderArray($this->addressMapper->toFlatArray($address));
        } else {
            return $this->escapeHtml(__('You have not set a default billing address.'));
        }
    }
    /**
     * Get CC Types
     *
     * @return string
     */

    public function getCCtypes()
    {
        $cctypes = $this->worldpayHelper->getCcTypes();
        return $cctypes;
    }
    /**
     * Get Checkout Specific Label
     *
     * @param Specific $labelcode
     * @return string
     */

    public function getCheckoutSpecificLabel($labelcode)
    {
        return $this->worldpayHelper->getCheckoutLabelbyCode($labelcode);
    }

    /**
     * Helps to build year html dropdown
     *
     * @return array
     */
    public function getMonths()
    {
        if (!self::$_months) {
            self::$_months = ['' => __($this->getCheckoutLabelbyCode('CO6')) ?: __('Month')];
            for ($i = 1; $i < 13; $i++) {
                $month = str_pad($i, 2, '0', STR_PAD_LEFT);
                self::$_months[$month] = date("$i - F", mktime(0, 0, 0, $i, 1));
            }
        }

        return self::$_months;
    }
    /**
     * Get Account Label by Code
     *
     * @param string $labelCode
     * @return string
     */

    public function getAccountLabelbyCode($labelCode)
    {
        return $this->worldpayHelper->getAccountLabelbyCode($labelCode);
    }
    /**
     * Get Checkout Label by Code
     *
     * @param string $labelCode
     * @return string
     */

    public function getCheckoutLabelbyCode($labelCode)
    {
        return $this->worldpayHelper->getCheckoutLabelbyCode($labelCode);
    }
    /**
     * Helps to build year html dropdown
     *
     * @return array
     */
    public function getExpiryYears()
    {
        if (!self::$_expiryYears) {
            self::$_expiryYears = ['' => __($this->getCheckoutLabelbyCode('CO7')) ?: __('Year')];
            $year = (int)date('Y');
            $endYear = ($year + 20);
            while ($year < $endYear) {
                self::$_expiryYears[$year] = $year;
                $year++;
            }
        }
        return self::$_expiryYears;
    }
    /**
     * Get Disclaimer Message Enable
     *
     * @return string
     */

    public function getDisclaimerMessageEnable()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/configure_disclaimer/stored_credentials_message_enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Disclaimer Text
     *
     * @return string
     */

    public function getDisclaimerText()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/tokenization/configure_disclaimer/stored_credentials_disclaimer_message',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Disclaimer Message Mandatory
     *
     * @return string
     */

    public function getDisclaimerMessageMandatory()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/configure_disclaimer/stored_credentials_flag',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Stored Credentials Enabled Value
     *
     * @return string
     */

    public function getStoredCredentialsEnabledValue()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/save_stored_credentials',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Credit Card Specific exception
     *
     * @param string $execptionCode
     * @return string
     */

    public function getCreditCardSpecificexception($execptionCode)
    {
        return $this->worldpayHelper->getCreditCardSpecificexception($execptionCode);
    }
}
