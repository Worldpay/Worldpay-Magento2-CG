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

class Index extends \Magento\Framework\App\Action\Action
{
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
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Model\HistoryNotificationFactory $historyNotification
     */
    public function __construct(Context $context,
        JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
        
    ) {
        parent::__construct($context);
        $this->wplogger = $wplogger;
        $this->paymentservice = $paymentservice;
        $this->resultJsonFactory = $resultJsonFactory;  
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $certificateKey = $this->scopeConfig->getValue('worldpay/wallets_config/apple_pay_wallets_config/certification_key', $storeScope);
        $certificateCrt = $this->scopeConfig->getValue('worldpay/wallets_config/apple_pay_wallets_config/certification_crt', $storeScope);
        $certificationPassword = $this->scopeConfig->getValue('worldpay/wallets_config/apple_pay_wallets_config/certification_password', $storeScope);
        $merchantName = $this->scopeConfig->getValue('worldpay/wallets_config/apple_pay_wallets_config/merchant_name', $storeScope);
        $domainName = $this->scopeConfig->getValue('worldpay/wallets_config/apple_pay_wallets_config/domain_name', $storeScope);
        
       // print_r($certificateKey);
        //exit;
        
        
         $validation_url = $_GET['u'];
         
         if($validation_url == 'getTotal') {
             
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 

            $subTotal = $cart->getQuote()->getSubtotal();
            $grandTotal = $cart->getQuote()->getGrandTotal();
            
            echo $grandTotal;

             
         }
        
        
        // update these with the real location of your two .pem files. keep them above/outside your webroot folder
        define('PRODUCTION_CERTIFICATE_KEY', $certificateKey);
        define('PRODUCTION_CERTIFICATE_PATH', $certificateCrt);

        // This is the password you were asked to create in terminal when you extracted ApplePay.key.pem
        define('PRODUCTION_CERTIFICATE_KEY_PASS', $certificationPassword);

        define('PRODUCTION_MERCHANTIDENTIFIER', openssl_x509_parse( file_get_contents( PRODUCTION_CERTIFICATE_PATH ))['subject']['UID'] ); //if you have a recent version of PHP, you can leave this line as-is. http://uk.php.net/openssl_x509_parse will parse your certificate and retrieve the relevant line of text from it e.g. merchant.com.name, merchant.com.mydomain or merchant.com.mydomain.shop
        // if the above line isn't working for you for some reason, comment it out and uncomment the next line instead, entering in your merchant identifier you created in your apple developer account
        //define('PRODUCTION_MERCHANTIDENTIFIER', $merchantName);

        //define('PRODUCTION_DOMAINNAME', $_SERVER["HTTP_HOST"]); //you can leave this line as-is too, it will take the domain from the server you run it on e.g. shop.mydomain.com or mydomain.com
        // if the line above isn't working for you, replace it with the one below, updating it for your own domain name
        define('PRODUCTION_DOMAINNAME', $domainName);


        //define('PRODUCTION_CURRENCYCODE', 'GBP');       // https://en.wikipedia.org/wiki/ISO_4217
        //define('PRODUCTION_COUNTRYCODE', 'GB');         // https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
        define('PRODUCTION_DISPLAYNAME', $domainName);

        define('DEBUG', 'true');
        try {
          
            //$validation_url = "https://apple-pay-gateway-cert.apple.com/paymentservices/startSession";
            $validation_url = $_GET['u'];


        if( "https" == parse_url($validation_url, PHP_URL_SCHEME) && substr( parse_url($validation_url, PHP_URL_HOST), -10 )  == ".apple.com" ){

                //require_once ('apple_pay_conf.php');

                // create a new cURL resource
                $ch = curl_init();

                $data = '{"merchantIdentifier":"'.PRODUCTION_MERCHANTIDENTIFIER.'", "domainName":"'.PRODUCTION_DOMAINNAME.'", "displayName":"'.PRODUCTION_DISPLAYNAME.'"}';

                curl_setopt($ch, CURLOPT_URL, $validation_url);
                curl_setopt($ch, CURLOPT_SSLCERT, PRODUCTION_CERTIFICATE_PATH);
                curl_setopt($ch, CURLOPT_SSLKEY, PRODUCTION_CERTIFICATE_KEY);
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, PRODUCTION_CERTIFICATE_KEY_PASS);
                //curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
                //curl_setopt($ch, CURLOPT_SSLVERSION, 'CURL_SSLVERSION_TLSv1_2');
                //curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'rsa_aes_128_gcm_sha_256,ecdhe_rsa_aes_128_gcm_sha_256');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                $result = curl_exec($ch);
                //var_dump($result);
                curl_close($ch);
                exit;

              

        }
                } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
           
        }
    }

   

   
    public function getGrandTotalAfterUpdate()
    {
      
    }
}
