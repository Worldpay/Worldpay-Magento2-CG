<?php
/**
 * @copyright 2018 Sapient
 */
namespace Sapient\Worldpay\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddExtraDataToTransport implements ObserverInterface
{
    /**
     * @var worldpaypayment
     */
    protected $worldpaypayment;
    /**
     * @var wpHelper
     */
    protected $wpHelper;
    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment
     * @param \Sapient\Worldpay\Helper\Data $wpHelper
     */
      
    public function __construct(
        \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment,
        \Sapient\Worldpay\Helper\Data $wpHelper
    ) {
        $this->worldpaypayment = $worldpaypayment;
        $this->wpHelper = $wpHelper;
    }
   /**
    * Execute
    *
    * @param \Magento\Framework\Event\Observer $observer
    * @return string
    */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transport = $observer->getEvent()->getTransport();
        // Order info
        $order = $transport['order'];
        $paymentCode = $order->getPayment()->getMethod();
        $allowedPaymentMethods = $this->wpHelper->getWpPaymentMethods();
        if (in_array($paymentCode, $allowedPaymentMethods)) {
            // Full payment method name
            $paymentMethod = $this->wpHelper->getPaymentTitleForOrders($order, $paymentCode, $this->worldpaypayment);
            if ($paymentMethod) {
                $transport['payment_html'] = $paymentMethod;
            }
        }
    }
}
