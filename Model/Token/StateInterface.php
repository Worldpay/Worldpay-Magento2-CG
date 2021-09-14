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
    const TOKEN_EVENT_NEW = 'NEW';
    const TOKEN_EVENT_MATCH = 'MATCH';
    const TOKEN_EVENT_CONFLICT = 'CONFLICT';

    /**
     * Get order code
     *
     * @return string
     */
    public function getOrderCode();

    /**
     * Get Token code
     *
     * @return string
     */
    public function getTokenCode();

    /**
     * Get customer id
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Get authenticated shopper id
     *
     * @return string
     */
    public function getAuthenticatedShopperId();

    /**
     * Get Obfuscated Card Number
     *
     * @return string
     */
    public function getObfuscatedCardNumber();

    /**
     * Get card holder name
     *
     * @return string
     */
    public function getCardholderName();

    /**
     * Get Token expiry date
     *
     * @return \DateTime
     */
    public function getTokenExpiryDate();

    /**
     * Get card expiry month
     *
     * @return integer
     */
    public function getCardExpiryMonth();

    /**
     * Get card expiry year
     *
     * @return integer
     */
    public function getCardExpiryYear();

    /**
     * Get payment method
     *
     * @return string
     */
    public function getPaymentMethod();

    /**
     * Get card brand
     *
     * @return string
     */
    public function getCardBrand();

    /**
     * Get card sub brand
     *
     * @return string
     */
    public function getCardSubBrand();

    /**
     * Get Card issuer country code
     *
     * @return string
     */
    public function getCardIssuerCountryCode();

    /**
     * Get Merchant code
     *
     * @return string
     */
    public function getMerchantCode();

    /**
     * Get Token reason
     *
     * @return string
     */
    public function getTokenReason();

    /**
     * Get Token event
     *
     * @return string
     */
    public function getTokenEvent();
    
    /**
     * Retrieve bin details
     *
     * @return string
     */
    public function getBin();
    
    /**
     * Retrieve transaction identifier
     *
     * @return string
     */
    public function getTransactionIdentifier();
}
