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
     * @param Integer $order OrderId.
     * @return json 
     */
    public function getHistory($order);
}