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
    private $createTokenBeforeAuth = false;
    private $createTokenAndAuthTogether = false;
    private $createTokenEnabled = false;

    /**
     * @param bool $isDynamic3D
     * @param bool $is3DSecure
     */
    public function __construct($args)
    {
        $this->createTokenEnabled = (bool)$args;
    }

    /**
     * @return bool
     */
    public function isSaveCreditCardReqested()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getTokenReason($orderCode = null){
        return 'To Save Card '.$orderCode;
    }
    
    /**
     * @return bool
     */
    public function istokenizationIsEnabled(){
        return $this->createTokenEnabled;
    }
}
