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

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * [$curlHelper description]
     * @var [type]
     */
    protected $curlHelper;
    
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
   
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
        \Sapient\Worldpay\Helper\CurlHelper $curlHelper
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
    }
    
    /**
     * Execute action
     *
     * @return string
     * @throws \Exception
     */
    public function execute()
    {
 
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        
        $serviceId = $this->scopeConfig->
                getValue('worldpay/wallets_config/samsung_pay_wallets_config/service_id', $storeScope);
        
        $shopName = $this->scopeConfig->
                getValue('worldpay/wallets_config/samsung_pay_wallets_config/samsung_merchant_shop_name', $storeScope);
      
        $shopUrl = $this->scopeConfig->
                getValue('worldpay/wallets_config/samsung_pay_wallets_config/samsung_merchant_shop_url', $storeScope);
        
        $environmentMode = $this->scopeConfig->
                getValue('worldpay/general_config/environment_mode', $storeScope);
        
        if ($environmentMode == 'Test Mode') {
            $serviceUrl = "https://api-ops.stg.mpay.samsung.com/ops/v1/transactions";
        } else {
            $serviceUrl = "https://api-ops.mpay.samsung.com/ops/v1/transactions";
        }
        
        $baseUrl =  $this->_storeManager->getStore()->getBaseUrl();
        
         $quoteId = $this->request->getParam('quoteId');
         $quote = $this->quoteFactory->create()->load($quoteId);
         $quoteData = $quote->getData();
         
         $currency = $quoteData['quote_currency_code'];
         $grandTotal = $quoteData['grand_total'];
        
         $postFields = [];
         
         $callBack = $baseUrl . 'worldpay/samsungpay/CallBack';
         
         $postFields['callback'] = $callBack;
         $postFields['paymentDetails']['service']['id'] = $serviceId;
         $postFields['paymentDetails']['orderNumber'] = 'sp-'.time();
         $postFields['paymentDetails']['recurring'] = false;
         $postFields['paymentDetails']['protocol']['type'] = '3DS';
         $postFields['paymentDetails']['protocol']['version'] = "80";
         $postFields['paymentDetails']['amount']['option'] = 'FORMAT_TOTAL_ESTIMATED_AMOUNT';
         $postFields['paymentDetails']['amount']['currency'] = $currency;
         $postFields['paymentDetails']['amount']['total'] = $grandTotal;
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
}
