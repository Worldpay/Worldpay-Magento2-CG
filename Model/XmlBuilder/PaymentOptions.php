<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

/**
 * Build xml for Inquiry request
 */
class PaymentOptions
{

    const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    private $merchantCode;
    private $countryCode;

    /**
     * Build xml for processing Request
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @return SimpleXMLElement $xml
     */
    public function build($merchantCode, $countryCode)
    {
        $this->merchantCode = $merchantCode;
        $this->countryCode = $countryCode;

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $inquiry = $this->_addInquiryElement($xml);
        $this->_addOptionInquiryElement($inquiry);

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
     * Add optionInquiry tag to xml
     *
     * @param SimpleXMLElement $modify
     * @return SimpleXMLElement
     */
    private function _addOptionInquiryElement($modify)
    {
        $optionInquiry = $modify->addChild('paymentOptionsInquiry');
        $optionInquiry['countryCode'] = $this->countryCode;

        return $optionInquiry;
    }
}
