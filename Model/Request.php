<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;

use Exception;

/**
 * Used for processing the Request
 */
class Request
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_request;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $_wplogger;
     /**
      * @var \Sapient\Worldpay\Helper\Data
      */
    protected $helper;

     /**
      * @var \Magento\Framework\HTTP\Client\Curl
      */
    protected $curl;

    public const CURL_POST = true;
    public const CURL_RETURNTRANSFER = true;
    public const CURL_NOPROGRESS = true;
    public const CURL_TIMEOUT = 300;
    public const CURL_VERBOSE = false;
    public const SUCCESS = 200;

    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $helper
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $helper,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->_wplogger = $wplogger;
        $this->helper = $helper;
        $this->curl = $curl;
    }
     /**
      * Process the request
      *
      * @param array $quote
      * @param string $username
      * @param string $password
      * @return SimpleXMLElement body
      * @throws Exception
      */
    public function sendRequest($quote, $username, $password)
    {
        $request = $this->_getRequest();
        $logger = $this->_wplogger;
        $url = $this->_getUrl();

        $pluginTrackerDetails = $this->collectHeaderPluginTrackerdetails($quote);
        $_xml  = clone($quote);
        $paymentDetails = $_xml->getElementsByTagName('paymentDetails');

        $logger->info('Setting destination URL: ' . $url);
        $logger->info('Initialising request');
        $request->setOption(CURLOPT_POST, self::CURL_POST);
        $request->setOption(CURLOPT_RETURNTRANSFER, self::CURL_RETURNTRANSFER);
        $request->setOption(CURLOPT_NOPROGRESS, self::CURL_NOPROGRESS);
        $request->setOption(CURLOPT_VERBOSE, self::CURL_VERBOSE);
        /*SSL verification false*/
        $request->setOption(CURLOPT_SSL_VERIFYHOST, 2);
        $request->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $request->setOption(CURLOPT_USERPWD, $username.':'.$password);
        // Cookie Set to 2nd 3DS request only.
        $cookie = $this->helper->getWorldpayAuthCookie();
        if ($this->helper->isThreeDSRequest() && $cookie != "") { // Check is 3DS request
            $cookie = $cookie.';SameSite=None';
            $request->setOption(CURLOPT_COOKIE, $cookie);
        }
        if ($this->helper->isDynamic3DS2Enabled() && $cookie != "") { // Check is 3DS2 request
            $request->setOption(CURLOPT_COOKIE, $cookie);
        }
        //$request->addCookie(CURLOPT_COOKIE, $cookie);
        $request->setTimeout(self::CURL_TIMEOUT);
        if (isset($paymentDetails[0]->nodeValue)) {
            $headersArray = [
                'Content-Type'=> 'text/xml',
                'Expect'=>'',
                'ecommerce_platform' => $pluginTrackerDetails['ecommerce_platform'],
                'ecommerce_platform_version' => $pluginTrackerDetails['ecommerce_platform_version'],
                'ecommerce_plugin_data'=>json_encode($pluginTrackerDetails['ecommerce_plugin_data'])
            ];
        } else {
            $headersArray = [
                'Content-Type'=> 'text/xml',
                'Expect'=>''
            ];
        }

        $logger->info('Sending XML as: ' . $this->_getObfuscatedXmlLog($quote));
        $request->setOption(CURLOPT_HEADER, 1);
        $request->setHeaders($headersArray);
        $request->setOption(CURLINFO_HEADER_OUT, 1);
        $request->post($url, $quote->saveXML());
        $result = $request->getBody();

        // logging Headder for 3DS request to check Cookie.
        if ($this->helper->isThreeDSRequest()) {
            //$information = $request->getInfo(CURLINFO_HEADER_OUT);
            $information = $request->getHeaders();
            $logger->info("**REQUEST HEADER START**");
            $logger->info(json_encode($information));
            $logger->info("**REQUEST HEADER ENDS**");
        }
        if (!$result || ($request->getStatus() != self::SUCCESS)) {
            $logger->info('Request could not be sent.');
            $errorMessage = $this->findErrorMessage($result);
            $logger->info($result);
            $logger->info('########### END OF REQUEST - FAILURE WHILE TRYING TO SEND REQUEST ###########');
            if (!empty($errorMessage)) {
                $errorMessage = $errorMessage.".";
            }
            $errorMessage .= 'Worldpay api service not available';
            throw new \Magento\Framework\Exception\LocalizedException(
                __($errorMessage)
            );
        }
        $logger->info('Request successfully sent');
        $logger->info($result);
        // extract headers

        // extract headers
        $bits = explode("\r\n\r\n", $result);
        $body = array_pop($bits);
        $headers = implode("\r\n\r\n", $bits);
        // Extracting Cookie from Response Header.
        if (preg_match("/set-cookie: (.+?)([\r\n]|$)/i", $headers, $match)) {
            // Keep a hold of the cookie returned incase we need to send a
            // second order message after 3dSecure check
            $logger->info('Cookie Get: ' . $match[1]);
            $this->helper->setWorldpayAuthCookie($match[1]);
        }
        return $body;
    }

    /**
     * Censors sensitive data before outputting to the log file
     *
     * @param array $quote
     */
    protected function _getObfuscatedXmlLog($quote)
    {
        $elems = ['cardNumber', 'cvc', 'iban'];
        $_xml  = clone($quote);

        foreach ($elems as $_e) {
            foreach ($_xml->getElementsByTagName($_e) as $_node) {
                $_node->nodeValue = str_repeat('X', strlen($_node->nodeValue));
            }
        }

        return $_xml->saveXML();
    }

    /**
     * Get URL of merchant site based on environment mode
     */
    private function _getUrl()
    {
        if ($this->helper->getEnvironmentMode()=='Live Mode') {
            return $this->helper->getLiveUrl();
        }
        return $this->helper->getTestUrl();
    }

    /**
     * Worldpay request object
     *
     * @return object
     */
    private function _getRequest()
    {
        if ($this->_request === null) {
            $this->_request = $this->curl;
        }

        return $this->_request;
    }
    /**
     * Find Error message from header response
     *
     * @param string $response
     */
    private function findErrorMessage($response)
    {
        $headerbits = explode("\r\n\r\n", $response);
        $remainingBody = array_pop($headerbits);
        $errorMsg = "";
        if (!empty($remainingBody)) {
            $responseXml = new \SimpleXmlElement($remainingBody);
            try {
                $errorMsg = $responseXml->head->title;
            } catch (\Exception $e) {
                $this->_wplogger->info(__('Could not parse error body xml'));
            }

        }
        return $errorMsg;
    }

    /**
     * Collect Plugin Trackerdetails for Header Request
     *
     * @param array $quote
     * @return array
     */
    private function collectHeaderPluginTrackerdetails($quote)
    {
        $paymentMethod='';
        $_xml  = clone($quote);
        $paymentDetails = $_xml->getElementsByTagName('paymentDetails');
        if (isset($paymentDetails[0]->nodeValue)) {
            $orderContent = $_xml->getElementsByTagName('orderContent');
            if (isset($orderContent[0]->nodeValue)) {
                $orderContentdata = $orderContent[0]->nodeValue;
                $result = json_decode($orderContentdata);
                $paymentMethod = $result->additional_details->transaction_method;
            }
        }
        $pluginTrackerDetails = $this->helper->getPluginTrackerHeaderdetails();
        $pluginTrackerDetails['ecommerce_plugin_data']['additional_details'] =
        ['payment_method'=>$paymentMethod];

        return $pluginTrackerDetails;
    }
}
