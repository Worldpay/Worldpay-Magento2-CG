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
     * @param \Magento\Backend\Block\Template\Context $context,
     * @param \Sapient\Worldpay\Model\SavedTokenFactory $savecard,
     * @param \Magento\Customer\Model\Session $customerSession,
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        //\Sapient\Worldpay\Model\SavedTokenFactory $savecard,
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
        //$this->_savecard = $savecard;
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
    
    public function getSessionId()
    {
        return $this->_customerSession->getSessionId();
    }
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
        /** @var \Magento\Customer\Block\Address\Renderer\RendererInterface $renderer */
        $address = $this->currentCustomerAddress->getDefaultBillingAddress();
        
        if ($address) {
            $renderer = $this->_addressConfig->getFormatByCode('html')->getRenderer();
            return $renderer->renderArray($this->addressMapper->toFlatArray($address));
        } else {
            return $this->escapeHtml(__('You have not set a default billing address.'));
        }
    }

    public function getCCtypes()
    {
        $cctypes = $this->worldpayHelper->getCcTypes();
        return $cctypes;
    }
    
    public function getCheckoutSpecificLabel($labelcode)
    {
        $data = $this->worldpayHelper->getCheckoutLabelbyCode($labelcode);
        return $data;
    }

    /**
     * Helps to build year html dropdown
     *
     * @return array
     */
    public function getMonths()
    {
        if (!self::$_months) {
            self::$_months = ['' => __($this->getCheckoutLabelbyCode('CO6') ?
                    $this->getCheckoutLabelbyCode('CO6') : 'Month')];
            for ($i = 1; $i < 13; $i++) {
                $month = str_pad($i, 2, '0', STR_PAD_LEFT);
                self::$_months[$month] = date("$i - F", mktime(0, 0, 0, $i, 1));
            }
        }

        return self::$_months;
    }

    public function getAccountLabelbyCode($labelCode)
    {
        return $this->worldpayHelper->getAccountLabelbyCode($labelCode);
    }
    
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
            self::$_expiryYears = ['' => __($this->getCheckoutLabelbyCode('CO7')
                    ? $this->getCheckoutLabelbyCode('CO7') : 'Year')];
            $year = date('Y');
            $endYear = ($year + 20);
            while ($year < $endYear) {
                self::$_expiryYears[$year] = $year;
                $year++;
            }
        }
        return self::$_expiryYears;
    }
    
    public function getDisclaimerMessageEnable()
    {
        
          return (bool) $this->_scopeConfig->getValue('worldpay/tokenization/configure_disclaimer'
                  . '/stored_credentials_message_enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function getDisclaimerText()
    {
        
            return $this->_scopeConfig->getValue('worldpay/tokenization/configure_disclaimer/'
                    . 'stored_credentials_disclaimer_message', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function getDisclaimerMessageMandatory()
    {
        
            return (bool) $this->_scopeConfig->getValue('worldpay/tokenization/configure_disclaimer/'
                    . 'stored_credentials_flag', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function getStoredCredentialsEnabledValue()
    {
        return (bool) $this->_scopeConfig->getValue(
            'worldpay/tokenization/save_stored_credentials',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function getCreditCardSpecificexception($execptionCode)
    {
        return $this->worldpayHelper->getCreditCardSpecificexception($execptionCode);
    }
}
