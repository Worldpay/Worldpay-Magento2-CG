<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

use Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecureConfig;
use \Sapient\Worldpay\Logger\WorldpayLogger;

class DirectOrder
{

    const EXPONENT = 2;
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
    private $cardAddress;
    protected $shopperEmail;
    protected $acceptHeader;
    protected $userAgentHeader;
    private $shippingAddress;
    private $billingAddress;
    protected $paResponse = null;
    private $echoData = null;
    private $shopperId;
    protected $threeDSecureConfig;
    protected $tokenRequestConfig;

    public function __construct(array $args = array())
    {
        $this->threeDSecureConfig = new \Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecure($args['threeDSecureConfig']['isDynamic3D'], $args['threeDSecureConfig']['is3DSecure']);
        $this->tokenRequestConfig = new \Sapient\Worldpay\Model\XmlBuilder\Config\TokenConfiguration($args['tokenRequestConfig']);
    }

    public function build(
        $merchantCode,
        $orderCode,
        $orderDescription,
        $currencyCode,
        $amount,
        $paymentDetails,
        $cardAddress,
        $shopperEmail,
        $acceptHeader,
        $userAgentHeader,
        $shippingAddress,
        $billingAddress,
        $shopperId
    ) {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->orderDescription = $orderDescription;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->paymentDetails = $paymentDetails;
        $this->cardAddress = $cardAddress;
        $this->shopperEmail = $shopperEmail;
        $this->acceptHeader = $acceptHeader;
        $this->userAgentHeader = $userAgentHeader;
        $this->shippingAddress = $shippingAddress;
        $this->billingAddress = $billingAddress;
        $this->shopperId = $shopperId;

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $submit = $this->_addSubmitElement($xml);
        $this->_addOrderElement($submit);

        return $xml;
    }

    public function build3DSecure(
        $merchantCode,
        $orderCode,
        $orderDescription,
        $currencyCode,
        $amount,
        $paymentDetails,
        $cardAddress,
        $shopperEmail,
        $acceptHeader,
        $userAgentHeader,
        $shippingAddress,
        $billingAddress,
        $shopperId,
        $paResponse,
        $echoData
    ) {
         $this->merchantCode = $merchantCode;
        $this->paResponse = $paResponse;
        $this->echoData = $echoData;
        $this->orderCode = $orderCode;
        $this->paymentDetails = $paymentDetails;
        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $submit = $this->_addSubmitElement($xml);
        $this->_addOrderElement($submit);
        return $xml;
    }

    private function _addSubmitElement($xml)
    {
        return $xml->addChild('submit');
    }

    private function _addOrderElement($submit)
    {
        $order = $submit->addChild('order');
        $order['orderCode'] = $this->orderCode;
        if ($this->paResponse) {
            $info3DSecure = $order->addChild('info3DSecure');
            $info3DSecure->addChild('paResponse', $this->paResponse);
            $session = $order->addChild('session');
            $session['id'] = $this->paymentDetails['sessionId'];
            return $order;
        }
        $this->_addDescriptionElement($order);
        $this->_addAmountElement($order);
        $this->_addPaymentDetailsElement($order);
        $this->_addShopperElement($order);
        $this->_addShippingElement($order);
        $this->_addBillingElement($order);
      

        if ($this->echoData) {
            $order->addChild('echoData', $this->echoData);
        }

        $this->_addDynamic3DSElement($order);
        $this->_addCreateTokenElement($order);
        $this->_addDynamicInteractionTypeElement($order);

       return $order;
    }

    private function _addDescriptionElement($order)
    {
        $description = $order->addChild('description');
        $this->_addCDATA($description, $this->orderDescription);
    }

    private function _addAmountElement($order)
    {
        $amountElement = $order->addChild('amount');
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = self::EXPONENT;
        $amountElement['value'] = $this->_amountAsInt($this->amount);
    }

    private function _addDynamicInteractionTypeElement($order)
    {
        if(self::ALLOW_INTERACTION_TYPE == $this->paymentDetails['dynamicInteractionType']){
        $interactionElement = $order->addChild('dynamicInteractionType');
        $interactionElement['type'] = $this->paymentDetails['dynamicInteractionType'];
        }
    }

    private function _addDynamic3DSElement($order)
    {
        if (! $this->threeDSecureConfig->isDynamic3DEnabled()) {
            return;
        }

        $threeDSElement = $order->addChild('dynamic3DS');
        if ($this->threeDSecureConfig->is3DSecureCheckEnabled()) {
            $threeDSElement['overrideAdvice'] = self::DYNAMIC3DS_DO3DS;
        } else {
            $threeDSElement['overrideAdvice'] = self::DYNAMIC3DS_NO3DS;
        }
    }

    private function _addCreateTokenElement($order)
    {
        if (! $this->tokenRequestConfig->istokenizationIsEnabled()) {
            return;
        }

       $createTokenElement = $order->addChild('createToken');
       $createTokenElement['tokenScope'] = self::TOKEN_SCOPE;

        if ($this->tokenRequestConfig->getTokenReason()) {
            $createTokenElement->addChild(
                'tokenReason',
                $this->tokenRequestConfig->getTokenReason()
            );
        }
    }


    protected function _addPaymentDetailsForTokenOrder($paymentDetailsElement)
    {
        if (isset($this->paymentDetails['encryptedData'])) {
            $cseElement = $this->_addCseElement($paymentDetailsElement);
        }

        $tokenNode = $paymentDetailsElement->addChild($this->paymentDetails['paymentType']);
        $tokenNode['tokenScope'] = 'shopper';
        $tokenNode->addChild('paymentTokenID', $this->paymentDetails['tokenCode']);

        if (isset($this->paymentDetails['cvc'])) {
            $tokenNode
                ->addChild('paymentInstrument')
                ->addChild('cardDetails')
                ->addChild('cvc', $this->paymentDetails['cvc']);
        }
    }

