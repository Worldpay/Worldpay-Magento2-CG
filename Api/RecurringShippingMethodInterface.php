<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Api;
 
interface RecurringShippingMethodInterface
{
    /**
     * Retrive Shipping Method
     *
     * @api
     * @param string $orderIncrementId
     * @param string $addressId
     * @return null|string
     */
    public function getShippingMethod($orderIncrementId, $addressId);
}
