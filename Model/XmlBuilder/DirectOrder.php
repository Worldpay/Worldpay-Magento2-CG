<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

use Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecureConfig;
use \Sapient\Worldpay\Logger\WorldpayLogger;

/**
 * Build xml for Direct Order request
 */
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
    protected $dfReferenceId = null;
    private $cusDetails;

    /**
     * @var Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecure
     */
    protected $threeDSecureConfig;

    /**
     * @var Sapient\Worldpay\Model\XmlBuilder\Config\TokenConfiguration
     */
    protected $tokenRequestConfig;

    /**
     * Constructor
     *
     * @param array $args
     */
    public function __construct(array $args = array())
    {
        $this->threeDSecureConfig = new \Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecure($args['threeDSecureConfig']['isDynamic3D'], $args['threeDSecureConfig']['is3DSecure']);
        $this->tokenRequestConfig = new \Sapient\Worldpay\Model\XmlBuilder\Config\TokenConfiguration($args['tokenRequestConfig']);
    }

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
     * @param string $cusDetails
     * @return SimpleXMLElement $xml
     */
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
        $shopperId,
        $cusDetails
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
        $this->cusDetails = $cusDetails;

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $submit = $this->_addSubmitElement($xml);
        $this->_addOrderElement($submit);

        return $xml;
    }

    /**
     * Build xml for 3dsecure processing Request
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @param array $paymentDetails
     * @param $paResponse,
     * @param $echoData
     * @return SimpleXMLElement $xml
     */
    public function build3DSecure(
        $merchantCode,
        $orderCode,
        $paymentDetails,
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
    
    /**
     * Build xml for 3ds2 processing Request
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @param array $paymentDetails
     * @param $dfReferenceId
     * @return SimpleXMLElement $xml
     */
    public function build3Ds2Secure(
        $merchantCode,
        $orderCode,
        $paymentDetails,
        $dfReferenceId
    ) {
        $this->merchantCode = $merchantCode;
        $this->dfReferenceId = $dfReferenceId;
        $this->orderCode = $orderCode;
        $this->paymentDetails = $paymentDetails;
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
        if ($this->paResponse) {
            $info3DSecure = $order->addChild('info3DSecure');
            $info3DSecure->addChild('paResponse', $this->paResponse);
            $session = $order->addChild('session');
            $session['id'] = $this->paymentDetails['sessionId'];
            return $order;
        }
        if ($this->dfReferenceId) {
            $info3DSecure = $order->addChild('info3DSecure');
            $info3DSecure->addChild('completedAuthentication');
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
        
        
        $cusAndRiskData = $this->cusDetails;
        if($this->shopperId && $cusAndRiskData['is_risk_data_enabled']){
        $this->_addCustomerRiskData($order);
        }elseif($cusAndRiskData['is_risk_data_enabled']) {
        $this->_addRiskData($order);
        }
        
        $this->_addAdditional3DsElement($order);
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
        $amountElement['exponent'] = self::EXPONENT;
        $amountElement['value'] = $this->_amountAsInt($this->amount);
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
     * Add dynamicInteractionType and its attribute tag to xml
     *
     * @param SimpleXMLElement $order
     */
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
        $tokenNode->addChild('paymentTokenID', $this->paymentDetails['tokenCode']);

        if (isset($this->paymentDetails['cvc'])) {
            $tokenNode
                ->addChild('paymentInstrument')
                ->addChild('cardDetails')
                ->addChild('cvc', $this->paymentDetails['cvc']);
        }
    }

    /**
     * Add paymentDetailsElement and its child tag to xml
     *
     * @param SimpleXMLElement $paymentDetailsElement
     */
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

    /**
     * Add paymentDetails and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
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
     * Add shopper and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    protected function _addShopperElement($order)
    {
        $shopper = $order->addChild(self::TOKEN_SCOPE);

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
    
    
    
    /**
     * Add Customer Risk Data  and its child tag to xml
     * @param SimpleXMLElement $order 
     */
    protected function _addCustomerRiskData($order)
    {
        $riskData = $order->addChild('riskData');
        
        
        //Authentication risk data
        $authenticationRiskData = $riskData->addChild('authenticationRiskData');
        $authenticationRiskData['authenticationMethod'] = 'localAccount';
        $authenticationTimestampElement = $authenticationRiskData->addChild('authenticationTimestamp');
        $dateElement = $authenticationTimestampElement->addChild('date');
        $dateElement['second'] = date("s");
        $dateElement['minute'] = date("i");
        $dateElement['hour'] = date("H");
        $dateElement['dayOfMonth'] = date("d");
        $dateElement['month'] = date("m");
        $dateElement['year'] = date("Y");
        
        
        //shoppper account risk data
        $shopperAccountRiskData = $riskData->addChild('shopperAccountRiskData');
        $shopperAccountRiskDataElement = $shopperAccountRiskData->addChild('shopperAccountCreationDate');
        $shopperAccountRiskDataElementChild = $shopperAccountRiskDataElement->addChild('date');
        
        
        $accountCreatedDate = strtotime($this->cusDetails['created_at']);
        
        $shopperAccountRiskDataElementChild['dayOfMonth'] = date("d", $accountCreatedDate);
        $shopperAccountRiskDataElementChild['month'] = date("m", $accountCreatedDate);
        $shopperAccountRiskDataElementChild['year'] = date("Y", $accountCreatedDate);
        
        
        $shopperAccountRiskDataElement1 = $shopperAccountRiskData->addChild('shopperAccountModificationDate');
        $shopperAccountRiskDataElementChild1 = $shopperAccountRiskDataElement1->addChild('date');
        
        
        $accountUpdatedDate = strtotime($this->cusDetails['updated_at']);
        
        $shopperAccountRiskDataElementChild1['dayOfMonth'] = date("d", $accountUpdatedDate);
        $shopperAccountRiskDataElementChild1['month'] = date("m", $accountUpdatedDate);
        $shopperAccountRiskDataElementChild1['year'] = date("Y", $accountUpdatedDate);
        
        return $riskData;
    }
    
    
    
    
    /**
     * Add  Risk Data  and its child tag to xml
     * @param SimpleXMLElement $order 
     */
    protected function _addRiskData($order)
    {
        $riskData = $order->addChild('riskData');
        
        
        //Authentication risk data
        $authenticationRiskData = $riskData->addChild('authenticationRiskData');
        $authenticationRiskData['authenticationMethod'] = 'localAccount';
        $authenticationTimestampElement = $authenticationRiskData->addChild('authenticationTimestamp');
        $dateElement = $authenticationTimestampElement->addChild('date');
        $dateElement['second'] = date("s");
        $dateElement['minute'] = date("i");
        $dateElement['hour'] = date("H");
        $dateElement['dayOfMonth'] = date("d");
        $dateElement['month'] = date("m");
        $dateElement['year'] = date("Y");
        
        return $riskData;
    }
    
    /**
     * Add Additional3Ds data and its child tag to xml
     * @param SimpleXMLElement $order 
     */
    protected function _addAdditional3DsElement($order)
    {
        $dfReferenceId = isset($this->paymentDetails['dfReferenceId']) ? $this->paymentDetails['dfReferenceId'] : '';
        if($dfReferenceId){
            $addisional3DsElement = $order->addChild('additional3DSData');
            $addisional3DsElement['dfReferenceId'] = $this->paymentDetails['dfReferenceId'];
            $addisional3DsElement['challengeWindowSize'] = "390x400";
            $addisional3DsElement['challengePreference'] = "challengeMandated";
            return $addisional3DsElement;
        }
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
        return round($amount, self::EXPONENT, PHP_ROUND_HALF_EVEN) * pow(10, self::EXPONENT);
    }
}
