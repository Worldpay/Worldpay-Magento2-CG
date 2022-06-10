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
    /**
     * @var INTERNAL_ERROR
     */
    public const INTERNAL_ERROR = 1;
    /**
     * @var PARSE_ERROR
     */
    public const PARSE_ERROR = 2;
    /**
     * @var SECURITY_ERROR
     */
    public const SECURITY_ERROR = 4;
    /**
     * @var INVALID_REQUEST_ERROR
     */
    public const INVALID_REQUEST_ERROR = 5;
    /**
     * @var INVALID_CONTENT_ERROR
     */
    public const INVALID_CONTENT_ERROR = 6;
    /**
     * @var PAYMENT_DETAILS_ERROR
     */
    public const PAYMENT_DETAILS_ERROR = 7;

    /**
     * [$_responseXml description]
     * @var [type]
     */
    protected $_responseXml;
    /**
     * [$_merchantCode description]
     * @var [type]
     */
    protected $_merchantCode;
    /**
     * [$_paymentStatus description]
     * @var [type]
     */
    protected $_paymentStatus;
    /**
     * [$_payAsOrder description]
     * @var [type]
     */
    protected $_payAsOrder;
    /**
     * [$_errorMessage description]
     * @var [type]
     */
    protected $_errorMessage;
    /**
     * [$_wpOrderId description]
     * @var [type]
     */
    protected $_wpOrderId;

    /**
     * Get response xml
     *
     * @return SimpleXMLElement
     */
    public function getXml()
    {
        return $this->_responseXml;
    }
    
    /**
     * [setResponse description]
     *
     * @param [type] $response [description]
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
