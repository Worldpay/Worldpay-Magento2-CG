<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use Exception;

class Cart implements ObserverInterface
{
    /**
     * Constructor
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Model\Checkout\Service $checkoutservice
     * @param \Magento\Checkout\Model\Session $checkoutsession
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Sapient\Worldpay\Model\Checkout\Service $checkoutservice,
        \Magento\Checkout\Model\Session $checkoutsession
    ) {
        $this->orderservice = $orderservice;
        $this->wplogger = $wplogger;
        $this->checkoutservice = $checkoutservice;
        $this->checkoutsession = $checkoutsession;
    }

   /**
    * Load the shopping cart from the latest authorized, but not completed order
    *
    * @param \Magento\Framework\Event\Observer $observer
    */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->checkoutsession->getauthenticatedOrderId()) {
            $order = $this->orderservice->getAuthorisedOrder();
            $this->checkoutservice->reactivateQuoteForOrder($order);
            $this->orderservice->removeAuthorisedOrder();
        }
    }
}
