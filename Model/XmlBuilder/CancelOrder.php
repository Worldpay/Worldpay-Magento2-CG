<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

/**
 * Build xml for Capture request
 */
class CancelOrder
{
    const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    private $merchantCode;
    private $orderCode;
    private $currencyCode;
    private $amount;
    private $exponent;

    /**
     * Build xml for processing Request
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @param string $currencyCode
     * @param float $amount
     * @param float $exponent
     * @return SimpleXMLElement $xml
     */
    public function build($merchantCode, $orderCode, $currencyCode, $amount, $exponent, $paymentType = null)
    {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->exponent = $exponent;

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $modify = $this->_addModifyElement($xml);
        $orderModification = $this->_addOrderModificationElement($modify);
        $this->_addVoidSale($orderModification);

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
     * Add tag orderModification to xml
     *
     * @param SimpleXMLElement $modify
     * @return SimpleXMLElement $orderModification
     */
    private function _addOrderModificationElement($modify)
    {
        $orderModification = $modify->addChild('orderModification');
        $orderModification['orderCode'] = $this->orderCode;

        return $orderModification;
    }

    /**
     * Add tag capture to xml
     *
     * @param SimpleXMLElement $orderModification
     * @return SimpleXMLElement $capture
     */
    private function _addVoidSale($orderModification)
    {
        return $orderModification->addChild('cancel');
    }
}
