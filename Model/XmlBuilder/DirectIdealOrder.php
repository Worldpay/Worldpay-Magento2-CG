<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

use Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecureConfig;

/**
 * Build xml for RedirectOrder request
 */
class DirectIdealOrder
{
    public const DYNAMIC3DS_DO3DS = 'do3DS';
    public const DYNAMIC3DS_NO3DS = 'no3DS';
    public const TOKEN_SCOPE = 'shopper';
    public const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    /**
     * @var string
     */
    private $merchantCode;
    /**
     * @var string
     */
    private $orderCode;
    /**
     * @var string
     */
    private $orderDescription;
    /**
     * @var string
     */
    private $currencyCode;
    /**
     * @var float
     */
    private $amount;
    /**
     * @var string
     */
    private $orderContent;
    /**
     * @var string
     */
    private $paymentType;
    /**
     * @var string
     */
    private $shopperEmail;
    /**
     * @var string
     */
    private $statementNarrative;
    /**
     * @var string
     */
    private $acceptHeader;
    /**
     * @var string
     */
    private $userAgentHeader;
    /**
     * @var array
     */
    private $shippingAddress;
    /**
     * @var array
     */
    private $billingAddress;
    /**
     * @var float
     */
    private $paymentPagesEnabled;
    /**
     * @var string
     */
    private $installationId;
    /**
     * @var string
     */
    private $hideAddress;
    /**
     * @var array|string
     */
    private $exponent;

    /**
     * @var Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecure
     */
    private $threeDSecureConfig;
    /**
     * @var Sapient\Worldpay\Model\XmlBuilder\Config\TokenConfiguration
     */
    private $tokenRequestConfig;
     /**
      * @var string $captureDelay
      */
    private $captureDelay;

    /**
     * @var string
     */
    private $callbackurl;
    /**
     * @var string
     */
    private $bankcode;
     /**
      * @var string
      */
    private $shopperId;
    /**
     * Constructor
     *
     * @param array $args
     */
    public function __construct(array $args = [])
    {
         $this->threeDSecureConfig = new \Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecure();

        $this->tokenRequestConfig = new \Sapient\Worldpay\Model\XmlBuilder\Config\TokenConfiguration(
            $args['tokenRequestConfig']
        );
        $this->shopperId = $args['shopperId'];
    }

