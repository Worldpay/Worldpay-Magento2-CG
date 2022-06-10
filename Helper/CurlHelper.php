<?php

namespace Sapient\Worldpay\Helper;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Marketplace\Helper\Cache;
use Magento\Backend\Model\UrlInterface;

class CurlHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * [$curl description]
     * @var [type]
     */
    public $curl;
    /**
     * [$wplogger description]
     * @var [type]
     */
    public $wplogger;
    /**
     * [__construct description]
     *
     * @param \Magento\Framework\HTTP\Client\Curl     $curl     [description]
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger [description]
     */
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) {
        $this->curl = $curl;
        $this->wplogger = $wplogger;
    }
    /**
     * [sendCurlRequest description]
     *
     * @param  [type] $url             [description]
     * @param  [type] $curlOptionArray [description]
     * @return [type]                  [description]
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
     * [sendGetCurlRequest description]
     *
     * @param  [type] $url             [description]
     * @param  [type] $curlOptionArray [description]
     * @param  [type] $headers         [description]
     * @return [type]                  [description]
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
