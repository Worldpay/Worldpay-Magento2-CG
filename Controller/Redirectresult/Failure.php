<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;
use Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory;

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
     * @var TransactionsFactory
     */
    private $transactionsFactory;

     /**
      * @var \Sapient\Worldpay\Model\Order\Service
      */
    private $orderservice;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

       /**
        * @var \Magento\Checkout\Model\Session
        */
    protected $checkoutSession;

   /**
    * @var \Sapient\Worldpay\Helper\Multishipping
    */
    protected $multishippingHelper;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $emailsender;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \SubscriptionFactory $subscriptionFactory
     * @param \TransactionsFactory $transactionsFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $emailsender
     * @param \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        SubscriptionFactory $subscriptionFactory,
        TransactionsFactory $transactionsFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $emailsender,
        \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
    ) {
        $this->pageFactory = $pageFactory;
        $this->orderservice = $orderservice;
        $this->wplogger = $wplogger;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->transactionsFactory = $transactionsFactory;
        $this->checkoutSession = $checkoutSession;
        $this->emailsender = $emailsender;
        $this->multishippingHelper = $multishippingHelper;
        return parent::__construct($context);
    }
    /**
     * Execute
     *
     * @return string
     */
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
        if ($reservedOrder->getIncrementId()) {
                $subscription = $this->subscriptionFactory
                ->create()
                ->loadByOrderId($reservedOrder->getIncrementId());
            if ($subscription) {
                $subscription->delete();
            }
                $transactions = $this->transactionsFactory
                ->create()
                ->loadByOrderIncrementId($reservedOrder->getIncrementId());
            if ($transactions) {
                $transactions->delete();
            }
        }
        // send Payment Fail Email
        $this->emailsender->authorisedEmailSend($magentoorder, false);
        $this->multishippingHelper->performMultishippingMessage($order, 'failure');
        return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
    }
    /**
     * Get Failure NoticeFor Order
     *
     * @param array $order
     * @return string
     */

    private function _getFailureNoticeForOrder($order)
    {
        return __('Order #'.$order->getIncrementId().' Failed');
    }
}
