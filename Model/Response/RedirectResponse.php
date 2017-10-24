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
     * Constructor
     *
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
	public function __construct(\Magento\Framework\UrlInterface $urlBuilder)
    {
		$this->_urlBuilder = $urlBuilder;
	}
    
    /**          
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
     * @param SimpleXmlElement $xml
     * @return string $url
     */
    public function getRedirectUrl($xml)
    {
        $this->setResponse($xml);

        $url = $this->_responseXml->xpath('reply/orderStatus/reference');
        return trim($url[0]);
    }

}
