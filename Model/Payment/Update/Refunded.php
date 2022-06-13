<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment\Update;

use \Sapient\Worldpay\Model\Payment\UpdateInterface;

class Refunded extends \Sapient\Worldpay\Model\Payment\Update\Base implements UpdateInterface
{
    /** @var \Sapient\Worldpay\Helper\Data */
    private $_configHelper;
    public const REFUND_COMMENT = 'Refund request PROCESSED by the bank.';
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
        $reference = $this->_paymentState->getJournalReference(
            \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_REFUNDED
        );
        $message = self::REFUND_COMMENT . ' Reference: ' . $reference;
        $order->refund($reference, $message);
        $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
    }
}
