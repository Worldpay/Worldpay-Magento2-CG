<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment\Update;

use Sapient\Worldpay\Model\Payment\StateInterface;
use \Sapient\Worldpay\Model\Payment\UpdateInterface;

class Cancelled extends \Sapient\Worldpay\Model\Payment\Update\Base implements UpdateInterface
{
    /** @var \Sapient\Worldpay\Helper\Data */
    private $_configHelper;

    /**
     * Constructor
     * @param \Sapient\Worldpay\Model\Payment\StateInterface $paymentState
     * @param \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment
     * @param \Sapient\Worldpay\Helper\Data $configHelper
     */
    public function __construct(
        \Sapient\Worldpay\Model\Payment\StateInterface $paymentState,
        \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment,
        \Sapient\Worldpay\Helper\Data $configHelper
    ) {
        $this->_paymentState = $paymentState;
        $this->_worldPayPayment = $worldPayPayment;
        $this->_configHelper = $configHelper;
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

            $payment = $order->getPayment();
            $method = $payment->getMethodInstance();
            $methodCode = $method->getCode();
            if ($methodCode == 'worldpay_paybylink') {
                $worldPayPayment = $this->_configHelper->getWorldpayPaymentModel()
                ->loadByPaymentId($order->getIncrementId());
                $isRedirectOrder = $worldPayPayment->getData('payment_model');
                $wpPaymentStatus = $worldPayPayment->getData('payment_status');
                if ($isRedirectOrder &&
                $wpPaymentStatus == StateInterface::STATUS_SENT_FOR_AUTHORISATION) {
                    $order->cancel()->save();
                    $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
                    return;
                }
            }

            $this->_assertValidPaymentStatusTransition($order, $this->_getAllowedPaymentStatuses());
            $order->cancel();
            $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
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
            StateInterface::STATUS_SENT_FOR_AUTHORISATION,
            StateInterface::STATUS_AUTHORISED,
            StateInterface::STATUS_CAPTURED,
            StateInterface::STATUS_WAITING_FOR_SHOPPER,
        ];
    }
}
