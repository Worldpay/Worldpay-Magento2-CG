<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Api;

interface PaypalInterface
{

    /**
     * Retrieves the PayPal Id for an order.
     *
     * @return string The PayPal order ID.
     */
    public function getPaypalOrderId(int $orderId): string;

    /**
     * Approve event
     *
     * @return string
     */
    public function approveOrder(int $orderId);
}
