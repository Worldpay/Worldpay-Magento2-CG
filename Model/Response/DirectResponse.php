<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Response;

class DirectResponse extends \Sapient\Worldpay\Model\Response\ResponseAbstract
{
    public const PAYMENT_AUTHORISED = 'AUTHORISED';

    /**
     * @var SimpleXmlElement
     */
    protected $_responseXml;

    /**
     * Get 3ds parameters
     *
     * @return \Magento\Framework\DataObject
     */
    public function get3dSecureParams()
    {
        $xml = $this->getXml();

        if ($xml && ($request = $xml->xpath('reply/orderStatus/requestInfo/request3DSecure'))) {
            $request = $request[0];
            $echoData = $xml->xpath('reply/orderStatus/echoData');
            return new \Magento\Framework\DataObject(['url' => "$request->issuerURL",
                'pa_request' => "$request->paRequest", 'echo_data' => (string) $echoData[0]]);
        }
    }
    
    /**
     * Get 3ds2 parameters
     *
     * @return \Magento\Framework\DataObject
     */
    public function get3ds2Params()
    {
        $xml = $this->getXml();

        if ($xml && ($request = $xml->xpath('reply/orderStatus/challengeRequired/threeDSChallengeDetails'))) {
            $request = $request[0];
            $payLoad = $xml->xpath('reply/orderStatus/challengeRequired/threeDSChallengeDetails/payload');
            return new \Magento\Framework\DataObject(['threeDSVersion'
                => "$request->threeDSVersion", 'transactionId3DS' => "$request->transactionId3DS",
                'acsURL' => "$request->acsURL", 'payload' => (string) $payLoad[0]]);
        }
    }
}
