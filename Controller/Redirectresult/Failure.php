<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

/**
 * Redirect to the cart Page if order is failed
 */
class Failure extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
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
        if (!$this->orderservice->getAuthorisedOrder()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
        }
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
