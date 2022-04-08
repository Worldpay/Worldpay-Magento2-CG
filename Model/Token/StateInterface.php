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
     * @return string
     */
    public function getOrderCode();

    /**
     * @return string
     */
    public function getTokenCode();

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @return string
     */
    public function getAuthenticatedShopperId();

    /**
     * @return string
     */
    public function getObfuscatedCardNumber();

    /**
     * @return string
     */
    public function getCardholderName();

    /**
     * @return \DateTime
     */
    public function getTokenExpiryDate();

    /**
     * @return integer
     */
    public function getCardExpiryMonth();

    /**
     * @return integer
     */
    public function getCardExpiryYear();

    /**
     * @return string
     */
    public function getPaymentMethod();

    /**
     * @return string
     */
    public function getCardBrand();

    /**
     * @return string
     */
    public function getCardSubBrand();

    /**
     * @return string
     */
    public function getCardIssuerCountryCode();

    /**
     * @return string
     */
    public function getMerchantCode();

    /**
     * @return string
     */
    public function getTokenReason();

    /**
     * @return string
     */
    public function getTokenEvent();
    
    /**
     * @return string
     */
    public function getBin();
    
    /**
     * @return string
     */
    public function getTransactionIdentifier();
}
