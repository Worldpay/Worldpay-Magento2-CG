<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Api;
 
interface CustomerPaymentTokenInterface
{
    /**
     * Get All Payment tokens
     *
     * @api
     * @return mixed
     */
    public function getAllPaymentTokens();
}
