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
    public const REFUND_FAILED_COMMENT  = 'The attempted refund request FAILED.';
    public const REFUND_EXPIRED_COMMENT = 'The attempted refund request EXPIRED.';

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
     * @param \Sapient\Worldpay\Model\Order $order
     */
    public function apply($payment, $order = null)
    {
        $paymentStatus = $this->_paymentState->getPaymentStatus();
        $reference = $this->_paymentState->getJournalReference(
            $this->_paymentState->getPaymentStatus()
        );

        if ($paymentStatus == \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_REFUND_EXPIRED) {
            $message = self::REFUND_EXPIRED_COMMENT;
        } else {
            $message = 'The refund attempt failed.';
        }

        $order->cancelRefund($reference, __($message));
        $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
    }
}