    protected function _addPaymentDetailsForCreditCardOrder($paymentDetailsElement)
    {
        if ($this->paymentDetails['cseEnabled']) {
            $cseElement = $this->_addCseElement($paymentDetailsElement);
            $cardAddress = $cseElement->addChild('cardAddress');
        } else {
            $paymentTypeElement = $this->_addPaymentTypeElement($paymentDetailsElement);
            $cardAddress = $paymentTypeElement->addChild('cardAddress');
        }

        $this->_addAddressElement(
            $cardAddress,
            $this->cardAddress['firstName'],
            $this->cardAddress['lastName'],
            $this->cardAddress['street'],
            $this->cardAddress['postalCode'],
            $this->cardAddress['city'],
            $this->cardAddress['countryCode']
        );
    }

    protected function _addPaymentDetailsElement($order)
    {
        $paymentDetailsElement = $order->addChild('paymentDetails');
         if (isset($this->paymentDetails['tokenCode'])) {
            $this->_addPaymentDetailsForTokenOrder($paymentDetailsElement);
        } else {
            $this->_addPaymentDetailsForCreditCardOrder($paymentDetailsElement);
        }
        $session = $paymentDetailsElement->addChild('session');
        $session['id'] = $this->paymentDetails['sessionId'];
        $session['shopperIPAddress'] = $this->paymentDetails['shopperIpAddress'];

        if ($this->paResponse) {
            $info3DSecure = $paymentDetailsElement->addChild('info3DSecure');
            $info3DSecure->addChild('paResponse', $this->paResponse);
        }
    }

    protected function _addCseElement($paymentDetailsElement)
    {
        $cseElement = $paymentDetailsElement->addChild('CSE-DATA');
        $cseElement->addChild('encryptedData', $this->paymentDetails['encryptedData']);

        return $cseElement;
    }

    protected function _addPaymentTypeElement($paymentDetailsElement)
    {
        $paymentTypeElement = $paymentDetailsElement->addChild($this->paymentDetails['paymentType']);
        $paymentTypeElement->addChild('cardNumber', $this->paymentDetails['cardNumber']);

        $expiryDate = $paymentTypeElement->addChild('expiryDate');
        $date = $expiryDate->addChild('date');
        $date['month'] = $this->paymentDetails['expiryMonth'];
        $date['year'] = $this->paymentDetails['expiryYear'];

        $paymentTypeElement->addChild('cardHolderName', $this->paymentDetails['cardHolderName']);

        if (isset($this->paymentDetails['cvc'])) {
            $paymentTypeElement->addChild('cvc', $this->paymentDetails['cvc']);
        }

        return $paymentTypeElement;
    }

    /**
     * @param $order
     * @return SimpleXMLElement
     */
    protected function _addShopperElement($order)
    {
        $shopper = $order->addChild('shopper');

        $shopper->addChild('shopperEmailAddress', $this->shopperEmail);

        if ($this->tokenRequestConfig->istokenizationIsEnabled()) {
            $shopper->addChild('authenticatedShopperID', $this->shopperId);
        }elseif (isset($this->paymentDetails['tokenCode'])) {
            $shopper->addChild('authenticatedShopperID', $this->paymentDetails['customerId']);
        }

        $browser = $shopper->addChild('browser');

        $acceptHeader = $browser->addChild('acceptHeader');
        $this->_addCDATA($acceptHeader, $this->acceptHeader);

        $userAgentHeader = $browser->addChild('userAgentHeader');
        $this->_addCDATA($userAgentHeader, $this->userAgentHeader);

        return $shopper;
    }

    private function _addShippingElement($order)
    {
        $shippingAddress = $order->addChild('shippingAddress');
        $this->_addAddressElement(
            $shippingAddress,
            $this->shippingAddress['firstName'],
            $this->shippingAddress['lastName'],
            $this->shippingAddress['street'],
            $this->shippingAddress['postalCode'],
            $this->shippingAddress['city'],
            $this->shippingAddress['countryCode']
        );
    }

    private function _addBillingElement($order)
    {
        $billingAddress = $order->addChild('billingAddress');
        $this->_addAddressElement(
            $billingAddress,
            $this->billingAddress['firstName'],
            $this->billingAddress['lastName'],
            $this->billingAddress['street'],
            $this->billingAddress['postalCode'],
            $this->billingAddress['city'],
            $this->billingAddress['countryCode']
        );
    }

    private function _addAddressElement($parentElement, $firstName, $lastName, $street, $postalCode, $city, $countryCode)
    {
        $address = $parentElement->addChild('address');

        $firstNameElement = $address->addChild('firstName');
        $this->_addCDATA($firstNameElement, $firstName);

        $lastNameElement = $address->addChild('lastName');
        $this->_addCDATA($lastNameElement, $lastName);

        $streetElement = $address->addChild('street');
        $this->_addCDATA($streetElement, $street);

        $postalCodeElement = $address->addChild('postalCode');
        $this->_addCDATA($postalCodeElement, $postalCode);

        $cityElement = $address->addChild('city');
        $this->_addCDATA($cityElement, $city);

        $countryCodeElement = $address->addChild('countryCode');
        $this->_addCDATA($countryCodeElement, $countryCode);
    }

    protected function _addCDATA($element, $content)
    {
        $node = dom_import_simplexml($element);
        $no   = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($content));
    }

    private function _amountAsInt($amount)
    {
        return round($amount, self::EXPONENT, PHP_ROUND_HALF_EVEN) * pow(10, self::EXPONENT);
    }
}
