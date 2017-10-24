<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Response;

class DirectResponse extends \Sapient\Worldpay\Model\Response\ResponseAbstract
{
    const PAYMENT_AUTHORISED = 'AUTHORISED';

    /**          
     * @param SimpleXmlElement
     */
    protected $_responseXml;

    /**
     * @return \Magento\Framework\DataObject
     */
    public function get3dSecureParams()
    {
        $xml = $this->getXml();

        if ($xml && ($request = $xml->xpath('reply/orderStatus/requestInfo/request3DSecure'))) {
            $request = $request[0];
            $echoData = $xml->xpath('reply/orderStatus/echoData');
            return new \Magento\Framework\DataObject(array('url' => "$request->issuerURL", 'pa_request' => "$request->paRequest", 'echo_data' => (string) $echoData[0]));
        }
    }
}
