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

    /**
     * @var SimpleXmlElement
     */
    protected $_responseXml;
    /**
     * Worldpay merchant code
     *
     * @var string
     */
    protected $_merchantCode;
    /**
     * Worldpay payment status
     *
     * @var string
     */
    protected $_paymentStatus;
    /**
     * Purchase order
     *
     * @var string
     */
    protected $_payAsOrder;
    /**
     * Error messages
     *
     * @var string
     */
    protected $_errorMessage;
    /**
     * Worldpay order id
     *
     * @var string
     */
    protected $_wpOrderId;

    /**
     * Getter for xml element
     *
     * @return SimpleXMLElement
     */
    public function getXml()
    {
        return $this->_responseXml;
    }

    /**
     * Set the XML response(s) to be returned by this adapter
     *
     * @param SimpleXmlElement|array|string $response
     * @return $this
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
