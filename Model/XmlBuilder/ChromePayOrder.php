<?php
namespace Sapient\Worldpay\Model\XmlBuilder;

use SimpleXMLElement;

class ChromePayOrder extends AbstractXmlBuilder
{

    public const TOKEN_SCOPE = 'shopper';

    protected array $orderParameters;


    public function build(array $orderParameters): \SimpleXMLElement
    {
        foreach (['shippingAddress', 'billingAddress'] as $addressKey) {
            $address = $orderParameters[$addressKey];
            $orderParameters[$addressKey] = [
                'firstName' => $address->recipient,
                'street' => $address->addressLine[0],
                'postalCode' => $address->postalCode ?: '00000',
                'city' => $address->city,
                'countryCode' => $address->country
            ];
        }
        $this->orderParameters = $orderParameters;

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->orderParameters['merchantCode'];
        $xml['version'] = '1.4';

        $submit = $xml->addChild('submit');
        $this->_addOrderElement($submit);

        return $xml;
    }

    protected function _addOrderElement(SimpleXMLElement $submit): void
    {
        $order = $submit->addChild('order');
        $order['orderCode'] = $this->orderParameters['orderCode'];
        if ($this->orderParameters['captureDelay'] != "") {
            $order['captureDelay'] = $this->orderParameters['captureDelay'];
        }
        $this->_addDescriptionElement($order);
        $this->_addAmountElement($order);
        $this->_addOrderContentElement($order);
        $this->_addPaymentDetailsElement($order);
        $this->_addShopperElement($order);
        $this->_addShippingElement($order);
        $this->_addBillingElement($order);
    }

    private function _addAmountElement(SimpleXMLElement $order): void
    {
        $amountElement = $order->addChild('amount');
        $amountElement['currencyCode'] = $this->orderParameters['currencyCode'];
        $amountElement['exponent'] = $this->orderParameters['exponent'];
        $amountElement['value'] = $this->_amountAsInt($this->orderParameters['amount']);
    }

    protected function _addPaymentDetailsElement(SimpleXMLElement $order): void
    {
        $paymentDetailsElement = $order->addChild('paymentDetails');

        $this->_addPaymentDetailsForCreditCardOrder($paymentDetailsElement);
    }

    protected function _addPaymentDetailsForCreditCardOrder(SimpleXMLElement $paymentDetailsElement): void
    {
        $paymentTypeElement = $this->_addPaymentTypeElement($paymentDetailsElement);
        $cardAddress = $paymentTypeElement->addChild('cardAddress');

        $this->_addAddressElement($cardAddress, $this->orderParameters['billingAddress']);
    }

    protected function _addPaymentTypeElement(SimpleXMLElement $paymentDetailsElement): SimpleXMLElement
    {
        $paymentDetails = $this->orderParameters['paymentDetails'];
        $paymentTypeElement = $paymentDetailsElement->addChild($this->orderParameters['paymentType']);
        $paymentTypeElement->addChild('cardNumber', $paymentDetails->cardNumber);

        $expiryDate = $paymentTypeElement->addChild('expiryDate');
        $date = $expiryDate->addChild('date');
        $date['month'] = $paymentDetails->expiryMonth;
        $date['year'] = $paymentDetails->expiryYear;

        $paymentTypeElement->addChild('cardHolderName', $paymentDetails->cardholderName);

        if (isset($paymentDetails->cardSecurityCode)) {
            $paymentTypeElement->addChild('cvc', $paymentDetails->cardSecurityCode);
        }

        return $paymentTypeElement;
    }

    protected function _addShopperElement(SimpleXMLElement $order): void
    {
        $shopper = $order->addChild(self::TOKEN_SCOPE);
        $shopper->addChild('shopperEmailAddress', $this->orderParameters['shopperEmail']);
    }
}
