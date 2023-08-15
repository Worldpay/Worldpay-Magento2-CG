<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Response;

/**
 * help to build redirect url
 */
class RedirectResponse extends \Sapient\Worldpay\Model\Response\ResponseAbstract
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    public $_urlBuilder;
    /**
     * Constructor
     *
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(\Magento\Framework\UrlInterface $urlBuilder)
    {
        $this->_urlBuilder = $urlBuilder;
    }
    
    /**
     * Return succeess, pending, cancel and failure redirect URLs
     *
     * @param SimpleXmlElement $xml
     * @return string $url
     */
    public function getRedirectLocation($xml)
    {
        $url = $this->getRedirectUrl($xml);

        $url .= '&successURL=' . $this->_urlBuilder->getUrl('worldpay/redirectresult/success');
        $url .= '&pendingURL=' . $this->_urlBuilder->getUrl('worldpay/redirectresult/pending');
        $url .= '&cancelURL=' . $this->_urlBuilder->getUrl('worldpay/redirectresult/cancel');
        $url .= '&failureURL=' . $this->_urlBuilder->getUrl('worldpay/redirectresult/failure');

        return $url;
    }

    /**
     * Returns url for redirect.
     *
     * @param SimpleXmlElement $xml
     * @return string $url
     */
    public function getRedirectUrl($xml)
    {
        $this->setResponse($xml);

        $url = $this->_responseXml->xpath('reply/orderStatus/reference');
        return trim($url[0]);
    }

    /**
     * Return succeess, pending, cancel and failure callback URLs
     *
     * @return array $callbackurl
     */
    public function getCallBackUrl()
    {
        $callbackurl = [];
        $callbackurl['successURL'] = $this->_urlBuilder->getUrl('worldpay/redirectresult/success');
        $callbackurl['pendingURL'] = $this->_urlBuilder->getUrl('worldpay/redirectresult/pending');
        $callbackurl['cancelURL'] = $this->_urlBuilder->getUrl('worldpay/redirectresult/cancel');
        $callbackurl['failureURL'] = $this->_urlBuilder->getUrl('worldpay/redirectresult/failure');
        return $callbackurl;
    }
}
