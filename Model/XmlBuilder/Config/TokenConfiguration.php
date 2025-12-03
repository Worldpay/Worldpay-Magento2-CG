<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder\Config;

/**
 * Get token Configuration
 */
class TokenConfiguration
{
    /**
     * @var bool
     */
    private $createTokenBeforeAuth = false;
    /**
     * @var bool
     */
    private $createTokenAndAuthTogether = false;
    /**
     * @var bool
     */
    private $createTokenEnabled = false;

    /**
     * @param bool $args
     */
    public function __construct($args)
    {
        $this->createTokenEnabled = (bool)$args;
    }

    /**
     * Requested save cc
     *
     * @return bool
     */
    public function isSaveCreditCardReqested()
    {
        return true;
    }

    /**
     * Retrive token reason
     *
     * @param string|null $orderCode
     * @return string
     */
    public function getTokenReason($orderCode = null)
    {
        return 'To Save Card '.$orderCode;
    }

    /**
     * Check if tokenization is enabled?
     *
     * @return bool
     */
    public function istokenizationIsEnabled()
    {
        return $this->createTokenEnabled;
    }
}
