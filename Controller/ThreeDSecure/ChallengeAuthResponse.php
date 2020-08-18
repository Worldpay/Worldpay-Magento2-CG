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
        CreditCardException $helper
    ) {
        $this->wplogger = $wplogger;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->urlBuilder = $context->getUrl();
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->threedscredirectresponse = $threedcredirectresponse;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Accepts callback from worldpay's 3DS2 Secure page. If payment has been
     * authorised, update order and redirect to the checkout success page.
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
            $this->_messageManager->addError(__($this->helper->getConfigValue('CCAM9')));
            $this->getResponse()->setRedirect($this->urlBuilders->getUrl('checkout/cart', ['_secure' => true]));
        }
        $redirectUrl = $this->checkoutSession->getWpResponseForwardUrl();
        $this->checkoutSession->unsWpResponseForwardUrl();
        $this->getResponse()->setRedirect($redirectUrl);
    }
}