    /**
     * Build xml for processing Request
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @param string $orderDescription
     * @param string $currencyCode
     * @param float $amount
     * @param string $orderContent
     * @param string $paymentType
     * @param string $shopperEmail
     * @param string $statementNarrative
     * @param string $acceptHeader
     * @param string $userAgentHeader
     * @param string $shippingAddress
     * @param string $billingAddress
     * @param float $paymentPagesEnabled
     * @param string $installationId
     * @param string $hideAddress
     * @param string $callbackurl
     * @param string $ccbank
     * @param array|string $exponent
     * @param string $captureDelay
     * @return SimpleXMLElement $xml
     */
    public function build(
        $merchantCode,
        $orderCode,
        $orderDescription,
        $currencyCode,
        $amount,
        $orderContent,
        $paymentType,
        $shopperEmail,
        $statementNarrative,
        $acceptHeader,
        $userAgentHeader,
        $shippingAddress,
        $billingAddress,
        $paymentPagesEnabled,
        $installationId,
        $hideAddress,
        $callbackurl,
        $ccbank,
        $exponent,
        $captureDelay
    ) {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->orderDescription = $orderDescription;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->orderContent = $orderContent;
        $this->paymentType = $paymentType;
        $this->shopperEmail = $shopperEmail;
        $this->statementNarrative = $statementNarrative;
        $this->acceptHeader = $acceptHeader;
        $this->userAgentHeader = $userAgentHeader;
        $this->shippingAddress = $shippingAddress;
        $this->billingAddress = $billingAddress;
        $this->paymentPagesEnabled = $paymentPagesEnabled;
        $this->installationId = $installationId;
        $this->hideAddress = $hideAddress;
        $this->callbackurl = $callbackurl;
        $this->bankcode = $ccbank;
        $this->exponent = $exponent;
        $this->captureDelay = $captureDelay;

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
     * Add order tag to xml
     *
     * @param SimpleXMLElement $submit
     * @return SimpleXMLElement $order
     */
    private function _addOrderElement($submit)
    {
        $order = $submit->addChild('order');
        $order['orderCode'] = $this->orderCode;

        if ($this->paymentPagesEnabled) {
            $order['installationId'] = $this->installationId;

            $order['fixContact'] = 'true';
            $order['hideContact'] = 'true';

            if ($this->hideAddress) {
                $order['fixContact'] = 'false';
                $order['hideContact'] = 'false';
            }
        }
        if ($this->captureDelay!="") {
            $order['captureDelay'] = $this->captureDelay;
        }
        $this->_addDescriptionElement($order);
        $this->_addAmountElement($order);
        $this->_addOrderContentElement($order);
        $this->_addPaymentDetailsElement($order);
        $this->_addShippingElement($order);
        $this->_addBillingElement($order);
        $this->_addDynamic3DSElement($order);
        $this->_addCreateTokenElement($order);
        if (!empty($this->statementNarrative)) {
            $this->_addStatementNarrativeElement($order);
        }
        return $order;
    }

    /**
     * Add description tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addDescriptionElement($order)
    {
        $description = $order->addChild('description');
        if (!empty($this->statementNarrative)) {
            $statement = substr($this->statementNarrative, 0, 35);
            $this->_addCDATA($description, $statement);
        } else {
            $this->_addCDATA($description, $this->orderDescription);
        }
    }

    /**
     * Add OrderContent tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addOrderContentElement($order)
    {
        $orderContent = $order->addChild('orderContent');
        $this->_addCDATA($orderContent, $this->orderContent);
    }

    /**
     * Add amount tag to xml
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
     * Add dynamicInteractionType and its attribute tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addDynamic3DSElement($order)
    {
        if ($this->threeDSecureConfig->isDynamic3DEnabled() === false) {
            return;
        }

        $threeDSElement = $order->addChild('dynamic3DS');
        if ($this->threeDSecureConfig->is3DSecureCheckEnabled()) {
            $threeDSElement['overrideAdvice'] = self::DYNAMIC3DS_DO3DS;
        } else {
            $threeDSElement['overrideAdvice'] = self::DYNAMIC3DS_NO3DS;
        }
    }

    /**
     * Add createToken and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addCreateTokenElement($order)
    {
        if (! $this->tokenRequestConfig->istokenizationIsEnabled()) {
            return;
        }

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
     * Add paymentDetails and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addPaymentDetailsElement($order)
    {
        $paymentdetails = $order->addChild('paymentDetails');
        $paymenttype = $paymentdetails->addChild($this->paymentType);
    //    $paymenttype['shopperBankCode'] = $this->bankcode;
        $paymenttype->addChild('successURL', $this->callbackurl['successURL']);
        $paymenttype->addChild('failureURL', $this->callbackurl['failureURL']);
        $paymenttype->addChild('cancelURL', $this->callbackurl['cancelURL']);
        $paymenttype->addChild('pendingURL', $this->callbackurl['pendingURL']);
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
            $this->shippingAddress['firstName'],
            $this->shippingAddress['lastName'],
            $this->shippingAddress['street'],
            $this->shippingAddress['postalCode'],
            $this->shippingAddress['city'],
            $this->shippingAddress['countryCode']
        );
    }

     /**
      * Add _addStatementNarrativeElement to xml
      *
      * @param SimpleXMLElement $order
      */
    private function _addStatementNarrativeElement($order)
    {
         $order->addChild('statementNarrative', $this->statementNarrative);
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
            $this->billingAddress['firstName'],
            $this->billingAddress['lastName'],
            $this->billingAddress['street'],
            $this->billingAddress['postalCode'],
            $this->billingAddress['city'],
            $this->billingAddress['countryCode']
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
    private function _addAddressElement(
        $parentElement,
        $firstName,
        $lastName,
        $street,
        $postalCode,
        $city,
        $countryCode
    ) {
        $address = $parentElement->addChild('address');

        $firstNameElement = $address->addChild('firstName');
        $this->_addCDATA($firstNameElement, $firstName);

        $lastNameElement = $address->addChild('lastName');
        $this->_addCDATA($lastNameElement, $lastName);

        $streetElement = $address->addChild('street');
        $this->_addCDATA($streetElement, $street);

        $postalCodeElement = $address->addChild('postalCode');
        //Zip code mandatory for worldpay, if not provided by customer we will pass manually
        $zipCode = '00000';
        //If Zip code provided by customer
        if ($postalCode) {
            $zipCode = $postalCode;
        }
        $this->_addCDATA($postalCodeElement, $zipCode);

        $cityElement = $address->addChild('city');
        $this->_addCDATA($cityElement, $city);

        $countryCodeElement = $address->addChild('countryCode');
        $this->_addCDATA($countryCodeElement, $countryCode);
    }

    /**
     * Add cdata to xml
     *
     * @param SimpleXMLElement $element
     * @param string $content
     */
    private function _addCDATA($element, $content)
    {
        $node = dom_import_simplexml($element);
        $no   = $node->ownerDocument;
        $node->appendChild($no->createCDATASection((string)$content));
    }

    /**
     * Returns the rounded value of num to specified precision
     *
     * @param float $amount
     * @return int
     */
    private function _amountAsInt($amount)
    {
        return round($amount, $this->exponent, PHP_ROUND_HALF_EVEN) * pow(10, $this->exponent);
    }
}
