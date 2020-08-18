<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

use \Sapient\Worldpay\Logger\WorldpayLogger;

/**
 * Build xml for Direct Order request
 */
class ChromePayOrder
{

    const ALLOW_INTERACTION_TYPE = 'MOTO';
    const DYNAMIC3DS_DO3DS = 'do3DS';
    const DYNAMIC3DS_NO3DS = 'no3DS';
    const TOKEN_SCOPE = 'shopper';
    const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    private $merchantCode;
    private $orderCode;
    private $orderDescription;
    private $currencyCode;
    private $amount;
    protected $paymentDetails;
    //private $cardAddress;
    protected $shopperEmail;
    protected $acceptHeader;
    protected $userAgentHeader;
    private $shippingAddress;
    private $billingAddress;
    private $echoData = null;
    private $exponent;

    /**
     * @var Sapient\Worldpay\Model\XmlBuilder\Config\TokenConfiguration
     */
    protected $tokenRequestConfig;

    /**
     * Build xml for processing Request
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @param string $orderDescription
     * @param string $currencyCode
     * @param float $amount
     * @param array $paymentDetails
     * @param array $cardAddress
     * @param string $shopperEmail
     * @param string $acceptHeader
     * @param string $userAgentHeader
     * @param string $shippingAddress
     * @param float $billingAddress
     * @param string $shopperId
     * @return SimpleXMLElement $xml
     */
    public function build(
        $merchantCode,
        $orderCode,
        $orderDescription,
        $currencyCode,
        $amount,
        $paymentType,
        $paymentDetails,
        //$cardAddress,
        //$acceptHeader,
        //$userAgentHeader,
        $shippingAddress,
        $billingAddress,
        //$shopperId,
        $shopperEmail,
        $exponent
    ) {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->orderDescription = $orderDescription;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->paymentType = $paymentType;
        $this->paymentDetails = $paymentDetails;
        //$this->cardAddress = $cardAddress;
        $this->shippingAddress = $shippingAddress;
        $this->billingAddress = $billingAddress;
        $this->shopperEmail = $shopperEmail;
        $this->exponent = $exponent;
        //$this->acceptHeader = $acceptHeader;
        //$this->userAgentHeader = $userAgentHeader;

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $submit = $this->_addSubmitElement($xml);
        $this->_addOrderElement($submit);

        return $xml;
    }

    /**
     * Add submit tag to xml
     *
     * @param SimpleXMLElement $xml
     * @return SimpleXMLElement
     */
    private function _addSubmitElement($xml)
    {
        return $xml->addChild('submit');
    }

    /**
     * Add order and its child tag to xml
     *
     * @param SimpleXMLElement $submit
     * @return SimpleXMLElement $order
     */
    private function _addOrderElement($submit)
    {
        $order = $submit->addChild('order');
        $order['orderCode'] = $this->orderCode;
        $this->_addDescriptionElement($order);
        $this->_addAmountElement($order);
        $this->_addPaymentDetailsElement($order);
        $this->_addShopperElement($order);
        $this->_addShippingElement($order);
        $this->_addBillingElement($order);

        if ($this->echoData) {
            $order->addChild('echoData', $this->echoData);
        }

        //$this->_addCreateTokenElement($order);
        //$this->_addDynamicInteractionTypeElement($order);

        return $order;
    }

    /**
     * Add description  tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addDescriptionElement($order)
    {
        $description = $order->addChild('description');
        $this->_addCDATA($description, $this->orderDescription);
    }

    /**
     * Add amount and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addAmountElement($order)
    {
        $amountElement = $order->addChild('amount');
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = $this->exponent;
        $amountElement['value'] = $this->_amountAsInt($this->amount);
    }
    
    /**
     * Add paymentDetails and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    protected function _addPaymentDetailsElement($order)
    {
        $paymentDetailsElement = $order->addChild('paymentDetails');
        
        $this->_addPaymentDetailsForCreditCardOrder($paymentDetailsElement);
        //$session = $paymentDetailsElement->addChild('session');
        //$session['id'] = $this->paymentDetails['sessionId'];
        //$session['shopperIPAddress'] = $this->paymentDetails['shopperIpAddress'];
    }
    
    /**
     * Add dynamicInteractionType and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addDynamicInteractionTypeElement($order)
    {
        if (self::ALLOW_INTERACTION_TYPE == $this->paymentDetails['dynamicInteractionType']) {
            $interactionElement = $order->addChild('dynamicInteractionType');
            $interactionElement['type'] = $this->paymentDetails['dynamicInteractionType'];
        }
    }

    /**
     * Add createToken and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addCreateTokenElement($order)
    {

        $createTokenElement = $order->addChild('createToken');
        $createTokenElement['tokenScope'] = self::TOKEN_SCOPE;

        if ($this->tokenRequestConfig->getTokenReason($this->orderCode)) {
            $createTokenElement->addChild(
                'tokenReason',
                $this->tokenRequestConfig->getTokenReason($this->orderCode)
            );
        }
    }

    /**
     * Add paymentDetailsElement and its child tag to xml
     *
     * @param SimpleXMLElement $paymentDetailsElement
     */
    protected function _addPaymentDetailsForCreditCardOrder($paymentDetailsElement)
    {
        $paymentTypeElement = $this->_addPaymentTypeElement($paymentDetailsElement);
        $cardAddress = $paymentTypeElement->addChild('cardAddress');

        $this->_addAddressElement(
            $cardAddress,
            $this->billingAddress->recipient,
            $this->billingAddress->addressLine[0],
            $this->billingAddress->postalCode,
            $this->billingAddress->city,
            $this->billingAddress->country
        );
    }

