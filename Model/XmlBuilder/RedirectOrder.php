<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

use Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecureConfig;

/**
 * Build xml for RedirectOrder request
 */
class RedirectOrder
{
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
    private $paymentType;
    private $shopperEmail;
    private $statementNarrative;
    private $acceptHeader;
    private $userAgentHeader;
    private $shippingAddress;
    private $billingAddress;
    private $paymentPagesEnabled;
    private $installationId;
    private $hideAddress;
    private $thirdparty;
    private $shippingfee;
    private $exponent;
    private $cusDetails;

    /**
     * @var Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecure
     */
    private $threeDSecureConfig;
    /**
     * @var Sapient\Worldpay\Model\XmlBuilder\Config\TokenConfiguration
     */
    private $tokenRequestConfig;

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
     * @param string $paymentType
     * @param $shopperEmail
     * @param $statementNarrative
     * @param string $acceptHeader
     * @param string $userAgentHeader
     * @param string $shippingAddress
     * @param string $billingAddress
     * @param float $paymentPagesEnabled
     * @param string $installationId
     * @param $hideAddress
     * @param array $paymentDetails
     * @return SimpleXMLElement $xml
     */
    public function build(
        $merchantCode,
        $orderCode,
        $orderDescription,
        $currencyCode,
        $amount,
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
        $paymentDetails,
        $thirdparty,
        $shippingfee,
        $exponent,
        $cusDetails
    ) {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->orderDescription = $orderDescription;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
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
        $this->paymentDetails = $paymentDetails;
        $this->thirdparty = $thirdparty;
        $this->shippingfee = $shippingfee;
        $this->exponent = $exponent;
        $this->cusDetails = $cusDetails;

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

        $this->_addDescriptionElement($order);
        $this->_addAmountElement($order);
        if (isset($this->paymentDetails['paymentType']) && $this->paymentDetails['paymentType'] == "TOKEN-SSL") {
            $this->_addPaymentDetailsElement($order);
        } else {
            $this->_addPaymentMethodMaskElement($order);
        }
        $this->_addShopperElement($order);
        $this->_addShippingElement($order);
        $this->_addBillingElement($order);
        if (!empty($this->thirdparty)) {
            $this->_addThirdPartyData($order);
        }
        $this->_addDynamic3DSElement($order);
        $this->_addCreateTokenElement($order);
        if (!empty($this->statementNarrative)) {
            $this->_addStatementNarrativeElement($order);
        }
        $this->_addFraudSightData($order);
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
        if (!empty($this->thirdparty['statement'])) {
            $this->_addCDATA($description, $this->thirdparty['statement']);
        } else {
            $this->_addCDATA($description, $this->orderDescription);
        }
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
     * @param SimpleXMLElement $order
     */
    private function _addCreateTokenElement($order)
    {
        if (! $this->tokenRequestConfig->istokenizationIsEnabled()) {
            return;
        }

        $createTokenElement = $order->addChild('createToken');
        $createTokenElement['tokenScope'] = self::TOKEN_SCOPE;
        if ($this->paymentDetails['token_type']) {
            $createTokenElement['tokenScope'] = 'merchant';
            $createTokenElement->addChild(
                'tokenEventReference',
                time().'_'.random_int(0, 99999)
            );
        }
        if ($this->tokenRequestConfig->getTokenReason($this->orderCode)) {
            $createTokenElement->addChild(
                'tokenReason',
                $this->tokenRequestConfig->getTokenReason($this->orderCode)
            );
        }
    }

    /**
     * Add paymentMethodMask and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addPaymentMethodMaskElement($order)
    {
        $paymentMethodMask = $order->addChild('paymentMethodMask');

        $include = $paymentMethodMask->addChild('include');
        $include['code'] = $this->paymentType;
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
     * Add shopper and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addShopperElement($order)
    {
        $shopper = $order->addChild('shopper');
        $shopper->addChild('shopperEmailAddress', $this->shopperEmail);
        if (!$this->paymentDetails['token_type']) {
            if ($this->tokenRequestConfig->istokenizationIsEnabled()) {
                $shopper->addChild('authenticatedShopperID', $this->shopperId);
            } elseif (isset($this->paymentDetails['tokenCode'])) {
                $shopper->addChild('authenticatedShopperID', $this->paymentDetails['customerId']);
            }
        }

        $browser = $shopper->addChild('browser');

        $acceptHeader = $browser->addChild('acceptHeader');
        $this->_addCDATA($acceptHeader, $this->acceptHeader);

        $userAgentHeader = $browser->addChild('userAgentHeader');
        $this->_addCDATA($userAgentHeader, $this->userAgentHeader);
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
    private function _addCDATA($element, $content)
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
    
    /**
     * Add paymentDetails and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    protected function _addPaymentDetailsElement($order)
    {
        $paymentDetailsElement = $order->addChild('paymentDetails');
        $this->_addPaymentDetailsForTokenOrder($paymentDetailsElement);
        
        $session = $paymentDetailsElement->addChild('session');
        $session['id'] = $this->paymentDetails['sessionId'];
        $session['shopperIPAddress'] = $this->paymentDetails['shopperIpAddress'];
    }
    
    /**
     * Add encryptedData and its child tag to xml
     *
     * @param SimpleXMLElement $paymentDetailsElement
     */
    protected function _addPaymentDetailsForTokenOrder($paymentDetailsElement)
    {
        if (isset($this->paymentDetails['encryptedData'])) {
            $cseElement = $this->_addCseElement($paymentDetailsElement);
        }
        $tokenNode = $paymentDetailsElement->addChild($this->paymentDetails['paymentType']);
        $tokenNode['tokenScope'] = self::TOKEN_SCOPE;
        if ($this->paymentDetails['token_type']) {
            $tokenNode['tokenScope'] = 'merchant';
        }
        if (isset($this->paymentDetails['ccIntegrationMode']) &&
            $this->paymentDetails['ccIntegrationMode'] == "redirect" && $this->paymentDetails['paymentPagesEnabled']) {
            $tokenNode['captureCvc'] = "true";
        }
        
        $tokenNode->addChild('paymentTokenID', $this->paymentDetails['tokenCode']);
    }
    
    protected function _addThirdPartyData($order)
    {
        $thirdparty = $order->addChild('thirdPartyData');
        if (!empty($this->thirdparty['instalment'])) {
            $thirdparty->addChild('instalments', $this->thirdparty['instalment']);
        }
        if ($this->billingAddress['countryCode']=='BR' && !empty($this->shippingfee['shippingfee'])) {
            $firstinstalment = $thirdparty->addChild('firstInstalment');
            $firstinstalment = $firstinstalment->addChild('amountNoCurrency');
            $firstinstalment['value'] =$this->_amountAsInt($this->shippingfee['shippingfee']);
        }
        if (!empty($this->thirdparty['cpf'])) {
            $thirdparty->addChild('cpf', $this->thirdparty['cpf']);
        }
        return $thirdparty;
    }
    
    private function _addFraudSightData($order)
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
        $this->_addAddressElement(
            $shopperAddress,
            $this->billingAddress['firstName'],
            $this->billingAddress['lastName'],
            $this->billingAddress['street'],
            $this->billingAddress['postalCode'],
            $this->billingAddress['city'],
            $this->billingAddress['countryCode']
        );
    }
}
