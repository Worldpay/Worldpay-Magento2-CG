<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Model\Payment\Update;

use Exception;
use \Magento\Framework\Exception\LocalizedException;

class Base
{

    /** @var \Sapient\Worldpay\Model\Payment\StateInterface */
    protected $_paymentState;

    /** @var \Sapient\Worldpay\Model\Payment\WorldPayPayment */
    protected $_worldPayPayment;

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_CLOSED = 'closed';

    /**
     * Constructor
     * @param \Sapient\Worldpay\Model\Payment\StateInterface $paymentState
     * @param \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment
     */
    public function __construct(
        \Sapient\Worldpay\Model\Payment\StateInterface $paymentState,
        \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment
    ) {

        $this->_paymentState = $paymentState;
        $this->_worldPayPayment = $worldPayPayment;
    }

    /**
     * Get target order code
     *
     * @return string ordercode
     */
    public function getTargetOrderCode()
    {
        return $this->_paymentState->getOrderCode();
    }

    /**
     * Check payment Status
     *
     * @param object $order
     * @param array $allowedPaymentStatuses
     * @return null
     * @throws Exception
     */
    protected function _assertValidPaymentStatusTransition($order, $allowedPaymentStatuses)
    {
        $this->_assertPaymentExists($order);
        $existingPaymentStatus = $order->getPaymentStatus();
        $newPaymentStatus = $this->_paymentState->getPaymentStatus();

        if (in_array($existingPaymentStatus, $allowedPaymentStatuses)) {
            return;
        }

        if ($existingPaymentStatus == $newPaymentStatus) {
            throw new \Magento\Framework\Exception\LocalizedException(__('same state'));
        }

        throw new \Magento\Framework\Exception\LocalizedException(__('invalid state transition'));
    }

    /**
     * Check if order is not placed throgh worldpay payment
     *
     * @param Order $order
     * @throws Exception
     */
    private function _assertPaymentExists($order)
    {
        if (!$order->hasWorldPayPayment()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('No payment'));
        }
    }

    /**
     * Convert worldpay amount to magento amount
     *
     * @param float $amount
     * @return int
     */
    protected function _amountAsInt($amount)
    {
        return round($amount, 2, PHP_ROUND_HALF_EVEN) / pow(10, 2);
    }
}
