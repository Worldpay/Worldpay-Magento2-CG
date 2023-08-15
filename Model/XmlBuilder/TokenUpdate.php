<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

use Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecureConfig;
use \Sapient\Worldpay\Logger\WorldpayLogger;

/**
 * Build xml for update token request
 */
class TokenUpdate
{
    public const TOKEN_SCOPE = 'shopper';
    public const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    private $customer;

    /**
     * @var \Sapient\WorldPay\Model\Token
     */
    private $tokenModel;

    /**
     * @var string
     */
    protected $merchantCode;

    /**
     * TokenUpdate constructor
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
     * Add tag modify to xml
     *
     * @param SimpleXMLElement $xml
     * @return SimpleXMLElement
     */
    private function _addModifyElement($xml)
    {
        return $xml->addChild('modify');
    }

    /**
     * Add token update and its child tag to xml
     *
     * @param SimpleXMLElement $modify
     * @return SimpleXMLElement $xml
     */
    private function _addTokenUpdateElement($modify)
    {
        $tokenUpdate = $modify->addChild('paymentTokenUpdate');
        $tokenUpdate['tokenScope'] = $this->tokenModel->getTokenType();

        $tokenUpdate->addChild('paymentTokenID', $this->tokenModel->getTokenCode());
        if ($this->tokenModel->getTokenType() == self::TOKEN_SCOPE) {
            $tokenUpdate->addChild('authenticatedShopperID', $this->customer->getId());
        }
        $cardDetails = $tokenUpdate
            ->addChild('paymentInstrument')
            ->addChild('cardDetails');
        $expiryDate = $cardDetails
            ->addChild('expiryDate')
            ->addChild('date');
        $expiryDate['month'] = sprintf('%02d', $this->tokenModel->getCardExpiryMonth());
        $expiryDate['year'] = $this->tokenModel->getCardExpiryYear();

        $cardDetails->addChild('cardHolderName', $this->tokenModel->getCardholderName());

        return $tokenUpdate;
    }
}
