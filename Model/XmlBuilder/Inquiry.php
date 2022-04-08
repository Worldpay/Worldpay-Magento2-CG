<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

/**
 * Build xml for Inquiry request
 */
class Inquiry
{

    public const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    private $merchantCode;
    private $orderCode;

    /**
     * Build xml for processing Request
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @return SimpleXMLElement $xml
     */
    public function build($merchantCode, $orderCode)
    {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $inquiry = $this->_addInquiryElement($xml);
        $this->_addOrderInquiryElement($inquiry);

        return $xml;
    }

    /**
     * Add inquiry tag to xml
     *
     * @param SimpleXMLElement $xml
     * @return SimpleXMLElement
     */
    private function _addInquiryElement($xml)
    {
        return $xml->addChild('inquiry');
    }

    /**
     * Add orderInquiry tag to xml
     *
     * @param SimpleXMLElement $modify
     * @return SimpleXMLElement
     */
    private function _addOrderInquiryElement($modify)
    {
        $orderInquiry = $modify->addChild('orderInquiry');
        $orderInquiry['orderCode'] = $this->orderCode;

        return $orderInquiry;
    }
}
