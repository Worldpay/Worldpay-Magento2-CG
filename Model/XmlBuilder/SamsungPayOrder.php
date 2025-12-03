<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

use Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecureConfig;

/**
 * Build xml for RedirectOrder request
 */
class SamsungPayOrder
{
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
     * @var mixed
     */
    private $exponent;
    /**
     * @var string $captureDelay
     */
    private $captureDelay;

    /**
     * @var string
     */
    private $shopperEmail;
    /**
     * @var array
     */
    private $data;
    /**
     * @var array
     */
    protected $browserFields;
    /**
     * @var string
     */
    private $shopperIpAddress;

    /**
     * @var string
     */
    private $sessionId;

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
     * @param array $data
     * @param mixed $exponent
     * @param string $captureDelay
     * @param array $browserFields
     * @param string $shopperIpAddress
     * @param string $sessionId
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
        $data,
        $exponent,
        $captureDelay,
        $browserFields,
        $shopperIpAddress,
        $sessionId
    ) {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->orderDescription = $orderDescription;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->orderContent = $orderContent;
        $this->paymentType = $paymentType;
        $this->shopperEmail = $shopperEmail;
        $this->data = $data;
        $this->exponent = $exponent;
        $this->captureDelay = $captureDelay;
        $this->browserFields = $browserFields;
        $this->shopperIpAddress = $shopperIpAddress;
        $this->sessionId = $sessionId;

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
        $order['shopperLanguageCode'] = "en";
        if ($this->captureDelay!="") {
            $order['captureDelay'] = $this->captureDelay;
        }

        $this->_addDescriptionElement($order);
        $this->_addAmountElement($order);
        $this->_addOrderContentElement($order);
        $this->_addPaymentDetailsElement($order);
        $this->_addShopperElement($order);
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
        $this->_addCDATA($description, $this->orderDescription);
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
     * Add PaymentDetails and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addPaymentDetailsElement($order)
    {
        $paymentDetails = $order->addChild('paymentDetails');
        $paymentType = $paymentDetails->addChild($this->paymentType);
        $paymentThreeDS = $paymentType->addChild('ThreeDS');
        $paymentThreeDS->addChild('data', $this->data);
        $paymentThreeDS->addChild('version', 100);

        $session = $paymentDetails->addChild('session');
        $session['id'] = $this->sessionId;
        $session['shopperIPAddress'] = $this->shopperIpAddress;
    }

    /**
     * Add shopper and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addShopperElement($order)
    {
        $shopper = $order->addChild('shopper');

        $shopper->addChild('shopperEmailAddress', $this->shopperEmail);

        $browser = $shopper->addChild('browser');
        $browserFields = $this->browserFields;

        $browser->addChild('browserColourDepth', $browserFields['browser_colorDepth']);
        $browser->addChild('browserScreenHeight', $browserFields['browser_screenWidth']);
        $browser->addChild('browserScreenWidth', $browserFields['browser_screenHeight']);
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
