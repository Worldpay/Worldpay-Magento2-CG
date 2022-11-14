<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment\Update;

use \Sapient\Worldpay\Model\Payment\UpdateInterface;

class Refused extends \Sapient\Worldpay\Model\Payment\Update\Base implements UpdateInterface
{
    /** @var \Sapient\Worldpay\Helper\Data */
    private $_configHelper;
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
            $this->_assertValidPaymentStatusTransition($order, $this->_getAllowedPaymentStatuses());
            $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
            $order->cancel();
            $worldpaypayment = $order->getWorldPayPayment();
            $worldpaypayment->getIsMultishippingOrder();
            if ($worldpaypayment->getIsMultishippingOrder()) {
                $this->multishippingHelper->cancelMultishippingOrders($order);
            }
        }
    }
    
    /**
     * Get allow payment status
     *
     * @return array
     */
    protected function _getAllowedPaymentStatuses()
    {
        return [
            \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SENT_FOR_AUTHORISATION,
            \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_AUTHORISED
        ];
    }
}
