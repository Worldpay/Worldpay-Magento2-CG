<?php
namespace Sapient\Worldpay\Model\XmlBuilder;

use SimpleXMLElement;

abstract class AbstractXmlBuilder
{
    public const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    protected function _addOrderContentElement(SimpleXMLElement $order): void
    {
        $orderContent = $order->addChild('orderContent');
        $this->_addCDATA($orderContent, $this->orderParameters['orderContent']);
    }

    protected function _addShippingElement(SimpleXMLElement $order): void
    {
        $shippingAddressElement = $order->addChild('shippingAddress');
        $this->_addAddressElement($shippingAddressElement, $this->orderParameters['shippingAddress']);
    }

    protected function _addBillingElement(SimpleXMLElement $order): void
    {
        $billingAddress = $order->addChild('billingAddress');
        $this->_addAddressElement($billingAddress, $this->orderParameters['billingAddress']);
    }

    protected function _addAddressElement(SimpleXMLElement $parentElement, array $address): void
    {
        $addressElement = $parentElement->addChild('address');
        $fields = [
            'firstName' => null,
            'lastName' => null,
            'street' => null,
            'postalCode' => '00000',
            'city' => null,
            'state' => null,
            'countryCode' => null,
            'telephoneNumber' => null
        ];

        foreach ($fields as $field => $default) {
            if ($field === 'state' && empty($address[$field])) {
                continue;
            }
            $value = $address[$field] ?? $default;

            $element = $addressElement->addChild($field);
            $this->_addCDATA($element, $value);
        }
    }

    protected function _addFraudSightData(SimpleXMLElement $order): void
    {
        $fraudsightData = $order->addChild('FraudSightData');
        $shopperFields = $fraudsightData->addChild('shopperFields');
        $shopperFields->addChild('shopperName', $this->cusDetails['shopperName']);
        if (isset($this->cusDetails['shopperId'])) {
            $shopperFields->addChild('shopperId', $this->cusDetails['shopperId']);
        }
        if (isset($this->cusDetails['birthDate'])) {
            $shopperDOB = $shopperFields->addChild('birthDate');
            $dateElement= $shopperDOB->addChild('date');
            $dateElement['dayOfMonth'] = date("d", strtotime($this->cusDetails['birthDate']));
            $dateElement['month'] = date("m", strtotime($this->cusDetails['birthDate']));
            $dateElement['year'] = date("Y", strtotime($this->cusDetails['birthDate']));
        }
        $shopperAddress = $shopperFields->addChild('shopperAddress');
        $this->_addAddressElement($shopperAddress, $this->orderParameters['billingAddress']);
    }


    protected function _amountAsInt(float $amount): int
    {
        return round($amount, $this->orderParameters['exponent'], PHP_ROUND_HALF_EVEN) * pow(10, $this->orderParameters['exponent']);
    }

    protected function _addCDATA(SimpleXMLElement $element, ?string $content): void
    {
        if (is_null($content)) {
            return;
        }

        $node = dom_import_simplexml($element);
        $no   = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($content));
    }
}
