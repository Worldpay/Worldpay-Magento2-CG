<?php

namespace Sapient\Worldpay\Helper;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Sapient\Worldpay\Logger\WorldpayLogger;

class CurlHelper
{
    /**
     * @var Curl $curlFactory
     */
    public $curlFactory;
    /**
     * @var WorldpayLogger $wplogger
     */
    public $wplogger;

    /**
     * Constructor
     *
     * @param CurlFactory $curlFactory
     * @param WorldpayLogger $wplogger
     */
    public function __construct(CurlFactory $curlFactory, WorldpayLogger $wplogger)
    {
        $this->curlFactory = $curlFactory;
        $this->wplogger = $wplogger;
    }

    /**
     * Send curl request
     *
     * @param string $url
     * @param array $curlOptionArray
     */
    public function sendCurlRequest(string $url, array $curlOptionArray)
    {
        $curl = $this->curlFactory->create();

        try {
            $curl->setOptions($curlOptionArray);
            $curl->post($url, []);
            $response = $curl->getBody();
            return $response;
        } catch (\Exception $e) {
            $this->wplogger->info(__("Error while sending curl request".$e->getMessage()));
        }
        return null;
    }
     /**
      * Send curl request
      *
      * @param string $url
      * @param array $curlOptionArray
      * @param array $headers
      */
    public function sendGetCurlRequest(string $url, array $curlOptionArray, array $headers)
    {
        $curl = $this->curlFactory->create();

        try {
            $curl->setOptions($curlOptionArray);
            $curl->setTimeout(0);
            $curl->setHeaders($headers);
            $curl->get($url);
            $response = $curl->getBody();
            return $response;
        } catch (\Exception $e) {
            $this->wplogger->info(__("Error while sending curl request".$e->getMessage()));
        }
        return null;
    }
}
