<?php
/**
 * @copyright 2023 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

/**
 * Build xml for SEPA Order request
 */
class SEPAOrder
{
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
     * @var array
     */
    private $paymentDetails;
    /**
     * @var string
     */
    private $shopperEmail;
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
     * @var string
     */
    private $shopperId;
    /**
     * @var array|string
     */
    private $statementNarrative;
    /**
     * @var array|string
     */
    private $exponent;
    /**
     * @var string $captureDelay
     */
    private $captureDelay;

    /**
     * Build xml for processing Request
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @param string $orderDescription
     * @param string $currencyCode
     * @param float $amount
     * @param string $orderContent
     * @param array $paymentDetails
     * @param string $shopperEmail
     * @param string $acceptHeader
     * @param string $userAgentHeader
     * @param string $shippingAddress
     * @param float $billingAddress
     * @param string $shopperId
     * @param string $statementNarrative
     * @param string $exponent
     * @param string $captureDelay
     */
    public function build(
        $merchantCode,
        $orderCode,
        $orderDescription,
        $currencyCode,
        $amount,
        $orderContent,
        $paymentDetails,
        $shopperEmail,
        $acceptHeader,
        $userAgentHeader,
        $shippingAddress,
        $billingAddress,
        $shopperId,
        $statementNarrative,
        $exponent,
        $captureDelay
    ) {
        
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->orderDescription = $orderDescription;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->orderContent = $orderContent;
        $this->paymentDetails = $paymentDetails;
        $this->shopperEmail = $shopperEmail;
        $this->acceptHeader = $acceptHeader;
        $this->userAgentHeader = $userAgentHeader;
        $this->shippingAddress = $shippingAddress;
        $this->billingAddress = $billingAddress;
        $this->shopperId = $shopperId;
        $this->statementNarrative = $statementNarrative;
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
     * Add order and its child tag to xml
     *
     * @param SimpleXMLElement $submit
     * @return SimpleXMLElement $order
     */
    private function _addOrderElement($submit)
    {
        $order = $submit->addChild('order');
        $order['orderCode'] = $this->orderCode;
        if ($this->captureDelay!="") {
            $order['captureDelay'] = $this->captureDelay;
        }
        $this->_addDescriptionElement($order);
        $this->_addAmountElement($order);
        $this->_addOrderContentElement($order);
        $this->_addPaymentDetailsElement($order);
        $this->_addShopperElement($order);
        $this->_addBillingElement($order);
        $this->_addMandateElement($order);

        return $order;
    }

    /**
     * Add description  tag to xml
     *
     * @param \SimpleXMLElement $order
     */
    private function _addDescriptionElement($order)
    {
        $description = $order->addChild('description');
        $this->_addCDATA($description, $this->orderDescription);
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
        $sepaElement = $paymentDetailsElement->addChild('SEPA_DIRECT_DEBIT-SSL');
        $sepaElement = $sepaElement->addChild('bankAccount-SEPA');
        $sepaElement->addChild('iban', $this->paymentDetails['sepaIban']);
        $sepaElement->addChild('accountHolderName', $this->paymentDetails['sepaAccountHolderName']);
        return $paymentDetailsElement;
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
        return $shopper;
    }

    /**
     * Add billing and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addBillingElement($order)
    {

        $billingAddress = $order->addChild('billingAddress');
        $address = $billingAddress->addChild('address');

        $firstNameElement = $address->addChild('firstName');
        $this->_addCDATA($firstNameElement, $this->billingAddress['firstName']);

        $lastNameElement = $address->addChild('lastName');
        $this->_addCDATA($lastNameElement, $this->billingAddress['lastName']);

        $streetElement = $address->addChild('address1');
        $this->_addCDATA($streetElement, $this->billingAddress['street']);

        $postalCodeElement = $address->addChild('postalCode');
        $postalCode = $this->billingAddress['postalCode'];
        //Zip code mandatory for worldpay, if not provided by customer we will pass manually
        $zipCode = '00000';
        //If Zip code provided by customer
        if ($postalCode) {
            $zipCode = $postalCode;
        }
        $this->_addCDATA($postalCodeElement, $zipCode);

        $cityElement = $address->addChild('city');
        $this->_addCDATA($cityElement, $this->billingAddress['city']);

        if (isset($this->billingAddress['state'])) {
            $stateElement = $address->addChild('state');
            $this->_addCDATA($stateElement, $this->billingAddress['state']);
        }

        $countryCodeElement = $address->addChild('countryCode');
        $this->_addCDATA($countryCodeElement, $this->billingAddress['countryCode']);

        if (isset($this->billingAddress['telephone'])) {
            $telephoneElement = $address->addChild('telephoneNumber');
            $this->_addCDATA($telephoneElement, $this->billingAddress['telephone']);
        }

        return $address;
    }

    /**
     * Add mandate and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    protected function _addMandateElement($order)
    {
        $time = strtotime("now");
        $merchantNumber = $this->paymentDetails['sepaMerchantNumber'];
        $mandateId = "M-".$merchantNumber."-".$time;
        $mandateElement = $order->addChild('mandate');
        $mandateElement->addChild('mandateType', $this->paymentDetails['sepaMandateType']);
        $mandateElement->addChild('mandateId', $mandateId);
        return $mandateElement;
    }

    /**
     * Add cdata to xml
     *
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
