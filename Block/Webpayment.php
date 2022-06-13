<?php
/**
 * @copyright 2017 Sapient
 */
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
     * @var $cart
     */
    protected $cart;
    
    /**
     * @var Sapient\Worldpay\Helper\Data;
     */
    
    protected $helper;
    
    /**
     * @var Magento\Framework\Message\ManagerInterface
     */
    
    public $_messageManager;

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
     * @param Session $customerSession
     * @param TokenFactory $tokenModelFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $messageManager
     * @param StoreManagerInterface $storeManager
     * @param Recurring $recurringHelper
     * @param SessionManagerInterface $session
     * @param SerializerInterface $serializer
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param array $data
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
        \Magento\Framework\View\Asset\Repository $assetRepo,
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
        $this->_assetRepo = $assetRepo;
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
     * Get Currency
     *
     * @return string
     */

    public function getCurrency()
    {
        $currency = $this->_cart->getQuote()->getQuoteCurrencyCode();
        return $currency;
    }
    /**
     * Get All Items
     *
     * @return string
     */

    public function getAllItems()
    {
        $allItems = $this->_cart->getQuote()->getAllVisibleItems();
        return $allItems;
    }
    /**
     * Get Total
     *
     * @return string
     */

    public function getTotal()
    {
        $quote = $this->_cart->getTotalsCache();
        $getGrandTotal = $quote['grand_total']->getData('value');

        return $getGrandTotal;
    }
    /**
     * Get Shipping Rate
     *
     * @return string
     */

    public function getShippingRate()
    {
        $quote = $this->_cart->getTotalsCache();
        $getShippingRate = $quote['shipping']->getData('value');

        return $getShippingRate;
    }
    /**
     * Get Tax Rate
     *
     * @return string
     */

    public function getTaxRate()
    {
        $quote = $this->_cart->getTotalsCache();
        $getShippingRate = $quote['tax']->getData('value');

        return $getShippingRate;
    }
    /**
     * Get Sub Total
     *
     * @return string
     */

    public function getSubTotal()
    {
        $quote = $this->_cart->getTotalsCache();
        $getSubTotal = $quote['subtotal']->getData('value');

        return $getSubTotal;
    }
    /**
     * Get Customer Token
     *
     * @return string
     */

    public function getCustomerToken()
    {
        if(!$this->_customerSession->isLoggedIn()){
            return null;
        }
        $customerId = $this->_customerSession->getCustomer()->getId();
        $customerToken = $this->_tokenModelFactory->create();
         return $customerToken->createCustomerToken($customerId)->getToken();
    }
    /**
     * Get Shipping
     *
     * @return string
     */

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
    /**
     * Check Downloadable Product
     *
     * @return string
     */

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
    /**
     * Get Product Count
     *
     * @return string
     */

    public function getProductCount()
    {
        $allItems = $this->_cart->getQuote()->getAllVisibleItems();
        return $count = count($allItems);
    }
    /**
     * Get Chrome Pay Button
     *
     * @return string
     */

    public function getChromepayButtonName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        return $this->scopeConfig->getValue('worldpay/chromepay_config/chromepay_button_name', $storeScope);
    }
    /**
     * Get Chrome Pay Button
     *
     * @return string
     */

    public function getChromepayEnabled()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $wpayEnabled = (bool)$this->_scopeConfig->getValue('worldpay/general_config/enable_worldpay', $storeScope);

        if ($wpayEnabled) {
            return $this->scopeConfig->getValue('worldpay/chromepay_config/chromepay', $storeScope);
        }
        return false;
    }
    /**
     * Get Payment Mode
     *
     * @return string
     */
    public function getPaymentMode()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        return $this->scopeConfig->getValue('worldpay/cc_config/integration_mode', $storeScope);
    }
    /**
     * Get Env mode
     *
     * @return string
     */
    public function getEnvMode()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        return $this->scopeConfig->getValue('worldpay/general_config/environment_mode', $storeScope);
    }
    /**
     * Get check subcribed Item
     *
     * @return string
     */

    public function checkSubscriptionItems()
    {
        if ($this->recurringHelper->quoteContainsSubscription($this->_cart->getQuote())) {
            return true;
        }
        return false;
    }
    /**
     * Check General Exception
     *
     * @return string
     */
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
    /**
     * Get Credite Card Exception
     *
     * @return string
     */

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
    /**
     * Get Customer Account Exception
     *
     * @return string
     */

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
    /**
     * Get Account Specific Exception
     *
     * @param array $exceptioncode
     * @return string
     */

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
    /**
     * Get Credit Card Specific Exception
     *
     * @param array $exceptioncode
     * @return string
     */
    public function getCreditCardSpecificException($exceptioncode)
    {
        return $this->_helper->getCreditCardSpecificexception($exceptioncode);
    }
    /**
     * Get Discount Rate
     *
     * @return string
     */

    public function getDiscountRate()
    {
        $discountamount=0;
        $quote = $this->_cart->getTotalsCache();
        if (isset($quote['discount'])) {
            $discountamount =  $quote['discount']->getData('value');
        }
        
        return $discountamount;
    }
    /**
     * Get Dynamic 3DS2 Enabled
     *
     * @return string
     */

    public function isDynamic3DS2Enabled()
    {
        return $this->_helper->isDynamic3DS2Enabled();
    }
    /**
     * Get Jwt Event Url
     *
     * @return string
     */

    public function getJwtEventUrl()
    {
        return $this->_helper->getJwtEventUrl();
    }
    /**
     * Get Session Id
     *
     * @return string
     */

    public function getSessionId()
    {
        return $this->session->getSessionId();
    }
    /**
     * Get 3Ds Enabled
     *
     * @return string
     */

    public function is3DsEnabled()
    {
        return $this->_helper->is3DSecureEnabled() || $this->_helper->isDynamic3DEnabled();
    }
    /**
     * Get Message Manager
     *
     * @return string
     */

    public function getMessageManager()
    {
        return $this->_messageManager;
    }

    /**
     * Get ServiceWorkerUrl
     *
     * @return string
     */

    public function getServiceWorkerUrl()
    {
        return  $this->_assetRepo->getUrl("Sapient_Worldpay::chromepay/sw.js");
    }

    /**
     * Get ServiceWorkerScope
     *
     * @return string
     */

    public function getServiceWorkerScope()
    {
        return  $this->_assetRepo->getUrl("Sapient_Worldpay::chromepay");
    }
}
