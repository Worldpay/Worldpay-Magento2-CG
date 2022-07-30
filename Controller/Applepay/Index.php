<?php
//error_reporting(0);
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Applepay;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;
use Laminas\Uri\UriFactory;
use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var fileDriver
     */
    protected $fileDriver;

    /**
     * @var curlHelper
     */
    public $curlHelper;
  
    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Sapient\Worldpay\Helper\CurlHelper $curlHelper
     * @param \Magento\Framework\Filesystem\Driver\file $fileDriver
     */

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Sapient\Worldpay\Helper\CurlHelper $curlHelper,
        \Magento\Framework\Filesystem\Driver\file $fileDriver
    ) {
        parent::__construct($context);
        $this->wplogger = $wplogger;
        $this->paymentservice = $paymentservice;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->curlHelper = $curlHelper;
        $this->fileDriver = $fileDriver;
    }
    /**
     * Execute
     *
     * @return string
     */

    public function execute()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $certificateKey = $this->scopeConfig->getValue(
            'worldpay/wallets_config/apple_pay_wallets_config/certification_key',
            $storeScope
        );
        $certificateCrt = $this->scopeConfig->
                getValue('worldpay/wallets_config/apple_pay_wallets_config/certification_crt', $storeScope);
        $certificationPassword = $this->scopeConfig->
                getValue('worldpay/wallets_config/apple_pay_wallets_config/certification_password', $storeScope);
        $merchantName = $this->scopeConfig->
                getValue('worldpay/wallets_config/apple_pay_wallets_config/merchant_name', $storeScope);
        $domainName = $this->scopeConfig->
                getValue('worldpay/wallets_config/apple_pay_wallets_config/domain_name', $storeScope);
 
          $validation_url = $this->request->getParam('u');
         
        if ($validation_url == 'getTotal') {
             
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $cart = $objectManager->get(\Magento\Checkout\Model\Cart::class);

            $subTotal = $cart->getQuote()->getSubtotal();
            $grandTotal = $cart->getQuote()->getGrandTotal();
            
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($grandTotal);
            
            return $resultJson;
     
        }

        define('PRODUCTION_CERTIFICATE_KEY', $certificateKey);
        define('PRODUCTION_CERTIFICATE_PATH', $certificateCrt);

        define('PRODUCTION_CERTIFICATE_KEY_PASS', $certificationPassword);

        $prodCertContents = $this->fileDriver->fileGetContents(PRODUCTION_CERTIFICATE_PATH);
        /*define('PRODUCTION_MERCHANTIDENTIFIER', openssl_x509_parse(
            file_get_contents(PRODUCTION_CERTIFICATE_PATH)
        )['subject']['UID']);*/
        define('PRODUCTION_MERCHANTIDENTIFIER', openssl_x509_parse(
            $prodCertContents
        )['subject']['UID']);
        define('PRODUCTION_DOMAINNAME', $domainName);

        define('PRODUCTION_DISPLAYNAME', $domainName);

        try {
          
            $validation_url = $this->request->getParam('u');
            $urlInfo = UriFactory::factory($validation_url);
            
            if ("https" == $urlInfo->getScheme() && substr($urlInfo->getHost(), -10) == ".apple.com") {
                $data = '{"merchantIdentifier":"'.PRODUCTION_MERCHANTIDENTIFIER.'", '
                        . '"domainName":"'.PRODUCTION_DOMAINNAME.'", "displayName":"'.PRODUCTION_DISPLAYNAME.'"}';
                // create a new cURL resource
                $result = $this->curlHelper->sendCurlRequest(
                    $validation_url,
                    [
                        CURLOPT_URL=>$validation_url,
                        CURLOPT_SSLCERT=> PRODUCTION_CERTIFICATE_PATH,
                        CURLOPT_SSLKEY=>PRODUCTION_CERTIFICATE_KEY,
                        CURLOPT_SSLKEYPASSWD=>PRODUCTION_CERTIFICATE_KEY_PASS,
                        CURLOPT_POST=>1,
                        CURLOPT_POSTFIELDS=>$data,
                        CURLOPT_RETURNTRANSFER=>1
                    ]
                );
                $resultJson = '';
                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $resultJson->setData($result);
            
                 return $resultJson;

            }
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
           
        }
    }
}
