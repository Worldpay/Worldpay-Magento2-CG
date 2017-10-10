<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder\Config;

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

    public function getTokenReason(){
        return 'ClothesDepartment';
    }
    
    public function istokenizationIsEnabled(){
        return $this->createTokenEnabled;
    }
}
