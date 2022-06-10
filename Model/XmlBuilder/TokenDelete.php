<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

use Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecureConfig;
use \Sapient\Worldpay\Logger\WorldpayLogger;

/**
 * Build xml for delete token request
 */
class TokenDelete
{
    public const TOKEN_SCOPE = 'shopper';
    public const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    /**
     * @var Mage_Customer_Model_Customer
     */
    private $customer;

    /**
     * @var Sapient_WorldPay_Model_Token
     */
    private $tokenModel;

    /**
     * @var string
     */
    protected $merchantCode;

    /**
     * Constructor
     *
     * @param array $args
     */
    public function __construct(array $args = [])
    {
        if (isset($args['tokenModel']) && $args['tokenModel'] instanceof \Sapient\WorldPay\Model\SavedToken) {
            $this->tokenModel = $args['tokenModel'];
        }

        if (isset($args['customer']) && $args['customer'] instanceof \Magento\Customer\Model\Customer) {
            $this->customer = $args['customer'];
        }

        if (isset($args['merchantCode'])) {
            $this->merchantCode = $args['merchantCode'];
        }
    }

    /**
     * Build xml for processing Request
     *
     * @return SimpleXMLElement $xml
     */
    public function build()
    {
        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['version'] = '1.4';
        $xml['merchantCode'] = $this->merchantCode;

        $modify = $this->_addModifyElement($xml);
        $this->_addTokenUpdateElement($modify);

        return $xml;
    }

    /**
     * Add modify attribute
     *
     * @param SimpleXMLElement $xml
     * @return SimpleXMLElement
     */
    private function _addModifyElement($xml)
    {
        return $xml->addChild('modify');
    }

    /**
     * Add token update element
     *
     * @param SimpleXMLElement $modify
     * @return SimpleXMLElement
     */
    private function _addTokenUpdateElement($modify)
    {
        $tokenDelete = $modify->addChild('paymentTokenDelete');
        $tokenDelete['tokenScope'] = $this->tokenModel->getTokenType();
        $tokenDelete->addChild('paymentTokenID', $this->tokenModel->getTokenCode());
        if ($this->tokenModel->getTokenType() == self::TOKEN_SCOPE) {
            $tokenDelete->addChild('authenticatedShopperID', $this->customer->getId());
        }
        return $tokenDelete;
    }
}
