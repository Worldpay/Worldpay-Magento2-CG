<?php
/**
 * Update @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

interface UpdateInterface
{
    /**
     * Apply function interface
     *
     * @param string $payment
     */
    public function apply($payment);

/**
 * GetTargetOrderCode function interface
 */
    public function getTargetOrderCode();
}
