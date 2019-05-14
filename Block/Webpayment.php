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
     * Webpayment constructor.
     * @param Template\Context $context
     * @param AbstractCart $cart
     * @param Create $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        AbstractCart $cart,
        Data $helper,
        array $data = [],
        Session $customerSession,
        \Magento\Integration\Model\Oauth\TokenFactory $tokenModelFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\ManagerInterface $messageManager)
    {

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
    }

    /**
     * @return string
     */
    public function getCurrency(){
        $currency = $this->_cart->getQuote()->getQuoteCurrencyCode();
        return $currency;
    }

    public function getAllItems(){
        $allItems = $this->_cart->getQuote()->getAllVisibleItems();
        return $allItems;
    }

    public function getTotal(){
        $quote = $this->_cart->getTotalsCache();
        $getGrandTotal = $quote['grand_total']->getData('value');

        return $getGrandTotal;
    }

    public function getShippingRate(){
        $quote = $this->_cart->getTotalsCache();
        $getShippingRate = $quote['shipping']->getData('value');

        return $getShippingRate;
    }
    
    public function getTaxRate(){
        $quote = $this->_cart->getTotalsCache();
        $getShippingRate = $quote['tax']->getData('value');

        return $getShippingRate;
    }

    public function getSubTotal(){
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
        $shippingReq = 'true';
        $allItems = $this->_cart->getQuote()->getAllItems();
        if($allItems) {
        $productType = array();
        if($allItems) {
        foreach($allItems as $item) {
            $productType[] = $item->getProductType();
         }

        $count = count($allItems); 
       
       
        // remove duplicates in array
        $productType = array_unique($productType);
        // remove downloadable product types in array
        $productType = array_diff( $productType, ['downloadable'] );
        
         // remove virtual product types in array
        $productType = array_diff( $productType, ['virtual'] );

        // Now check if any other product types are still there in array, if no disable shipping
        if( sizeof($productType) == 0 ) {
             $shippingReq = 'false';  
        }
        }
       
        return $shippingReq;
      
        }
          
    }
    
    public function checkDownloadableProduct()
    {
        // Login required for downloadable and virtual products
        $allItems = $this->_cart->getQuote()->getAllItems();
        $productType = array();
        if($allItems) {
        foreach($allItems as $item) {
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
    
     public function getChromepayButtonName() {
     $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

     return $this->scopeConfig->getValue('worldpay/cc_config/chromepay_button_name', $storeScope);
     }
     
     public function getChromepayEnabled() {
     $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

     return $this->scopeConfig->getValue('worldpay/cc_config/chromepay', $storeScope);
     }
     
     public function getPaymentMode() {
     $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

     return $this->scopeConfig->getValue('worldpay/cc_config/integration_mode', $storeScope);
     }
    
}