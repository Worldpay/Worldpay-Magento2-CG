<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Api;
 
interface LatAmInstalInterface
{
    /**
     * Retrive Instalment Types
     *
     * @api
     * @param string $countryId.
     * @return null|string
     */
    public function getInstalmentType($countryId);
}
