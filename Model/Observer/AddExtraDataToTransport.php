<?php
/**
 * @copyright 2018 Sapient
 */
namespace Sapient\Worldpay\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddExtraDataToTransport implements ObserverInterface
{

    protected $worldpaypayment;

    public function __construct(
        \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment
    ) {
        $this->worldpaypayment = $worldpaypayment;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transport = $observer->getEvent()->getTransport();
        // Order info
        $order = $transport['order'];
        $paymentCode = $order->getPayment()->getMethod();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->get(\Sapient\Worldpay\Helper\Data::class);
        // Full payment method name
        $paymentMethod = $helper->getPaymentTitleForOrders($order, $paymentCode, $this->worldpaypayment);
        if ($paymentMethod) {
            $transport['payment_html'] = $paymentMethod;
        }
    }
}
