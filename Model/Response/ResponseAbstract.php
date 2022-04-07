<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Response;

use Exception;

/**
 * Abstract class used for reading the xml
 */
abstract class ResponseAbstract
{
    public const INTERNAL_ERROR = 1;
    public const PARSE_ERROR = 2;
    public const SECURITY_ERROR = 4;
    public const INVALID_REQUEST_ERROR = 5;
    public const INVALID_CONTENT_ERROR = 6;
    public const PAYMENT_DETAILS_ERROR = 7;

    protected $_responseXml;
    protected $_merchantCode;
    protected $_paymentStatus;
    protected $_payAsOrder;
    protected $_errorMessage;
    protected $_wpOrderId;

    /**
     * @return SimpleXMLElement
     */
    public function getXml()
    {
        return $this->_responseXml;
    }

    /**
     * @param $response
     * @return  $this
     */
    public function setResponse($response)
    {
        try {
            $this->_responseXml = new \SimpleXmlElement($response);
            $this->_merchantCode = $this->_responseXml['merchantCode'];
        } catch (Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not parse response XML')
            );
        }

        return $this;
    }
}
