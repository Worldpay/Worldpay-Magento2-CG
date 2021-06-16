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
	$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/3ds.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
	
	$logger->info('Step 1.5 -entered AuthResponse.php');
        $logger->info('cookie value before isset -in AuthResponse.php ');

         if(isset($_COOKIE['PHPSESSID'])){
          $phpsessId = $_COOKIE['PHPSESSID'];
          $domain = parse_url($this->_url->getUrl(), PHP_URL_HOST);
          setcookie("PHPSESSID", $phpsessId, [
         'expires' => time() + 86400,
         'path' => '/',
         'domain' => $domain,
         'secure' => true,
         'httponly' => true,
         'samesite' => 'None',
          ]);
        }

      
	
	$logger->info('Before $directOrderParams - AuthResponse.php');
        
        $directOrderParams = $this->checkoutSession->getDirectOrderParams();
        
	$logger->info('After $directOrderParams - AuthResponse.php --'.print_r($directOrderParams['orderCode'],true));
	$logger->info('Before $threeDSecureParams - AuthResponse.php--'.print_r($directOrderParams['orderCode'],true));
        
        $threeDSecureParams = $this->checkoutSession->get3DSecureParams();
        
	$logger->info('After $threeDSecureParams--'.print_r($directOrderParams['orderCode'],true));
	$logger->info('Before unsDirectOrderParams() - AuthResponse.php--'.print_r($directOrderParams['orderCode'],true));
        
        $this->checkoutSession->unsDirectOrderParams();
	$logger->info('After unsDirectOrderParams() - AuthResponse.php--'.print_r($directOrderParams['orderCode'],true));
	$logger->info('Before uns3DSecureParams() - AuthResponse.php--'.print_r($directOrderParams['orderCode'],true));
        
        $this->checkoutSession->uns3DSecureParams();
        
	$logger->info('After uns3DSecureParams() - AuthResponse.php--'.print_r($directOrderParams['orderCode'],true));
        try {
            $logger->info('Step2 - Logging the action before forming the PARes--'.print_r($directOrderParams['orderCode'],true));
            $logger->info('Pay response --'.print_r($this->getRequest()->getParam('PaRes'),true));
            $logger->info('Direct order code --'.print_r($directOrderParams['orderCode'],true));
	    
            $this->threedsredirectresponse->continuePost3dSecureAuthorizationProcess(
                $this->getRequest()->getParam('PaRes'),
                $directOrderParams,
                $threeDSecureParams
            );
        } catch (\Exception $e) {
            $logger->info('Before $this->wplogger->error($e->getMessage()) - AuthResponse.php--'.print_r($directOrderParams['orderCode'],true));
            $logger->info($e->getMessage() . ' - AuthResponse.php');
	    
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
                'savedcard/addnewcard', ['_secure' => true]));
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
}
