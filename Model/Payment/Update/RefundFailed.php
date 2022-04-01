<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment\Update;

use \Sapient\Worldpay\Model\Payment\UpdateInterface;

class RefundFailed extends \Sapient\Worldpay\Model\Payment\Update\Base implements UpdateInterface
{
    /** @var \Sapient\Worldpay\Helper\Data */
    private $_configHelper;
    const REFUND_FAILED_COMMENT  = 'The attempted refund request FAILED.';
    const REFUND_EXPIRED_COMMENT = 'The attempted refund request EXPIRED.';
    /**
     * Constructor
     * @param \Sapient\Worldpay\Model\Payment\State $paymentState
     * @param \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment
     * @param \Sapient\Worldpay\Helper\Data $configHelper
     */
    public function __construct(
        \Sapient\Worldpay\Model\Payment\State $paymentState,
        \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment,
        \Sapient\Worldpay\Helper\Data $configHelper
    ) {
        $this->_paymentState = $paymentState;
        $this->_worldPayPayment = $worldPayPayment;
        $this->_configHelper = $configHelper;
    }

    public function apply($payment, $order = null)
    {
        $paymentStatus = $this->_paymentState->getPaymentStatus();
        $this->_reference = $this->_paymentState->getJournalReference(
            $this->_paymentState->getPaymentStatus()
        );

        if ($paymentStatus == \Sapient\Worldpay\Model\Payment\State::STATUS_REFUND_EXPIRED) {
            $this->_message = self::REFUND_EXPIRED_COMMENT;
        } else {
            $this->_message = self::REFUND_FAILED_COMMENT;
        }
        $this->_message .= ' Reference:' . $this->_reference;

        $order->cancelRefund($this->_reference, $this->_message);
        $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
    }
}
