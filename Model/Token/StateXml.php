<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Token;

/**
 * read from WP's token update response
 */
class StateXml implements \Sapient\Worldpay\Model\Token\StateInterface
{
    /**
     * @var SimpleXMLElement
     */
    private $_xml;
    /**
     * @var $_tokenNode
     */
    private $_tokenNode;
    /**
     * @var $_cardNode
     */
    private $_cardNode;
    /**
     * @var $_derivedNode
     */
    private $_derivedNode;
    /**
     * @var $_paymentNode
     */
    private $_paymentNode;
    /**
     * @var $_orderStatusNode
     */
    private $_orderStatusNode;

    /**
     * @param SimpleXMLElement $xml
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        $this->_xml = $xml;

        if (isset($this->_xml->reply->orderStatus)) {
            $this->_orderStatusNode = $this->_xml->reply->orderStatus;
        } else {
            $this->_orderStatusNode =  $this->_xml->notify->orderStatusEvent;
        }

        if (isset($this->_orderStatusNode->token)) {
            $this->_tokenNode = $this->_orderStatusNode->token;
        }

        if (isset($this->_tokenNode->paymentInstrument->cardDetails)) {
            $this->_cardNode = $this->_tokenNode->paymentInstrument->cardDetails;
        }

        if (isset($this->_tokenNode->paymentInstrument->cardDetails->derived)) {
            $this->_derivedNode = $this->_tokenNode->paymentInstrument->cardDetails->derived;
        }

        if (isset($this->_orderStatusNode->payment)) {
            $this->_paymentNode = $this->_orderStatusNode->payment;
        }
    }

    /**
     * Retrive ordercode
     *
     * @return string
     */
    public function getOrderCode()
    {
        return (string) $this->_orderStatusNode['orderCode'];
    }

    /**
     * Retrive tokencode
     *
     * @return string
     */
    public function getTokenCode()
    {
        if (! isset($this->_tokenNode->tokenDetails->paymentTokenID)) {
            return '';
        }

        return (string)$this->_tokenNode->tokenDetails->paymentTokenID;
    }

    /**
     * Retrive customer id
     *
     * @return int
     */
    public function getCustomerId()
    {
        return (int)$this->_tokenNode->authenticatedShopperID;
    }

    /**
     * Retrive authenticated shopper id
     *
     * @return string
     */
    public function getAuthenticatedShopperId()
    {
        return (string)$this->_tokenNode->authenticatedShopperID;
    }

    /**
     * Retrive obfuscated card number
     *
     * @return string
     */
    public function getObfuscatedCardNumber()
    {
        return (string)$this->_derivedNode->obfuscatedPAN;
    }

    /**
     * Retrive card holder name
     *
     * @return string
     */
    public function getCardholderName()
    {
        return (string)$this->_tokenNode->paymentInstrument->cardDetails->cardHolderName;
    }

    /**
     * Retrive token expiry date
     *
     * @return \DateTime
     */
    public function getTokenExpiryDate()
    {
        $dateNode = $this->_tokenNode->tokenDetails->paymentTokenExpiry->date;
        $expireDate = new \DateTime();

        return $expireDate->setDate((int)$dateNode['year'], (int)$dateNode['month'], (int)$dateNode['dayOfMonth']);
    }

    /**
     * Retrive payment methods
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        return (string)$this->_paymentNode->paymentMethod;
    }

    /**
     * Retrive card brand
     *
     * @return string
     */
    public function getCardBrand()
    {
        return (string)$this->_derivedNode->cardBrand;
    }

    /**
     * Retrive card sub brand
     *
     * @return string
     */
    public function getCardSubBrand()
    {
        return (string)$this->_derivedNode->cardSubBrand;
    }

    /**
     * Retrive card issuer country code
     *
     * @return string
     */
    public function getCardIssuerCountryCode()
    {
        return (string)$this->_derivedNode->issuerCountryCode;
    }

    /**
     * Retrive merchant code
     *
     * @return string
     */
    public function getMerchantCode()
    {
        return (string)$this->_xml['merchantCode'];
    }

    /**
     * Retrive token reason
     *
     * @return string
     */
    public function getTokenReason()
    {
        if ((string)$this->_tokenNode->tokenReason) {
            return (string)$this->_tokenNode->tokenReason;
        }

        return (string)$this->_tokenNode->tokenDetails->tokenReason;
    }

    /**
     * Retrive token event
     *
     * @return string
     */
    public function getTokenEvent()
    {
        return (string)$this->_tokenNode->tokenDetails['tokenEvent'];
    }

    /**
     * Retrive card expiry month
     *
     * @return int
     */
    public function getCardExpiryMonth()
    {
        return (int)$this->_cardNode->expiryDate->date['month'];
    }

    /**
     * Retrive card expiry year
     *
     * @return int
     */
    public function getCardExpiryYear()
    {
        return (int)$this->_cardNode->expiryDate->date['year'];
    }
    
    /**
     * Retrive bin
     *
     * @return string
     */
    public function getBin()
    {
        return (string)$this->_derivedNode->bin;
    }
    
    /**
     * Retrive transaction identifier
     *
     * @return string
     */
    public function getTransactionIdentifier()
    {
        return (string)$this->_paymentNode->schemeResponse->transactionIdentifier;
    }
}
