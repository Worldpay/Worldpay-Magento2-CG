<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

interface UpdateInterface
{
    /**
     * Apply
     *
     * @param Payment $payment
     */
    public function apply($payment);
    /**
     * Get target order code
     *
     * @return string ordercode
     */
    public function getTargetOrderCode();
}
