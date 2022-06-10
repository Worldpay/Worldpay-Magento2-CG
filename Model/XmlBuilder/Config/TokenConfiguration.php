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
     * [$createTokenBeforeAuth description]
     * @var [type]
     */
    private $createTokenBeforeAuth = false;
    /**
     * [$createTokenAndAuthTogether description]
     * @var [type]
     */
    private $createTokenAndAuthTogether = false;
    /**
     * [$createTokenEnabled description]
     * @var [type]
     */
    private $createTokenEnabled = false;
    
    /**
     * [__construct description]
     *
     * @param [type] $args [description]
     */
    public function __construct($args)
    {
        $this->createTokenEnabled = (bool)$args;
    }

    /**
     * Check if save card is requested
     *
     * @return bool
     */
    public function isSaveCreditCardReqested()
    {
        return true;
    }

    /**
     * Get token reason
     *
     * @param string $orderCode
     * @return string
     */
    public function getTokenReason($orderCode = null)
    {
        return 'To Save Card '.$orderCode;
    }
    
    /**
     * Check if tokennization is enable
     *
     * @return bool
     */
    public function istokenizationIsEnabled()
    {
        return $this->createTokenEnabled;
    }
}
