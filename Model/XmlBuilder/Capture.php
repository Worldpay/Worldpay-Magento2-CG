<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

/**
 * Build xml for Capture request
 */
class Capture
{
    const EXPONENT = 2;
    const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    private $merchantCode;
    private $orderCode;
    private $currencyCode;
    private $amount;

    /**
     * Build xml for processing Request
     *
     * @param string $merchantCode
     * @param string $orderCode     
     * @param string $currencyCode
     * @param float $amount    
     * @return SimpleXMLElement $xml
     */
    public function build($merchantCode, $orderCode, $currencyCode, $amount)
    {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $modify = $this->_addModifyElement($xml);
        $orderModification = $this->_addOrderModificationElement($modify);
        $this->_addCaptureElement($orderModification);

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
     */
    private function _addCaptureElement($orderModification)
    {
        $capture = $orderModification->addChild('capture');

        // data
        $today = new \DateTime();
        $date = $capture->addChild('date');
        $date['dayOfMonth'] = $today->format('d');
        $date['month'] = $today->format('m');
        $date['year'] = $today->format('Y');

        $amountElement = $capture->addChild('amount');
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = self::EXPONENT;
        $amountElement['value'] = $this->_amountAsInt($this->amount);
    }

    /**
     * @param float $amount
     * @return int
     */
    private function _amountAsInt($amount)
    {
        return round($amount, self::EXPONENT, PHP_ROUND_HALF_EVEN) * pow(10, self::EXPONENT);
    }
}
