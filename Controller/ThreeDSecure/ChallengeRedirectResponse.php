<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\ThreeDSecure;

class ChallengeRedirectResponse extends \Magento\Framework\App\Action\Action
{
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
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
        
    ) {
        $this->wplogger = $wplogger;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->urlBuilder = $context->getUrl();
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->threedscredirectresponse = $threedcredirectresponse;
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Accepts callback from worldpay's 3DS2 challenge iframe page.
     */
    public function execute()
    { 
        $resultPage = $this->_resultPageFactory->create();
                $block = $resultPage->getLayout()
                ->createBlock('Sapient\Worldpay\Block\Checkout\Hpp\ChallengeIframe')
                ->setTemplate('Sapient_Worldpay::checkout/hpp/challengeiframe.phtml')
                ->toHtml();
                $this->getResponse()->setBody($block);
                return;
    }
}
