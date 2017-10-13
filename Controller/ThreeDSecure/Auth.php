<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\ThreeDSecure;

use Magento\Framework\App\Action\Context;
use Exception;

class Auth extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;
    
    public function __construct(Context $context,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->wplogger = $wplogger;
        $this->urlBuilders    = $urlBuilder;
        $this->checkoutSession = $checkoutSession;
        $this->_messageManager = $messageManager;
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($redirectData = $this->checkoutSession->get3DSecureParams()) {
            echo '
                <form name="theForm" id="form" method="POST" action='.$redirectData->getUrl().'>
                    <input type="hidden" name="PaReq" value='.$redirectData->getPaRequest().' />
                    <input type="hidden" name="TermUrl" value='.$this->urlBuilders->getUrl('worldpay/threedsecure/authresponse', ['_secure' => true]).' />
                </form>';
            echo '
                <script language="Javascript">
                    document.getElementById("form").submit();
                </script>';
        }else if ($this->checkoutSession->getThreeDSEnabledWithError()) {
            $this->checkoutSession->unsThreeDSEnabledWithError();
            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success', ['_current' => true]);
        } else {
            $this->_messageManager->addError(__('Unfortunately the order could not be processed. Please contact us or try again later.'));
            $this->getResponse()->setRedirect($this->urlBuilders->getUrl('checkout/cart', ['_secure' => true]));
        }
    }
}