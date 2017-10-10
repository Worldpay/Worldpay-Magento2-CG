<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;

use Exception;

class Request
{
    protected $_request;
    protected $_logger;
    protected $_configHelper;

    const CURL_POST = true;
    const CURL_RETURNTRANSFER = true;
    const CURL_NOPROGRESS = false;
    const CURL_TIMEOUT = 60;
    const CURL_VERBOSE = true;
    const SUCCESS = 200;

    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Request\CurlRequest $curlrequest
        ) {
        $this->_wplogger = $wplogger;
        $this->curlrequest = $curlrequest;
    }

    public function sendRequest($quote, $username, $password)
    {
        $request = $this->_getRequest();
        $logger = $this->_wplogger;
        $url = $this->_getUrl();

        $logger->info('Setting destination URL: ' . $url);
        $request->setUrl($url);

        $logger->info('Initialising request');
        $request->setOption(CURLOPT_POST, self::CURL_POST);
        $request->setOption(CURLOPT_RETURNTRANSFER, self::CURL_RETURNTRANSFER);
        $request->setOption(CURLOPT_NOPROGRESS, self::CURL_NOPROGRESS);
        $request->setOption(CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
        $request->setOption(CURLOPT_VERBOSE, self::CURL_VERBOSE);
        /*SSL verification false*/
        $request->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $request->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $request->setOption(CURLOPT_POSTFIELDS, $quote->saveXML());
        $request->setOption(CURLOPT_USERPWD, $username.':'.$password);
        $request->setOption(CURLOPT_HEADER, true);
        $request->setOption(CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        $logger->info('Sending XML as: ' . $this->_getObfuscatedXmlLog($quote));

        $result = $request->execute();

        if (!$result || ($code = $request->getInfo(CURLINFO_HTTP_CODE)) != self::SUCCESS) {
            $logger->info('Request could not be sent.');
            $logger->info($result);
            $logger->info('########### END OF REQUEST - FAILURE WHILST TRYING TO SEND REQUEST ###########');

            throw new Exception();
        }

        $request->close();

        $logger->info('Request successfully sent');
        $logger->info($result);

        // extract headers
        $bits = explode("\r\n\r\n", $result);
        $body = array_pop($bits);
        $headers = implode("\r\n\r\n", $bits);

        return $body;
    }

    /**
     * Censors sensitive data before outputting to the log file
     */
    protected function _getObfuscatedXmlLog($quote)
    {
        $elems = array('cardNumber', 'cvc');
        $_xml  = clone($quote);

        foreach ($elems as $_e) {
            foreach ($_xml->getElementsByTagName($_e) as $_node) {
                $_node->nodeValue = str_repeat('X', strlen($_node->nodeValue));
            }
        }

        return $_xml->saveXML();
    }

    private function _getUrl()
    {
        return 'https://secure-test.worldpay.com/jsp/merchant/xml/paymentService.jsp';
    }

    private function _getRequest()
    {
        if ($this->_request === null) {
            $this->_request = $this->curlrequest;
        }

        return $this->_request;
    }

}
