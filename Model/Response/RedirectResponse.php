<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Response;

class RedirectResponse extends \Sapient\Worldpay\Model\Response\ResponseAbstract
{
	public function __construct(\Magento\Framework\UrlInterface $urlBuilder)
    {
		$this->_urlBuilder = $urlBuilder;
	}
    
    public function getRedirectLocation($xml)
    {
        $url = $this->getRedirectUrl($xml);

        $url .= '&successURL=' . $this->_urlBuilder->getUrl('worldpay/redirectresult/success');
        $url .= '&pendingURL=' . $this->_urlBuilder->getUrl('worldpay/redirectresult/pending');
        $url .= '&cancelURL=' . $this->_urlBuilder->getUrl('worldpay/redirectresult/cancel');
        $url .= '&failureURL=' . $this->_urlBuilder->getUrl('worldpay/redirectresult/failure');

        return $url;
    }

    public function getRedirectUrl($xml)
    {
        $this->setResponse($xml);

        $url = $this->_responseXml->xpath('reply/orderStatus/reference');
        return trim($url[0]);
    }

}
