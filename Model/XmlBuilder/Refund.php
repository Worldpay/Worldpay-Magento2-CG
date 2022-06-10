<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

/**
 * Build xml for Refund request
 */
class Refund
{

    public const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    /**
     * [$merchantCode description]
     * @var [type]
     */
    private $merchantCode;
    /**
     * [$orderCode description]
     * @var [type]
     */
    private $orderCode;
    /**
     * [$currencyCode description]
     * @var [type]
     */
    private $currencyCode;
    /**
     * [$amount description]
     * @var [type]
     */
    private $amount;
    /**
     * [$refundReference description]
     * @var [type]
     */
    private $refundReference;
    /**
     * [$exponent description]
     * @var [type]
     */
    private $exponent;
    
    /**
     * [build description]
     *
     * @param  [type] $merchantCode    [description]
     * @param  [type] $orderCode       [description]
     * @param  [type] $currencyCode    [description]
     * @param  [type] $amount          [description]
     * @param  [type] $refundReference [description]
     * @param  [type] $exponent        [description]
     * @return [type]                  [description]
     */
    public function build($merchantCode, $orderCode, $currencyCode, $amount, $refundReference, $exponent)
    {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->refundReference = $refundReference;
        $this->exponent = $exponent;

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $modify = $this->_addModifyElement($xml);
        $orderModification = $this->_addOrderModificationElement($modify);
        $this->_addRefundElement($orderModification);

        return $xml;
    }

    /**
     * Code to add modify tag
     *
     * @param SimpleXMLElement $xml
     * @return SimpleXMLElement
     */
    private function _addModifyElement($xml)
    {
        return $xml->addChild('modify');
    }

    /**
     * Add order modification element
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
     * Add refund element details
     *
     * @param SimpleXMLElement $orderModification
     */
    private function _addRefundElement($orderModification)
    {
        $refund = $orderModification->addChild('refund');
        $refund["reference"] = $this->refundReference ;

        $amountElement = $refund->addChild('amount');
        $amountElement['value'] = $this->_amountAsInt($this->amount);
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = $this->exponent;
    }

    /**
     * Retrieve amount value
     *
     * @param float $amount
     * @return int
     */
    private function _amountAsInt($amount)
    {
        return round($amount, $this->exponent, PHP_ROUND_HALF_EVEN) * pow(10, $this->exponent);
    }
}
