<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Api;
 
interface UpdateRecurringShipmentInterface
{
    /**
     * Update Recurring Shipment
     *
     * @api
     * @param mixed $shipmentData
     * @return string
     */
    public function updateRecurringShipment($shipmentData) : string;
}
