<?php
declare(strict_types=1);

namespace Sapient\Worldpay\Model\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Sapient\Worldpay\Model\WorldpaymentFactory;

/**
 * Class AddExtraDataToTransport
 * @package Sapient\Worldpay\Model\Observer
 */
class AddExtraDataToTransport implements ObserverInterface
{
    /**
     * @var WorldpaymentFactory
     */
    protected $worldpayPayment;

    /**
     * AddExtraDataToTransport constructor.
     * @param WorldpaymentFactory $worldpaypayment
     */
    public function __construct(
        WorldpaymentFactory $worldpayPayment
    ) {
        $this->worldpayPayment = $worldpayPayment;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $allowedPaymentMethods = [
            "worldpay_apm",
            "worldpay_cc",
            "worldpay_cc_vault",
            "worldpay_moto",
            "worldpay_wallets"
        ];
        $transport = $observer->getEvent()->getTransport();
        $order = $transport['order'];
        $paymentCode = $order->getPayment()->getMethod();
        if (in_array($paymentCode, $allowedPaymentMethods)) {
            $paymentMethod = $this->data->getPaymentTitleForOrders($order, $paymentCode, $this->worldpaypayment);
            if ($paymentMethod) {
                $transport['payment_html'] = $paymentMethod;
            }
        }
    }
}
