<?php
//error_reporting(0);
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Samsungpay;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    public const SAMSUMG_CONFIG_PATH = "worldpay/multishipping/ms_wallets_config/ms_samsung_pay_wallets_config/";
    /**
     * @var $quoteFactory
     */
    protected $quoteFactory;
    /**
     * @var $_storeManager
     */
    protected $_storeManager;
    /**
     * @var $curlHelper
     */
    protected $curlHelper;
    /**
     * @var $quoteIdMaskFactory
     */
    public $quoteIdMaskFactory;
     /**
      * @var $customerSession
      */
    public $customerSession;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    public $wplogger;

    /**
     * @var \Sapient\Worldpay\Model\Payment\Service
     */
    public $paymentservice;

     /**
      * @var JsonFactory
      */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

     /**
      * @var \Magento\Framework\App\Request\Http
      */
    protected $request;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayHelper;
    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Sapient\Worldpay\Helper\CurlHelper $curlHelper
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Sapient\Worldpay\Helper\CurlHelper $curlHelper,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Sapient\Worldpay\Helper\Data $worldpayHelper
    ) {
        parent::__construct($context);
        $this->wplogger = $wplogger;
        $this->paymentservice = $paymentservice;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->quoteFactory = $quoteFactory;
        $this->_storeManager = $storeManager;
        $this->curlHelper = $curlHelper;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->customerSession=$customerSession;
        $this->worldpayHelper = $worldpayHelper;
    }
    /**
     * Execute
     *
     * @return string
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->worldpayHelper->isWorldPayEnable()) {
            $resultRedirect->setPath('noroute');
             return $resultRedirect;
         }
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        
        $serviceId = $this->scopeConfig->
                getValue('worldpay/wallets_config/samsung_pay_wallets_config/service_id', $storeScope);
        
        $shopName = $this->scopeConfig->
                getValue('worldpay/wallets_config/samsung_pay_wallets_config/samsung_merchant_shop_name', $storeScope);
      
        $shopUrl = $this->scopeConfig->
                getValue('worldpay/wallets_config/samsung_pay_wallets_config/samsung_merchant_shop_url', $storeScope);
        
        $environmentMode = $this->scopeConfig->
                getValue('worldpay/general_config/environment_mode', $storeScope);
        
        $quoteId = $this->request->getParam('quoteId');
        
        if ($environmentMode == 'Test Mode') {
            $serviceUrl = "https://api-ops.stg.mpay.samsung.com/ops/v1/transactions";
        } else {
            $serviceUrl = "https://api-ops.mpay.samsung.com/ops/v1/transactions";
        }
        
        $baseUrl =  $this->_storeManager->getStore()->getBaseUrl();
        
        if (!$this->customerSession->isLoggedIn()) {
            $quoteIdMask = $this->quoteIdMaskFactory->create();
            $quoteIdMask->load($quoteId, 'masked_id');
            $quoteId = $quoteIdMask->getQuoteId();
        }

         $quote = $this->quoteFactory->create()->load($quoteId);
         $quoteData = $quote->getData();
         $currency = $quote->getQuoteCurrencyCode();
         $grandTotal =  $quote->getGrandTotal();
         $postFields = [];
         
        /** Multishipping Samsung Pay Configuration */
        if ($quote->getIsMultiShipping()) {
            $msServiceId = $this->scopeConfig->
                getValue(self::SAMSUMG_CONFIG_PATH.'ms_service_id', $storeScope);
        
            $msShopName = $this->scopeConfig->
                    getValue(self::SAMSUMG_CONFIG_PATH.'ms_samsung_merchant_shop_name', $storeScope);
        
            $msShopUrl = $this->scopeConfig->
                    getValue(self::SAMSUMG_CONFIG_PATH.'ms_samsung_merchant_shop_url', $storeScope);
            
            $serviceId = !empty($msServiceId) ? $msServiceId : $serviceId;
            $shopName = !empty($msShopName) ? $msShopName : $shopName;
            $shopUrl = !empty($msShopUrl) ? $msShopUrl : $shopUrl;
        }

         $callBack = $baseUrl . 'worldpay/samsungpay/CallBack';
         $exponent = $this->worldpayHelper->getCurrencyExponent($currency);
         $postFields['callback'] = $callBack;
         $postFields['paymentDetails']['service']['id'] = $serviceId;
         $postFields['paymentDetails']['orderNumber'] = 'sp-'.time();
         $postFields['paymentDetails']['recurring'] = false;
         $postFields['paymentDetails']['protocol']['type'] = '3DS';
         $postFields['paymentDetails']['protocol']['version'] = "80";
         $postFields['paymentDetails']['amount']['option'] = 'FORMAT_TOTAL_ESTIMATED_AMOUNT';
         $postFields['paymentDetails']['amount']['currency'] = $currency;
         $postFields['paymentDetails']['amount']['total'] = $this->_amountAsInt($grandTotal, $exponent);
         $postFields['paymentDetails']['merchant']['name'] = $shopName;
         $postFields['paymentDetails']['merchant']['url'] = $shopUrl;
         $postFields['paymentDetails']['merchant']['reference'] = 'ref-'.time();
         $postFields['paymentDetails']['allowedBrands'] = ['VI', 'MC'];
             
        $postFieldsJson = (json_encode($postFields));
              
        try {
            
            $response = $this->curlHelper->sendCurlRequest(
                $serviceUrl,
                [
                    CURLOPT_URL => $serviceUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS =>$postFieldsJson,
                    CURLOPT_HTTPHEADER => [
                      "Content-Type: application/json"
                    ],
                ]
            );
            
                $resultJson = '';
                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $resultJson->setData($response);
                return $resultJson;
      
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
           
        }
    }
    /**
     * Returns the rounded value of num to specified precision
     *
     * @param float $amount
     * @param float $exponent
     * @return int
     */
    private function _amountAsInt($amount, $exponent)
    {
        return round($amount, $exponent, PHP_ROUND_HALF_EVEN);
    }
}
