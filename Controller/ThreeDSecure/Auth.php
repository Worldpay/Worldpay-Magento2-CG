<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\ThreeDSecure;

use Magento\Framework\App\Action\Context;
use Exception;

class Auth extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession                 
     */
    public function __construct(Context $context,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->wplogger = $wplogger;
        $this->checkoutSession = $checkoutSession;      
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Renders the 3D Secure  page, responsible for forwarding
     * all necessary order data to worldpay.
     */
    public function execute()
    {
        if ($redirectData = $this->checkoutSession->get3DSecureParams()) {
            echo '
                <form name="theForm" id="form" method="POST" action='.$redirectData->getUrl().'>
                    <input type="hidden" name="PaReq" value='.$redirectData->getPaRequest().' />
                    <input type="hidden" name="TermUrl" value='.$this->_url->getUrl('worldpay/threedsecure/authresponse', ['_secure' => true]).' />
                </form>';
            echo '
                <script language="Javascript">
                    document.getElementById("form").submit();
                </script>';
        } else if ($this->checkoutSession->getThreeDSEnabledWithError()) {
            $this->checkoutSession->unsThreeDSEnabledWithError();
            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success', ['_current' => true]);
        } else {
            $this->messageManager->addError(__('Unfortunately the order could not be processed. Please contact us or try again later.'));
            $this->getResponse()->setRedirect($this->_url->getUrl('checkout/cart', ['_secure' => true]));
        }
    }
}
