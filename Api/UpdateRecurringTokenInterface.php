<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Api;
 
interface UpdateRecurringTokenInterface
{
    /**
     * Update Recurring Token
     *
     * @api
     * @param string $tokenId
     * @param string $subscriptionId
     *
     * @return string
     */
    public function updateRecurringPaymentToken(string $tokenId, string $subscriptionId): string;
}
