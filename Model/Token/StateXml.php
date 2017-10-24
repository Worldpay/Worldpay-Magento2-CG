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
    private $_xml;
    private $_tokenNode;
    private $_cardNode;
    private $_derivedNode;
    private $_paymentNode;
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
     * @return string
     */
    public function getOrderCode()
    {
        return (string) $this->_orderStatusNode['orderCode'];
    }

    /**
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
     * @return int
     */
    public function getCustomerId()
    {
        return (int)$this->_tokenNode->authenticatedShopperID;
    }

    /**
     * @return string
     */
    public function getAuthenticatedShopperId()
    {
        return (string)$this->_tokenNode->authenticatedShopperID;
    }

    /**
     * @return string
     */
    public function getObfuscatedCardNumber()
    {
        return (string)$this->_derivedNode->obfuscatedPAN;
    }

    /**
     * @return string
     */
    public function getCardholderName()
    {
        return (string)$this->_tokenNode->paymentInstrument->cardDetails->cardHolderName;
    }

    /**
     * @return \DateTime
     */
    public function getTokenExpiryDate()
    {
        $dateNode = $this->_tokenNode->tokenDetails->paymentTokenExpiry->date;
        $expireDate = new \DateTime();

        return $expireDate->setDate((int)$dateNode['year'], (int)$dateNode['month'], (int)$dateNode['dayOfMonth']);
    }

    public function getPaymentMethod()
    {
        return (string)$this->_paymentNode->paymentMethod;
    }

    /**
     * @return string
     */
    public function getCardBrand()
    {
        return (string)$this->_derivedNode->cardBrand;
    }

    /**
     * @return string
     */
    public function getCardSubBrand()
    {
        return (string)$this->_derivedNode->cardSubBrand;
    }

    /**
     * @return string
     */
    public function getCardIssuerCountryCode()
    {
        return (string)$this->_derivedNode->issuerCountryCode;
    }

    /**
     * @return string
     */
    public function getMerchantCode()
    {
        return (string)$this->_xml['merchantCode'];
    }

    /**
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
     * @return string
     */
    public function getTokenEvent()
    {
        return (string)$this->_tokenNode->tokenDetails['tokenEvent'];
    }

    /**
     * @return int
     */
    public function getCardExpiryMonth()
    {
        return (int)$this->_cardNode->expiryDate->date['month'];
    }

    /**
     * @return int
     */
    public function getCardExpiryYear()
    {
        return (int)$this->_cardNode->expiryDate->date['year'];
    }
}
