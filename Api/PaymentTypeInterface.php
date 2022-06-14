<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Api;
 
interface PaymentTypeInterface
{
    /**
     * Retrive Payment Types
     *
     * @api
     * @param string $countryId
     * @return null|string
     */
    public function getPaymentType($countryId);
}
