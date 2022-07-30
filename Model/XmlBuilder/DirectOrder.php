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

    public const ALLOW_INTERACTION_TYPE = 'MOTO';
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
     * @var array
     */
    protected $paymentDetails;
    /**
     * @var array
     */
    private $cardAddress;
    /**
     * @var string
     */
    protected $shopperEmail;
    /**
     * @var string
     */
    protected $acceptHeader;
    /**
     * @var string
     */
    protected $userAgentHeader;
    /**
     * @var array
     */
    private $shippingAddress;
    /**
     * @var array
     */
    private $billingAddress;
    /**
     * @var mixed|null
     */
    protected $paResponse = null;
    /**
     * @var bool|null
     */
    private $echoData = null;
    /**
     * @var string
     */
    private $shopperId;
    /**
     * @var string
     */
    private $saveCardEnabled;
    /**
     * @var string
     */
    private $tokenizationEnabled;
    /**
     * @var string
     */
    private $storedCredentialsEnabled;
    /**
     * @var string|null
     */
    protected $dfReferenceId = null;
    /**
     * @var string
     */
    private $cusDetails;
    /**
     * @var mixed
     */
    private $exemptionEngine;
    /**
     * @var string
     */
    private $thirdparty;
    /**
     * @var mixed
     */
    private $shippingfee;
    /**
     * @var array|string
     */
    private $exponent;
    /**
     * @var mixed
     */
    private $primeRoutingData;
    /**
     * @var array
     */
    private $orderLineItems;

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
    public function __construct(array $args = [])
    {
        $this->threeDSecureConfig = new \Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecure(
            $args['threeDSecureConfig']['isDynamic3D'],
            $args['threeDSecureConfig']['is3DSecure']
        );
        $this->tokenRequestConfig = new \Sapient\Worldpay\Model\XmlBuilder\Config\TokenConfiguration(
            $args['tokenRequestConfig']
        );
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
     * @param string $saveCardEnabled
     * @param string $tokenizationEnabled
     * @param string $storedCredentialsEnabled
     * @param string $cusDetails
     * @param mixed $exemptionEngine
     * @param string $thirdparty
     * @param mixed $shippingfee
     * @param array|string $exponent
     * @param mixed $primeRoutingData
     * @param array $orderLineItems
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
        $saveCardEnabled,
        $tokenizationEnabled,
        $storedCredentialsEnabled,
        $cusDetails,
        $exemptionEngine,
        $thirdparty,
        $shippingfee,
        $exponent,
        $primeRoutingData,
        $orderLineItems
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
        $this->saveCardEnabled = $saveCardEnabled;
        $this->tokenizationEnabled = $tokenizationEnabled;
        $this->storedCredentialsEnabled = $storedCredentialsEnabled;
        $this->cusDetails = $cusDetails;
        $this->exemptionEngine = $exemptionEngine;
        $this->thirdparty = $thirdparty;
        $this->shippingfee = $shippingfee;
        $this->exponent = $exponent;
        $this->primeRoutingData =$primeRoutingData;
        $this->orderLineItems = $orderLineItems;

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
     * @param mixed $paResponse
     * @param bool $echoData
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
     * @param string $dfReferenceId
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
        if (!isset($this->paymentDetails['myaccountSave'])) {
            $this->_addShippingElement($order);
        }
        $this->_addBillingElement($order);
        
        //Level 23 data request body
        if (!empty($this->paymentDetails['isLevel23Enabled']) && $this->paymentDetails['isLevel23Enabled']
            && (($this->paymentDetails['paymentType'] === 'ECMC-SSL'
                || $this->paymentDetails['paymentType'] === 'VISA-SSL')
                || (($this->paymentDetails['cardType'] === 'ECMC-SSL'
                    || $this->paymentDetails['cardType'] === 'VISA-SSL')))
            && ($this->paymentDetails['countryCode'] === 'US' || $this->paymentDetails['countryCode'] === 'CA')) {
            $this->_addBranchSpecificExtension($order);
        }
        
        if (!empty($this->thirdparty)) {
            $this->_addThirdPartyData($order);
        }
        if ($this->echoData) {
            $order->addChild('echoData', $this->echoData);
        }
        $this->_addDynamic3DSElement($order);
        $this->_addCreateTokenElement($order);
        $this->_addDynamicInteractionTypeElement($order);
        if (isset($this->primeRoutingData['advanced_primerouting'])) {
            $this->_addPrimeRoutingElement($order);
        }
        $this->_addCustomerRiskData($order);
        $this->_addAdditional3DsElement($order);
        $this->_addExemptionEngineElement($order);
        $this->_addFraudSightData($order);
        
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
        if (!empty($this->thirdparty['statement'])) {
            $this->_addCDATA($description, $this->thirdparty['statement']);
        } else {
            $this->_addCDATA($description, $this->orderDescription);
        }
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
        $isRecurringOrder =  isset($this->paymentDetails['isRecurringOrder'])? true : false;
        if ($this->paymentDetails['dynamicInteractionType'] == 'MOTO') {
            $threeDSElement = $order->addChild('dynamic3DS');
            $threeDSElement['overrideAdvice'] = self::DYNAMIC3DS_NO3DS;
        }
        if (! $this->threeDSecureConfig->isDynamic3DEnabled()) {
            return;
        }

        $threeDSElement = $order->addChild('dynamic3DS');
        if ($this->threeDSecureConfig->is3DSecureCheckEnabled()
            && $this->paymentDetails['dynamicInteractionType'] !== 'MOTO'
            && !$isRecurringOrder) {
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
        if (!$this->tokenizationEnabled && !$this->storedCredentialsEnabled) {
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
        if (isset($this->paymentDetails['isIAVEnabled'])) {
            $paymentDetailsElement['action'] = "ACCOUNTVERIFICATION";
        }
        if (isset($this->primeRoutingData['primerouting'])) {
            $paymentDetailsElement['action'] = 'SALE';
        }
        if (isset($this->paymentDetails['tokenCode'])) {
            $this->_addPaymentDetailsForTokenOrder($paymentDetailsElement);
            if (isset($this->paymentDetails['transactionIdentifier']) &&
            !empty($this->paymentDetails['transactionIdentifier'])) {
                $this->_addPaymentDetailsForStoredCredentialsOrder($paymentDetailsElement);
            }
        } else {
            $this->_addPaymentDetailsForCreditCardOrder($paymentDetailsElement);
        }
        if ($this->saveCardEnabled && $this->storedCredentialsEnabled) {
            $this->_addStoredCredentials($paymentDetailsElement);
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
        if($postalCode){
		    $zipCode = $postalCode;
        }
        $this->_addCDATA($postalCodeElement, $zipCode);

        $cityElement = $address->addChild('city');
        $this->_addCDATA($cityElement, $city);

        $countryCodeElement = $address->addChild('countryCode');
        $this->_addCDATA($countryCodeElement, $countryCode);
    }
    
    /**
     * Add Customer Risk Data  and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    protected function _addCustomerRiskData($order)
    {
        $riskData = $order->addChild('riskData');
        $accountCreatedDate = strtotime($this->cusDetails['created_at']);
        $accountUpdatedDate = strtotime($this->cusDetails['updated_at']);
        
        $orderCreateDate = strtotime($this->cusDetails['order_details']['created_at']);
        $orderUpdateDate = strtotime($this->cusDetails['order_details']['updated_at']);
        if ($this->shippingAddress) {
            $shippingNameMatchesAccountName = ($this->billingAddress['firstName'] == $this->
            shippingAddress['firstName']) ? 'true' : 'false';
        } else {
            $shippingNameMatchesAccountName = 'false';
        }
        //Authentication risk data
        $authenticationRiskData = $riskData->addChild('authenticationRiskData');
        $authenticationRiskData['authenticationMethod'] = !empty($this->shopperId)? 'localAccount' : 'guestCheckout';
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
        $shopperAccountRiskData['transactionsAttemptedLastDay'] = $this->cusDetails['order_count']['last_day_count'];
        $shopperAccountRiskData['transactionsAttemptedLastYear'] = $this->cusDetails['order_count']['last_year_count'];
        $shopperAccountRiskData['purchasesCompletedLastSixMonths'] = $this->
            cusDetails['order_count']['last_six_months_count'];
        $shopperAccountRiskData['addCardAttemptsLastDay'] = $this->cusDetails['card_count'];
        $shopperAccountRiskData['previousSuspiciousActivity'] = 'false';
        $shopperAccountRiskData['shippingNameMatchesAccountName'] = $shippingNameMatchesAccountName;
        $shopperAccountRiskData['shopperAccountAgeIndicator'] = $this->cusDetails['shopperAccountAgeIndicator'];
        $shopperAccountRiskData['shopperAccountChangeIndicator'] = $this->cusDetails['shopperAccountChangeIndicator'];
        $shopperAccountRiskData['shopperAccountPasswordChangeIndicator'] = $this->
            cusDetails['shopperAccountPasswordChangeIndicator'];
        $shopperAccountRiskData['shopperAccountShippingAddressUsageIndicator'] = $this->
            cusDetails['shopperAccountShippingAddressUsageIndicator'];
        $shopperAccountRiskData['shopperAccountPaymentAccountIndicator'] = $this->
            cusDetails['shopperAccountPaymentAccountIndicator'];
        
        $shopperAccountRiskDataElement = $shopperAccountRiskData->addChild('shopperAccountCreationDate');
        $shopperAccountRiskDataElementChild = $shopperAccountRiskDataElement->addChild('date');
        $shopperAccountRiskDataElementChild['dayOfMonth'] = date("d", $accountCreatedDate);
        $shopperAccountRiskDataElementChild['month'] = date("m", $accountCreatedDate);
        $shopperAccountRiskDataElementChild['year'] = date("Y", $accountCreatedDate);
        
        $shopperAccountRiskDataElement1 = $shopperAccountRiskData->addChild('shopperAccountModificationDate');
        $shopperAccountRiskDataElementChild1 = $shopperAccountRiskDataElement1->addChild('date');
        $shopperAccountRiskDataElementChild1['dayOfMonth'] = date("d", $accountUpdatedDate);
        $shopperAccountRiskDataElementChild1['month'] = date("m", $accountUpdatedDate);
        $shopperAccountRiskDataElementChild1['year'] = date("Y", $accountUpdatedDate);
        
        $shopperAccountPasswordChangeAttribute = $shopperAccountRiskData->addChild('shopperAccountPasswordChangeDate');
        $shopperAccountPasswordChangeElement = $shopperAccountPasswordChangeAttribute->addChild('date');
        $shopperAccountPasswordChangeElement['dayOfMonth'] = date("d", $accountUpdatedDate);
        $shopperAccountPasswordChangeElement['month'] = date("m", $accountUpdatedDate);
        $shopperAccountPasswordChangeElement['year'] = date("Y", $accountUpdatedDate);
        
        $shopperAccountShippingAddressAttribute = $shopperAccountRiskData->
            addChild('shopperAccountShippingAddressFirstUseDate');
        $shopperAccountShippingAddressElement = $shopperAccountShippingAddressAttribute->addChild('date');
        $shopperAccountShippingAddressElement['dayOfMonth'] = date("d", $orderCreateDate);
        $shopperAccountShippingAddressElement['month'] = date("m", $orderCreateDate);
        $shopperAccountShippingAddressElement['year'] = date("Y", $orderCreateDate);
        
        $shopperAccountPaymentAccountFirstUseDateAttribute = $shopperAccountRiskData->
            addChild('shopperAccountPaymentAccountFirstUseDate');
        $shopperAccountPaymentAccountFirstUseDateElement = $shopperAccountPaymentAccountFirstUseDateAttribute->
            addChild('date');
        $shopperAccountPaymentAccountFirstUseDateElement['dayOfMonth'] = date("d", $orderUpdateDate);
        $shopperAccountPaymentAccountFirstUseDateElement['month'] = date("m", $orderUpdateDate);
        $shopperAccountPaymentAccountFirstUseDateElement['year'] = date("Y", $orderUpdateDate);
        
        // Transaction Risk Data
        $transactionRiskData = $riskData->addChild('transactionRiskData');
        $transactionRiskData['shippingMethod'] = 'other';
        /* Set Delivery time if exists */
        //$transactionRiskData['deliveryTimeframe'] = '';
        $transactionRiskData['deliveryEmailAddress'] = $this->shopperEmail;
        $transactionRiskData['reorderingPreviousPurchases'] = $this->cusDetails['order_details']['previous_purchase'];
        $transactionRiskData['preOrderPurchase'] = 'false';
        $transactionRiskData['giftCardCount'] = 0;
        /* Enable if giftcard data available  */
//  $transactionRiskDataGiftCardAmountAttribute = $transactionRiskData->addChild('transactionRiskDataGiftCardAmount');
//  $transactionRiskDataGiftCardAmountElement = $transactionRiskDataGiftCardAmountAttribute->addChild('amount');
//  $transactionRiskDataGiftCardAmountElement['value'] = 0;
//  $transactionRiskDataGiftCardAmountElement['currencyCode'] = $this->currencyCode;
//  $transactionRiskDataGiftCardAmountElement['exponent'] = $this->exponent;
        
        return $riskData;
    }
    
    /**
     * Add  Risk Data  and its child tag to xml
     *
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
     *
     * @param SimpleXMLElement $order
     */
    protected function _addAdditional3DsElement($order)
    {
        $dfReferenceId = isset($this->paymentDetails['dfReferenceId']) ? $this->paymentDetails['dfReferenceId'] : '';
        if ($dfReferenceId) {
            $addisional3DsElement = $order->addChild('additional3DSData');
            $addisional3DsElement['dfReferenceId'] = $this->paymentDetails['dfReferenceId'];
            $addisional3DsElement['challengeWindowSize'] = "390x400";
            $addisional3DsElement['challengePreference'] = "challengeMandated";
            return $addisional3DsElement;
        }
    }
    
    /**
     * Add Exemption Engine data and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    protected function _addExemptionEngineElement($order)
    {
      
        if ($this->exemptionEngine['enabled']) {
            $exemptionEngineElement = $order->addChild('exemption');
            $exemptionEngineElement['placement'] = $this->exemptionEngine['placement'];
            $exemptionEngineElement['type'] = $this->exemptionEngine['type'];
            return $exemptionEngineElement;
        }
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
    
    /**
     * Add stored credentials data and its child tag to xml
     *
     * @param element $paymentDetailsElement
     * @return string
     */
    private function _addStoredCredentials($paymentDetailsElement)
    {
        $storedCredentials  = $paymentDetailsElement->addChild('storedCredentials');
        $storedCredentials['usage'] = "FIRST";
        return $storedCredentials;
    }
    
    /**
     * Add payment details for stored credentials data and its child tag to xml
     *
     * @param element $paymentDetailsElement
     * @return string
     */
    private function _addPaymentDetailsForStoredCredentialsOrder($paymentDetailsElement)
    {
        $isRecurringOrder =  isset($this->paymentDetails['isRecurringOrder'])? true : false;
        $storedCredentials  = $paymentDetailsElement->addChild('storedCredentials');
        $storedCredentials['usage'] = "USED";
        if($isRecurringOrder){
            $storedCredentials['merchantInitiatedReason'] = "RECURRING";
        }
        $storedCredentials->addChild('schemeTransactionIdentifier', $this->paymentDetails['transactionIdentifier']);
        return $storedCredentials;
    }
    
    /**
     * Add third party data and its child tag to xml
     *
     * @param element $order
     * @return string
     */
    private function _addThirdPartyData($order)
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
    
    /**
     * Add prime routing data and its child tag to xml
     *
     * @param element $order
     * @return string
     */
    private function _addPrimeRoutingElement($order)
    {
        $primeRouting = $order->addChild('primeRoutingRequest');
        $routingPreference = $this->primeRoutingData['routing_preference'];
        $primeRouting->addChild('routingPreference', $routingPreference);
        $debitNetworks = $this->primeRoutingData['debit_networks'];
        if (!empty($debitNetworks)) {
            $preferredNetworks = $primeRouting->addChild('preferredNetworks');
            foreach ($debitNetworks as $key => $network) {
                $preferredNetworks->addChild('networkName', $network);
            }
            
        }
        return $primeRouting;
    }
    
    /**
     * Add fraud sight data and its child tag to xml
     *
     * @param element $order
     * @return string
     */
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
    
     /**
      * Add branchSpecificExtension and its child tag to xml
      *
      * @param SimpleXMLElement $order
      */
    private function _addBranchSpecificExtension($order)
    {
        $order_details = $this->cusDetails['order_details'];

        $branchSpecificExtension = $order->addChild('branchSpecificExtension');
        $purchase = $branchSpecificExtension->addChild('purchase');
        
        $customerId = '';
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get(\Magento\Customer\Model\Session::class);
        if ($customerSession->isLoggedIn()) {
            $customerId =  $customerSession->getCustomer()->getId();
        }
           
        if (!empty($customerId)) {
            $purchase->addChild('customerReference', $customerId);
        } else {
            $purchase->addChild('customerReference', 'guest');
        }
        
        $purchase->addChild('cardAcceptorTaxId', $this->paymentDetails['cardAcceptorTaxId']);
        
        $salesTax = $purchase->addChild('salesTax');
 
        $this->_addAmountElementDirect(
            $salesTax,
            $this->currencyCode,
            $this->exponent,
            $this->paymentDetails['salesTax']
        );
                  
        if (isset($this->cusDetails['discount_amount'])) {
            $discountAmount = $purchase->addChild('discountAmount');
            $this->_addAmountElementDirect(
                $discountAmount,
                $this->currencyCode,
                $this->exponent,
                $this->cusDetails['discount_amount']
            );
        }
                 
        if (isset($this->cusDetails['shipping_amount'])) {
            $shippingAmount = $purchase->addChild('shippingAmount');
            $this->_addAmountElementDirect(
                $shippingAmount,
                $this->currencyCode,
                $this->exponent,
                $this->cusDetails['shipping_amount']
            );
        }
        
        if (isset($this->paymentDetails['dutyAmount'])) {
            $dutyAmount = $purchase->addChild('dutyAmount');
            $this->_addAmountElementDirect(
                $dutyAmount,
                $this->currencyCode,
                $this->exponent,
                $this->paymentDetails['dutyAmount']
            );
        }
        
        //$purchase->addChild('shipFromPostalCode', '');
        $purchase->addChild('destinationPostalCode', $this->shippingAddress['postalCode']);
        $purchase->addChild('destinationCountryCode', $this->shippingAddress['countryCode']);
        
        $orderDate = $purchase->addChild('orderDate');
        $dateElement = $orderDate->addChild('date');
        $today = new \DateTime();
        $dateElement['dayOfMonth'] = $today->format('d');
        $dateElement['month'] = $today->format('m');
        $dateElement['year'] = $today->format('Y');
        
        $purchase->addChild('taxExempt', $this->paymentDetails['salesTax'] > 0 ? 'true' : 'false');
        
        $this->_addL23OrderLineItemElement($order, $purchase);
    }
    
    /**
     * Add all order line item element values to xml
     *
     * @param Order $order
     * @param mixed $purchase
     */
    private function _addL23OrderLineItemElement($order, $purchase)
    {
        
        $orderLineItems = $this->orderLineItems;
        
        foreach ($orderLineItems['lineItem'] as $lineitem) {
            $this->_addLineItemElement(
                $purchase,
                $lineitem['description'],
                $lineitem['productCode'],
                $lineitem['commodityCode'],
                $lineitem['quantity'],
                $lineitem['unitCost'],
                $lineitem['unitOfMeasure'],
                $lineitem['itemTotal'],
                $lineitem['itemTotalWithTax'],
                $lineitem['itemDiscountAmount'],
                $lineitem['taxAmount']
            );
        }
    }
    
    /**
     * Add order line item element values to xml
     *
     * @param SimpleXMLElement $parentElement
     * @param string $description
     * @param string $productCode
     * @param string $commodityCode
     * @param string $quantity
     * @param float $unitCost
     * @param string $unitOfMeasure
     * @param float $itemTotal
     * @param float $itemTotalWithTax
     * @param float $itemDiscountAmount
     * @param float $taxAmount
     */
    private function _addLineItemElement(
        $parentElement,
        $description,
        $productCode,
        $commodityCode,
        $quantity,
        $unitCost,
        $unitOfMeasure,
        $itemTotal,
        $itemTotalWithTax,
        $itemDiscountAmount,
        $taxAmount
    ) {
        $item = $parentElement->addChild('item');
        
        $descriptionElement = $item->addChild('description');
        $this->_addCDATA($descriptionElement, $description);
        
        $productCodeElement = $item->addChild('productCode');
        $this->_addCDATA($productCodeElement, $productCode);
        
        if ($commodityCode) {
            $commodityCodeElement = $item->addChild('commodityCode');
            $this->_addCDATA($commodityCodeElement, $commodityCode);
        }

        $quantityElement = $item->addChild('quantity');
        $this->_addCDATA($quantityElement, $quantity);

        $unitCostElement = $item->addChild('unitCost');
        $this->_addAmountElementDirect($unitCostElement, $this->currencyCode, $this->exponent, $unitCost);
        
        if ($unitOfMeasure) {
            $unitOfMeasureElement = $item->addChild('unitOfMeasure');
            $this->_addCDATA($unitOfMeasureElement, $unitOfMeasure);
        }
        
        $itemTotalElement = $item->addChild('itemTotal');
        $this->_addAmountElementDirect($itemTotalElement, $this->currencyCode, $this->exponent, $itemTotal);

        $itemTotalWithTaxElement = $item->addChild('itemTotalWithTax');
        $this->_addAmountElementDirect(
            $itemTotalWithTaxElement,
            $this->currencyCode,
            $this->exponent,
            $itemTotalWithTax
        );

        $itemDiscountAmountElement = $item->addChild('itemDiscountAmount');
        $this->_addAmountElementDirect(
            $itemDiscountAmountElement,
            $this->currencyCode,
            $this->exponent,
            $itemDiscountAmount
        );

        $taxAmountElement = $item->addChild('taxAmount');
        $this->_addAmountElementDirect($taxAmountElement, $this->currencyCode, $this->exponent, $taxAmount);
    }
    /**
     * Add amount and its child tag to xml
     *
     * @param SimpleXMLElement $orderXml
     * @param string $currencyCode
     * @param bool $exponent
     * @param float $amount
     */
    private function _addAmountElementDirect($orderXml, $currencyCode, $exponent, $amount)
    {
        $amountElement = $orderXml->addChild('amount');
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = $this->exponent;
        $amountElement['value'] = $this->_amountAsInt($amount);
    }
}
