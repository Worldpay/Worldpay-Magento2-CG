<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Api;
 
interface HostedUrlInterface
{
    /**
     * Retrieve HPP payment Url
     *
     * @api
     * @param string $quoteId
     * @param string[] $paymentdetails
     * @return null|string
     */
    
    public function getHostedUrl($quoteId, array $paymentdetails);
}
