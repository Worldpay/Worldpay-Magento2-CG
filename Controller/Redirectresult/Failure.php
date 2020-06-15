<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;

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
     * @var SubscriptionFactory
     */
    private $subscriptionFactory;

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
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        SubscriptionFactory $subscriptionFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Sapient\Worldpay\Model\Recurring\Subscription\Transactions $recurringTransactions
    ) {
        $this->pageFactory = $pageFactory;
        $this->orderservice = $orderservice;
        $this->wplogger = $wplogger;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->transactionCollectionFactory = $recurringTransactions;
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
        $reservedOrder = $this->checkoutSession->getLastRealOrder();
        if($reservedOrder->getIncrementId()){
            $subscription = $this->subscriptionFactory
            ->create()
            ->loadByOrderId($reservedOrder->getIncrementId());
            if($subscription){
                $subscription->delete();
            }
            $transactions = $this->transactionCollectionFactory
            ->create()
            ->loadByOrderId($reservedOrder->getIncrementId());
            if($transactions){
                $transactions->delete();
            }
        }
        return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
    }

    private function _getFailureNoticeForOrder($order)
    {
        return __('Order #'.$order->getIncrementId().' Failed');
    }
}
