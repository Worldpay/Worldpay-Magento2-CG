<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\ThreeDSecure;

use Sapient\Worldpay\Helper\CreditCardException;

class ChallengeAuthResponse extends \Magento\Framework\App\Action\Action
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
     * @param \Sapient\Worldpay\Model\Authorisation\ThreeDSecureChallenge $threedcredirectresponse
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Sapient\Worldpay\Model\Authorisation\ThreeDSecureChallenge $threedcredirectresponse,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\Session\SessionManagerInterface $session,
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
        $this->threedscredirectresponse = $threedcredirectresponse;
        $this->session = $session;
        $this->urlBuilders    = $urlBuilder;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Accepts callback from worldpay's 3DS2 Secure page. If payment has been
     * authorised, update order and redirect to the checkout success page.
     */
    public function execute()
    {
        if (isset($_COOKIE['PHPSESSID'])) {
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
        $directOrderParams = $this->checkoutSession->getDirectOrderParams();
        $threeDSecureParams = $this->checkoutSession->get3Ds2Params();
        $this->checkoutSession->unsDirectOrderParams();
        $this->checkoutSession->uns3Ds2Params();
        try {
            $this->threedscredirectresponse->continuePost3dSecure2AuthorizationProcess(
                $directOrderParams,
                $threeDSecureParams
            );
        } catch (\Exception $e) {
            $this->wplogger->error($e->getMessage());
            $this->wplogger->error('3DS2 Failed');
            if ($e->getMessage()=== 'Unique constraint violation found') {
                $this->messageManager
                        ->addError(__($this->paymentservicerequest
                                ->getCreditCardSpecificException('CCAM22')));
            } else {
                $this->messageManager->addError(__($this->helper->getConfigValue('CCAM9')));
            }
            if ($this->checkoutSession->getInstantPurchaseOrder()) {
                $redirectUrl = $this->checkoutSession->getInstantPurchaseRedirectUrl();
                $this->checkoutSession->unsInstantPurchaseRedirectUrl();
                $this->checkoutSession->unsInstantPurchaseOrder();
                //$this->getResponse()->setRedirect($redirectUrl);
                return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
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
        } else {
        $redirectUrl = $this->checkoutSession->getWpResponseForwardUrl();
        $this->checkoutSession->unsWpResponseForwardUrl();
        $this->getResponse()->setRedirect($redirectUrl);
	}
    }
}
