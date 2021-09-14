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
     * Retrieve order code
     *
     * @return string
     */
    public function getOrderCode()
    {
        return (string) $this->_orderStatusNode['orderCode'];
    }

    /**
     * Retrieve token code
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
     * Retrieve customer id
     *
     * @return int
     */
    public function getCustomerId()
    {
        return (int)$this->_tokenNode->authenticatedShopperID;
    }

    /**
     * Obtain authenticated shopper id
     *
     * @return string
     */
    public function getAuthenticatedShopperId()
    {
        return (string)$this->_tokenNode->authenticatedShopperID;
    }

    /**
     * Get Obfuscated Card Number
     *
     * @return string
     */
    public function getObfuscatedCardNumber()
    {
        return (string)$this->_derivedNode->obfuscatedPAN;
    }

    /**
     * Obtain card holder name
     *
     * @return string
     */
    public function getCardholderName()
    {
        return (string)$this->_tokenNode->paymentInstrument->cardDetails->cardHolderName;
    }

    /**
     * Get Token expiry date
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
     * Get payment method
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        return (string)$this->_paymentNode->paymentMethod;
    }

    /**
     * Obtain card brand
     *
     * @return string
     */
    public function getCardBrand()
    {
        return (string)$this->_derivedNode->cardBrand;
    }

    /**
     * Get card sub brand
     *
     * @return string
     */
    public function getCardSubBrand()
    {
        return (string)$this->_derivedNode->cardSubBrand;
    }

    /**
     * Get Card issuer country code
     *
     * @return string
     */
    public function getCardIssuerCountryCode()
    {
        return (string)$this->_derivedNode->issuerCountryCode;
    }

    /**
     * Obtain Merchant code
     *
     * @return string
     */
    public function getMerchantCode()
    {
        return (string)$this->_xml['merchantCode'];
    }

    /**
     * Get token reason
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
     * Retrieve token event
     *
     * @return string
     */
    public function getTokenEvent()
    {
        return (string)$this->_tokenNode->tokenDetails['tokenEvent'];
    }

    /**
     * Retrieve card expiry month
     *
     * @return int
     */
    public function getCardExpiryMonth()
    {
        return (int)$this->_cardNode->expiryDate->date['month'];
    }

    /**
     * Retrieve card expiry year
     *
     * @return int
     */
    public function getCardExpiryYear()
    {
        return (int)$this->_cardNode->expiryDate->date['year'];
    }
    
    /**
     * Retrieve bin value
     *
     * @return string
     */
    public function getBin()
    {
        return (string)$this->_derivedNode->bin;
    }
    
    /**
     * Return transaction identifier
     *
     * @return string
     */
    public function getTransactionIdentifier()
    {
        return (string)$this->_paymentNode->schemeResponse->transactionIdentifier;
    }
}
