<?php

namespace Sapient\Worldpay\Helper;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Marketplace\Helper\Cache;
use Magento\Backend\Model\UrlInterface;

class CurlHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $curl;
    public $wplogger;
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) {
        $this->curl = $curl;
        $this->wplogger = $wplogger;
    }
    /**
     *  send curl request
     */
    public function sendCurlRequest($url, $curlOptionArray)
    {
        try {
            $this->curl->setOptions($curlOptionArray);
            $this->curl->post($url, []);
            $response = $this->curl->getBody();
            return $response;
        } catch (\Exception $e) {
            $this->wplogger->info(__("Error while sending curl request".$e->getMessage()));
        }
        return null;
    }
    /**
     *  send get curl request
     */
    public function sendGetCurlRequest($url, $curlOptionArray, $headers)
    {
        try {
            $this->curl->setOptions($curlOptionArray);
            $this->curl->setTimeout(0);
            $this->curl->setHeaders($headers);
            $this->curl->get($url);
            $response = $this->curl->getBody();
            return $response;
        } catch (\Exception $e) {
            $this->wplogger->info(__("Error while sending curl request".$e->getMessage()));
        }
        return null;
    }
}
