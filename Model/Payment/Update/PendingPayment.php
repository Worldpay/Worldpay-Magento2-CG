<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment\Update;

use \Sapient\Worldpay\Model\Payment\UpdateInterface;

class PendingPayment extends \Sapient\Worldpay\Model\Payment\Update\Base implements UpdateInterface
{
    /** @var \Sapient\Worldpay\Helper\Data */
    private $_configHelper;
     /**
      * @var \Sapient\Worldpay\Helper\Multishipping
      */
    protected $multishippingHelper;
    /**
     * Constructor
     * @param \Sapient\Worldpay\Model\Payment\StateInterface $paymentState
     * @param \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment
     * @param \Sapient\Worldpay\Helper\Data $configHelper
     * @param \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
     */
    public function __construct(
        \Sapient\Worldpay\Model\Payment\StateInterface $paymentState,
        \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment,
        \Sapient\Worldpay\Helper\Data $configHelper,
        \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
    ) {
        $this->_paymentState = $paymentState;
        $this->_worldPayPayment = $worldPayPayment;
        $this->_configHelper = $configHelper;
        $this->multishippingHelper = $multishippingHelper;
    }

    /**
     * Apply
     *
     * @param Payment $payment
     * @param Order $order
     */
    public function apply($payment, $order = null)
    {
        if (!empty($order)) {
            $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
            $order->pendingPayment();
            $worldpaypayment = $order->getWorldPayPayment();
            if ($worldpaypayment->getIsMultishippingOrder()) {
                $this->multishippingHelper->pendingMultishippingOrders($order);
            }
        }
    }
}
