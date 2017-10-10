<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
 
class Failure extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    public function __construct(Context $context, PageFactory $pageFactory,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
         \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) { 
        $this->pageFactory = $pageFactory;
        $this->orderservice = $orderservice;
        $this->wplogger = $wplogger;
        return parent::__construct($context);

    }
 
    public function execute()
    {
        $this->wplogger->info('worldpay returned failure url');
        $order = $this->orderservice->getAuthorisedOrder();
        $magentoorder = $order->getOrder();
        $notice = $this->_getFailureNoticeForOrder($magentoorder);
        $this->messageManager->addNotice($notice);
        return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
    }

    private function _getFailureNoticeForOrder($order)
    {
        return __('Order #'.$order->getIncrementId().' Failed');
    }    
}