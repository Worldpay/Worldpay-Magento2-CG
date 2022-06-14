<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Token;

use Sapient\Worldpay\Model\SavedToken;

/**
 * Represents the token delete xml response from WP server
 */
class DeleteXml implements UpdateInterface
{
    /**
     * @var SimpleXMLElement
     */
    private $_xml;

    /**
     * @param SimpleXMLElement $xml
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        $this->_xml = $xml;
    }

    /**
     * Getting token code
     *
     * @return string
     */
    public function getTokenCode()
    {
        return (string)$this->_xml->reply->ok->deleteTokenReceived['paymentTokenID'];
    }

    /**
     * Is success?
     *
     * @return bool
     */
    public function isSuccess()
    {
        return isset($this->_xml->reply->ok);
    }
}
