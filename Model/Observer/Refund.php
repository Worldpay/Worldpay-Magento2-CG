<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use Exception;

class Refund implements ObserverInterface
{
    /**
     * Constructor
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Framework\Pricing\Helper\Data $pricinghelper
     * @param \Magento\Checkout\Model\Session $checkoutsession
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\Pricing\Helper\Data $pricinghelper,
        \Magento\Checkout\Model\Session $checkoutsession
    ) {
        $this->wplogger = $wplogger;
        $this->checkoutsession = $checkoutsession;
        $this->pricinghelper = $pricinghelper;
    }

    /**
     * Process the credit memo
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $creditmemo = $observer->getEvent()->getCreditmemo();
        if ($payment->getMethod()=='worldpay_cc' || $payment->getMethod()=='worldpay_apm'
                || $payment->getMethod()=='worldpay_cc_vault') {
             $amount = $this->pricinghelper->currency($creditmemo->getGrandTotal(), true, false);
             $creditmemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);
             $creditmemo->setStatus(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);
             $creditmemo->addComment('Refund request sent, amount: ' . $amount, false, false);
        }
    }
}
