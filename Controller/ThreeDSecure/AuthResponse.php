<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\ThreeDSecure;

use Sapient\Worldpay\Helper\CreditCardException;

class AuthResponse extends \Magento\Framework\App\Action\Action
{

    /**
     * @var CreditCardException
     */
    protected $helper;
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Sapient\Worldpay\Model\Authorisation\ThreeDSecureService $threedsredirectresponse
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Sapient\Worldpay\Model\Authorisation\ThreeDSecureService $threedsredirectresponse,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        CreditCardException $helper
    ) {
        $this->wplogger = $wplogger;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->urlBuilder = $context->getUrl();
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->threedsredirectresponse = $threedsredirectresponse;
        $this->urlBuilders    = $urlBuilder;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Accepts callback from worldpay's 3D Secure page. If payment has been
     * authorised, update order and redirect to the checkout success page.
     */
    public function execute()
    {
        $directOrderParams = $this->checkoutSession->getDirectOrderParams();
        $threeDSecureParams = $this->checkoutSession->get3DSecureParams();
        $skipSameSiteForIOs = $this->shouldSkipSameSiteNone($directOrderParams);
        
        if($skipSameSiteForIOs) {
          if(isset($_COOKIE['PHPSESSID'])){
          $phpsessId = $_COOKIE['PHPSESSID'];
          $domain = parse_url($this->_url->getUrl(), PHP_URL_HOST);
          setcookie("PHPSESSID", $phpsessId, [
         'expires' => time() + 3600,
         'path' => '/',
         'domain' => $domain,
         'secure' => true,
         'httponly' => true,
          ]);
        }
            
        }else {
        if(isset($_COOKIE['PHPSESSID'])){
          $phpsessId = $_COOKIE['PHPSESSID'];
          $domain = parse_url($this->_url->getUrl(), PHP_URL_HOST);
          setcookie("PHPSESSID", $phpsessId, [
         'expires' => time() + 3600,
         'path' => '/',
         'domain' => $domain,
         'secure' => true,
         'httponly' => true,
         'samesite' => 'None',
          ]);
        }
        }
        $this->checkoutSession->unsDirectOrderParams();
        $this->checkoutSession->uns3DSecureParams();
        try {
            $this->threedsredirectresponse->continuePost3dSecureAuthorizationProcess(
                $this->getRequest()->getParam('PaRes'),
                $directOrderParams,
                $threeDSecureParams
            );
        } catch (\Exception $e) {
            $this->wplogger->error($e->getMessage());
            $this->wplogger->error('3DS Failed');
            $this->messageManager->addError(__($this->helper->getConfigValue('CCAM9')));
            if ($this->checkoutSession->getInstantPurchaseOrder()) {
                $redirectUrl = $this->checkoutSession->getInstantPurchaseRedirectUrl();
                $this->checkoutSession->unsInstantPurchaseRedirectUrl();
                $this->checkoutSession->unsInstantPurchaseOrder();
                return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
            } elseif ($this->checkoutSession->getIavCall()) {
                $this->checkoutSession->unsIavCall();
                $this->getResponse()->setRedirect($this->urlBuilders->getUrl(
                    'savedcard/addnewcard',
                    ['_secure' => true]
                ));
            } else {
                $this->getResponse()->setRedirect($this->urlBuilders->getUrl('checkout/cart', ['_secure' => true]));
            }
        }
        if ($this->checkoutSession->getInstantPurchaseOrder()) {
            $redirectUrl = $this->checkoutSession->getInstantPurchaseRedirectUrl();
            $this->checkoutSession->unsInstantPurchaseRedirectUrl();
            $this->checkoutSession->unsInstantPurchaseOrder();
            $message=$this->checkoutSession->getInstantPurchaseMessage();
            if ($message) {
                $this->checkoutSession->unsInstantPurchaseMessage();
                $this->messageManager->addSuccessMessage($message);
            }
            return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
        } elseif ($this->checkoutSession->getIavCall()) {
            $this->checkoutSession->unsIavCall();
            $this->getResponse()->setRedirect($this->urlBuilders->getUrl('worldpay/savedcard', ['_secure' => true]));
        } else {
            $redirectUrl = $this->checkoutSession->getWpResponseForwardUrl();
            $this->checkoutSession->unsWpResponseForwardUrl();
            $this->getResponse()->setRedirect($redirectUrl);
        }
    }
    
     private function shouldSkipSameSiteNone($directOrderParams)
    {
         if(isset($directOrderParams)) {
         $useragent = $directOrderParams['userAgentHeader'] ;
           $iosDeviceRegex = "/\(iP.+; CPU .*OS (\d+)[_\d]*.*\) AppleWebKit\//";
           $macDeviceRegex = "/\(Macintosh;.*Mac OS X (\d+)_(\d+)[_\d]*.*\) AppleWebKit\//";
           $iosVersionRegex = '/OS 12./';
           $macVersionRegex ='/OS X 10./';
           $macLatestVersionRegex = '/OS X 10_15_7/';
           if (preg_match($iosDeviceRegex,$useragent) && preg_match($iosVersionRegex,$useragent) ) {
               $this->wplogger->info('Passed regex check for ios');
              return true; 
           }elseif ((preg_match($macDeviceRegex,$useragent) && preg_match($macVersionRegex,$useragent)) 
                   &&(!preg_match($macLatestVersionRegex,$useragent))) {
              $this->wplogger->info('Passed regex check for mac'); 
              return true;
           }
           $this->wplogger->info(print_r($useragent,true));
           $this->wplogger->info('Outside regex check');
           return false;
         }
         return false;
    }
}
