<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment\Update;

use Sapient\Worldpay\Helper\Data;
use Sapient\Worldpay\Model\Payment\StateInterface;
use Sapient\Worldpay\Model\Payment\UpdateInterface;
use Sapient\Worldpay\Model\Payment\WorldPayPayment;

class RefundFailed extends \Sapient\Worldpay\Model\Payment\Update\Base implements UpdateInterface
{
    /** @var Data */
    private $_configHelper;
    public const REFUND_FAILED_COMMENT  = 'The attempted refund request FAILED.';
    public const REFUND_EXPIRED_COMMENT = 'The attempted refund request EXPIRED.';

    /**
     * Constructor
     * @param StateInterface $paymentState
     * @param WorldPayPayment $worldPayPayment
     * @param Data $configHelper
     */
    public function __construct(
        StateInterface $paymentState,
        WorldPayPayment $worldPayPayment,
        Data $configHelper
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

        if ($paymentStatus == StateInterface::STATUS_REFUND_EXPIRED) {
            $message = self::REFUND_EXPIRED_COMMENT;
        } else {
            $message = self::REFUND_FAILED_COMMENT;
        }

        $order->cancelRefund($reference, __($message));
        $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
    }
}
