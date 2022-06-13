<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Token;

/**
 * Interface Sapient\WorldPay\Model\Token\StateInterface
 *
 * Describe what can be read from WP's response
 */
interface StateInterface
{
    public const TOKEN_EVENT_NEW = 'NEW';
    public const TOKEN_EVENT_MATCH = 'MATCH';
    public const TOKEN_EVENT_CONFLICT = 'CONFLICT';

    /**
     * Retrive ordercode
     *
     * @return string
     */
    public function getOrderCode();

    /**
     * Retrive tokencode
     *
     * @return string
     */
    public function getTokenCode();

    /**
     * Retrive customer id
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Retrive authenticated shopper id
     *
     * @return string
     */
    public function getAuthenticatedShopperId();

    /**
     * Retrive obfuscated card number
     *
     * @return string
     */
    public function getObfuscatedCardNumber();

    /**
     * Retrive card holder name
     *
     * @return string
     */
    public function getCardholderName();

    /**
     * Retrive token expiry date
     *
     * @return \DateTime
     */
    public function getTokenExpiryDate();

    /**
     * Retrive card expiry month
     *
     * @return integer
     */
    public function getCardExpiryMonth();

    /**
     * Retrive card expiry year
     *
     * @return integer
     */
    public function getCardExpiryYear();

    /**
     * Retrive payment methods
     *
     * @return string
     */
    public function getPaymentMethod();

    /**
     * Retrive card brand
     *
     * @return string
     */
    public function getCardBrand();

    /**
     * Retrive card sub brand
     *
     * @return string
     */
    public function getCardSubBrand();

    /**
     * Retrive card issuer country code
     *
     * @return string
     */
    public function getCardIssuerCountryCode();

    /**
     * Retrive merchant code
     *
     * @return string
     */
    public function getMerchantCode();

    /**
     * Retrive token reason
     *
     * @return string
     */
    public function getTokenReason();

    /**
     * Retrive token event
     *
     * @return string
     */
    public function getTokenEvent();
    
    /**
     * Retrive bin
     *
     * @return string
     */
    public function getBin();
    
    /**
     * Retrive transaction identifier
     *
     * @return string
     */
    public function getTransactionIdentifier();
}