    /**
     * Add CSE-DATA and its child tag to xml
     *
     * @param SimpleXMLElement $paymentDetailsElement
     * @return SimpleXMLElement cseElement
     */
    protected function _addCseElement($paymentDetailsElement)
    {
        $cseElement = $paymentDetailsElement->addChild('CSE-DATA');
        $cseElement->addChild('encryptedData', $this->paymentDetails['encryptedData']);

        return $cseElement;
    }

    /**
     * Add paymentType and its child tag to xml
     *
     * @param SimpleXMLElement $paymentDetailsElement
     * @return SimpleXMLElement $paymentTypeElement
     */
    protected function _addPaymentTypeElement($paymentDetailsElement)
    {
        $paymentTypeElement = $paymentDetailsElement->addChild($this->paymentType);
        $paymentTypeElement->addChild('cardNumber', $this->paymentDetails->cardNumber);

        $expiryDate = $paymentTypeElement->addChild('expiryDate');
        $date = $expiryDate->addChild('date');
        $date['month'] = $this->paymentDetails->expiryMonth;
        $date['year'] = $this->paymentDetails->expiryYear;

        $paymentTypeElement->addChild('cardHolderName', $this->paymentDetails->cardholderName);

        if (isset($this->paymentDetails->cardSecurityCode)) {
            $paymentTypeElement->addChild('cvc', $this->paymentDetails->cardSecurityCode);
        }

        return $paymentTypeElement;
    }

    /**
     * Add shopper and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    protected function _addShopperElement($order)
    {
        $shopper = $order->addChild(self::TOKEN_SCOPE);

        $shopper->addChild('shopperEmailAddress', $this->shopperEmail);

        //$shopper->addChild('authenticatedShopperID', $this->paymentDetails['customerId']);

        $browser = $shopper->addChild('browser');

//        $acceptHeader = $browser->addChild('acceptHeader');
//        $this->_addCDATA($acceptHeader, $this->acceptHeader);
//
//        $userAgentHeader = $browser->addChild('userAgentHeader');
//        $this->_addCDATA($userAgentHeader, $this->userAgentHeader);

        return $shopper;
    }

    /**
     * Add shippingAddress and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addShippingElement($order)
    {
        $shippingAddress = $order->addChild('shippingAddress');
        $this->_addAddressElement(
            $shippingAddress,
            $this->shippingAddress->recipient,
            $this->shippingAddress->addressLine[0],
            $this->shippingAddress->postalCode,
            $this->shippingAddress->city,
            $this->shippingAddress->country
        );
    }

    /**
     * Add billing and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addBillingElement($order)
    {
        $billingAddress = $order->addChild('billingAddress');
        $this->_addAddressElement(
            $billingAddress,
            $this->billingAddress->recipient,
            $this->billingAddress->addressLine[0],
            $this->billingAddress->postalCode,
            $this->billingAddress->city,
            $this->billingAddress->country
        );
    }

    /**
     * Add address and its child tag to xml
     *
     * @param SimpleXMLElement $parentElement
     * @param string $firstName
     * @param string $lastName
     * @param string $street
     * @param string $postalCode
     * @param string $city
     * @param string $countryCode
     */
    private function _addAddressElement($parentElement, $firstName, $street, $postalCode, $city, $countryCode)
    {
        $address = $parentElement->addChild('address');

        $firstNameElement = $address->addChild('firstName');
        $this->_addCDATA($firstNameElement, $firstName);

//        $lastNameElement = $address->addChild('lastName');
//        $this->_addCDATA($lastNameElement, $lastName);

        $streetElement = $address->addChild('street');
        $this->_addCDATA($streetElement, $street);

        $postalCodeElement = $address->addChild('postalCode');
        $this->_addCDATA($postalCodeElement, $postalCode);

        $cityElement = $address->addChild('city');
        $this->_addCDATA($cityElement, $city);

        $countryCodeElement = $address->addChild('countryCode');
        $this->_addCDATA($countryCodeElement, $countryCode);
    }

    /**
     * @param SimpleXMLElement $element
     * @param string $content
     */
    protected function _addCDATA($element, $content)
    {
        $node = dom_import_simplexml($element);
        $no   = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($content));
    }

    /**
     * @param float $amount
     * @return int
     */
    private function _amountAsInt($amount)
    {
        return round($amount, $this->exponent, PHP_ROUND_HALF_EVEN) * pow(10, $this->exponent);
    }
}
