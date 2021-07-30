<?php

namespace Sapient\Worldpay\Block;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Checkout\Block\Cart\AbstractCart;
use Sapient\Worldpay\Helper\Data;
use Magento\Customer\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Sapient\Worldpay\Helper\Recurring;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Webpayment block
 */
class Webpayment extends Template
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
   
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
    /**
     * @var \Magento\Checkout\Block\Cart\AbstractCart
     */
    protected $cart;
    
    /**
     * @var Sapient\Worldpay\Helper\Data;
     */
    
    protected $helper;
    
    /**
     * @var Magento\Framework\Message\ManagerInterface
     */
    
    protected $messageManager;

    /**
     * @var Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $_storeManager;
    
    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    protected $recurringHelper;
    
    /**
     * @var SerializerInterface
     */
    private $serializer;
    
    /**
     * Webpayment constructor.
     * @param Template\Context $context
     * @param AbstractCart $cart
     * @param Create $helper
     * @param array $data
     * @param Recurring $recurringHelper
     */
    public function __construct(
        Template\Context $context,
        AbstractCart $cart,
        Data $helper,
        Session $customerSession,
        \Magento\Integration\Model\Oauth\TokenFactory $tokenModelFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Recurring $recurringHelper,
        SessionManagerInterface $session,
        SerializerInterface $serializer,
        array $data = []
    ) {

        $this->_helper = $helper;
        $this->_cart = $cart;
        parent::__construct(
            $context,
            $data
        );
        $this->_customerSession = $customerSession;
        $this->_tokenModelFactory = $tokenModelFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->recurringHelper = $recurringHelper;
        $this->serializer = $serializer;
        $this->session = $session;
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
     * @return string
     */
    public function getCurrency()
    {
        $currency = $this->_cart->getQuote()->getQuoteCurrencyCode();
        return $currency;
    }

    public function getAllItems()
    {
        $allItems = $this->_cart->getQuote()->getAllVisibleItems();
        return $allItems;
    }

    public function getTotal()
    {
        $quote = $this->_cart->getTotalsCache();
        $getGrandTotal = $quote['grand_total']->getData('value');

        return $getGrandTotal;
    }

    public function getShippingRate()
    {
        $quote = $this->_cart->getTotalsCache();
        $getShippingRate = $quote['shipping']->getData('value');

        return $getShippingRate;
    }
    
    public function getTaxRate()
    {
        $quote = $this->_cart->getTotalsCache();
        $getShippingRate = $quote['tax']->getData('value');

        return $getShippingRate;
    }

    public function getSubTotal()
    {
        $quote = $this->_cart->getTotalsCache();
        $getSubTotal = $quote['subtotal']->getData('value');

        return $getSubTotal;
    }
 
    public function getCustomerToken()
    {
        $customerId = $this->_customerSession->getCustomer()->getId();
        $customerToken = $this->_tokenModelFactory->create();
         return $customerToken->createCustomerToken($customerId)->getToken();
    }
    
    public function getshippingRequired()
    {
        // Disable shipping for downloadable and virtual products
        $shippingReq = true;
        $allItems = $this->_cart->getQuote()->getAllItems();
        if ($allItems) {
            $productType = [];
            if ($allItems) {
                foreach ($allItems as $item) {
                    $productType[] = $item->getProductType();
                }

                $count = count($allItems);
              
            // remove duplicates in array
                $productType = array_unique($productType);
            // remove downloadable product types in array
                $productType = array_diff($productType, ['downloadable']);
        
             // remove virtual product types in array
                $productType = array_diff($productType, ['virtual']);

            // Now check if any other product types are still there in array, if no disable shipping
                if (count($productType) == 0) {
                     $shippingReq = false;
                }
            }
       
            return $shippingReq;
      
        }
    }
    
    public function checkDownloadableProduct()
    {
        // Login required for downloadable and virtual products
        $allItems = $this->_cart->getQuote()->getAllItems();
        $productType = [];
        if ($allItems) {
            foreach ($allItems as $item) {
                $productType[] = $item->getProductType();
            }
 
            $productType = array_unique($productType);
       
            $isDownloadable = 'false';
        
            if (in_array("downloadable", $productType)) {
                $isDownloadable = 'true';
            }
            if (in_array("virtual", $productType)) {
                $isDownloadable = 'true';
            }
               
            return $isDownloadable;
        }
        return 'false';
    }

    public function getProductCount()
    {
        $allItems = $this->_cart->getQuote()->getAllVisibleItems();
        return $count = count($allItems);
    }
    
    public function getChromepayButtonName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        return $this->scopeConfig->getValue('worldpay/chromepay_config/chromepay_button_name', $storeScope);
    }
     
    public function getChromepayEnabled()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $wpayEnabled = (bool)$this->_scopeConfig->getValue('worldpay/general_config/enable_worldpay', $storeScope);

        if ($wpayEnabled) {
            return $this->scopeConfig->getValue('worldpay/chromepay_config/chromepay', $storeScope);
        }
        return false;
    }
     
    public function getPaymentMode()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        return $this->scopeConfig->getValue('worldpay/cc_config/integration_mode', $storeScope);
    }
    
    public function getEnvMode()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        return $this->scopeConfig->getValue('worldpay/general_config/environment_mode', $storeScope);
    }
    
    public function checkSubscriptionItems()
    {
        if ($this->recurringHelper->quoteContainsSubscription($this->_cart->getQuote())) {
            return true;
        }
        return false;
    }
    
    public function getGeneralException()
    {
        $generaldata=$this->serializer->unserialize($this->_helper->getGeneralException());
        $result=[];
        $data=[];
        if (is_array($generaldata) || is_object($generaldata)) {
            foreach ($generaldata as $key => $value) {

                $result['exception_code']=$key;
                $result['exception_messages'] = $value['exception_messages'];
                $result['exception_module_messages'] = $value['exception_module_messages'];
                array_push($data, $result);
            
            }
        }
        //$output=implode(',', $data);
        return json_encode($data);
    }
    public function getCreditCardException()
    {
        $generaldata=$this->serializer->unserialize($this->_helper->getCreditCardException());
        $result=[];
        $data=[];
        if (is_array($generaldata) || is_object($generaldata)) {
            foreach ($generaldata as $key => $value) {

                $result['exception_code']=$key;
                $result['exception_messages'] = $value['exception_messages'];
                $result['exception_module_messages'] = $value['exception_module_messages'];
                array_push($data, $result);
            
            }
        }
        //$output=implode(',', $data);
        return json_encode($data);
    }
    
    public function myAccountExceptions()
    {
        $generaldata=$this->serializer->unserialize($this->_helper->getMyAccountException());
        $result=[];
        $data=[];
        if (is_array($generaldata) || is_object($generaldata)) {
            foreach ($generaldata as $key => $value) {

                $result['exception_code']=$key;
                $result['exception_messages'] = $value['exception_messages'];
                $result['exception_module_messages'] = $value['exception_module_messages'];
                array_push($data, $result);
            
            }
        }
        //$output=implode(',', $data);
        return json_encode($data);
    }
    
    public function getMyAccountSpecificException($exceptioncode)
    {
        $data=json_decode($this->myAccountExceptions(), true);
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $valuepair) {
                if ($valuepair['exception_code'] == $exceptioncode) {
                    return $valuepair['exception_module_messages']?
                            $valuepair['exception_module_messages']:$valuepair['exception_messages'];
                }
            }
        }
    }
    public function getCreditCardSpecificException($exceptioncode)
    {
        return $this->_helper->getCreditCardSpecificexception($exceptioncode);
    }
    
    public function getDiscountRate()
    {
        $discountamount=0;
        $quote = $this->_cart->getTotalsCache();
        if (isset($quote['discount'])) {
            $discountamount =  $quote['discount']->getData('value');
        }
        
        return $discountamount;
    }
    
    public function isDynamic3DS2Enabled()
    {
        return $this->_helper->isDynamic3DS2Enabled();
    }
    public function getJwtEventUrl()
    {
        return $this->_helper->getJwtEventUrl();
    }
    
    public function getSessionId()
    {
        return $this->session->getSessionId();
    }
    
    public function is3DsEnabled()
    {
      return $this->_helper->is3DSecureEnabled() ||  $this->_helper->isDynamic3DEnabled(); 
    }
}
