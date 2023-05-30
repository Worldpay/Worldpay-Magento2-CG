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
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var string
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * @var \Sapient\Worldpay\Model\Authorisation\ThreeDSecureChallenge
     */
    protected $threedscredirectresponse;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilders;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $session;

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
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param CreditCardException $helper
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
     * Accepts callback from worldpay's 3DS2 Secure page
     */
    public function execute()
    {
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
                return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
            } elseif ($this->checkoutSession->getIavCall()) {
                $this->checkoutSession->unsIavCall();
                $this->getResponse()->setRedirect($this->urlBuilders->getUrl(
                    'worldpay/savedcard/addnewcard',
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
}
