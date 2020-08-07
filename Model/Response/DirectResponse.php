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
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/3ds.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('Step3 - Log received xml of 3ds form submit');
        $xml = $this->getXml();
		$logger->info(print_r($xml, true));

        if ($xml && ($request = $xml->xpath('reply/orderStatus/requestInfo/request3DSecure'))) {
			$logger->info('3d secure info from xml--'.print_r($request, true));
            $request = $request[0];
            $echoData = $xml->xpath('reply/orderStatus/echoData');
			$logger->info('echoData info from xml--'.print_r($echoData, true));
            return new \Magento\Framework\DataObject(array('url' => "$request->issuerURL", 'pa_request' => "$request->paRequest", 'echo_data' => (string) $echoData[0]));
        }
    }
    
    /**
     * @return \Magento\Framework\DataObject
     */
    public function get3ds2Params()
    {
        $xml = $this->getXml();

        if ($xml && ($request = $xml->xpath('reply/orderStatus/challengeRequired/threeDSChallengeDetails'))) {
            $request = $request[0];
            $payLoad = $xml->xpath('reply/orderStatus/challengeRequired/threeDSChallengeDetails/payload');
            return new \Magento\Framework\DataObject(array('threeDSVersion' => "$request->threeDSVersion", 'transactionId3DS' => "$request->transactionId3DS", 'acsURL' => "$request->acsURL", 'payload' => (string) $payLoad[0]));
        }
    }
}