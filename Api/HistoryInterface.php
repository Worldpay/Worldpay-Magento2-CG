<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Api;
 
interface HistoryInterface
{
    /**
     * Retrive order Notification
     *
     * @api
     * @param integer $order OrderId.
     * @return string
     */
    public function getHistory($order);
}
