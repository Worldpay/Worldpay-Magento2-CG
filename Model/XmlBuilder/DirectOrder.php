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
    /**
     * @var ALLOW_INTERACTION_TYPE
     */
    public const ALLOW_INTERACTION_TYPE = 'MOTO';
    /**
     * @var DYNAMIC3DS_DO3DS
     */
    public const DYNAMIC3DS_DO3DS = 'do3DS';
    /**
     * @var DYNAMIC3DS_NO3DS
     */
    public const DYNAMIC3DS_NO3DS = 'no3DS';
    /**
     * @var TOKEN_SCOPE
     */
    public const TOKEN_SCOPE = 'shopper';
    public const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    /**
     * [$merchantCode description]
     * @var [type]
     */
    private $merchantCode;
    /**
     * [$orderCode description]
     * @var [type]
     */
    private $orderCode;
    /**
     * [$orderDescription description]
     * @var [type]
     */
    private $orderDescription;
    /**
     * [$currencyCode description]
     * @var [type]
     */
    private $currencyCode;
    /**
     * [$amount description]
     * @var [type]
     */
    private $amount;
    /**
     * [$paymentDetails description]
     * @var [type]
     */
    protected $paymentDetails;
    /**
     * [$cardAddress description]
     * @var [type]
     */
    private $cardAddress;
    /**
     * [$shopperEmail description]
     * @var [type]
     */
    protected $shopperEmail;
    /**
     * [$acceptHeader description]
     * @var [type]
     */
    protected $acceptHeader;
    /**
     * [$userAgentHeader description]
     * @var [type]
     */
    protected $userAgentHeader;
    /**
     * [$shippingAddress description]
     * @var [type]
     */
    private $shippingAddress;
    /**
     * [$billingAddress description]
     * @var [type]
     */
    private $billingAddress;
    /**
     * [$paResponse description]
     * @var null
     */
    protected $paResponse = null;
    /**
     * [$echoData description]
     * @var null
     */
    private $echoData = null;
    /**
     * [$shopperId description]
     * @var [type]
     */
    private $shopperId;
    /**
     * [$saveCardEnabled description]
     * @var [type]
     */
    private $saveCardEnabled;
    /**
     * [$tokenizationEnabled description]
     * @var [type]
     */
    private $tokenizationEnabled;
    /**
     * [$storedCredentialsEnabled description]
     * @var [type]
     */
    private $storedCredentialsEnabled;
    /**
     * [$dfReferenceId description]
     * @var null
     */
    protected $dfReferenceId = null;
    /**
     * [$cusDetails description]
     * @var [type]
     */
    private $cusDetails;
    /**
     * [$exemptionEngine description]
     * @var [type]
     */
    private $exemptionEngine;
    /**
     * [$thirdparty description]
     * @var [type]
     */
    private $thirdparty;
    /**
     * [$shippingfee description]
     * @var [type]
     */
    private $shippingfee;
    /**
     * [$exponent description]
     * @var [type]
     */
    private $exponent;
    /**
     * [$primeRoutingData description]
     * @var [type]
     */
    private $primeRoutingData;

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
     * [build description]
     *
     * @param  [type] $merchantCode             [description]
     * @param  [type] $orderCode                [description]
     * @param  [type] $orderDescription         [description]
     * @param  [type] $currencyCode             [description]
     * @param  [type] $amount                   [description]
     * @param  [type] $paymentDetails           [description]
     * @param  [type] $cardAddress              [description]
     * @param  [type] $shopperEmail             [description]
     * @param  [type] $acceptHeader             [description]
     * @param  [type] $userAgentHeader          [description]
     * @param  [type] $shippingAddress          [description]
     * @param  [type] $billingAddress           [description]
     * @param  [type] $shopperId                [description]
     * @param  [type] $saveCardEnabled          [description]
     * @param  [type] $tokenizationEnabled      [description]
     * @param  [type] $storedCredentialsEnabled [description]
     * @param  [type] $cusDetails               [description]
     * @param  [type] $exemptionEngine          [description]
     * @param  [type] $thirdparty               [description]
     * @param  [type] $shippingfee              [description]
     * @param  [type] $exponent                 [description]
     * @param  [type] $primeRoutingData         [description]
     * @return [type]                           [description]
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
        $primeRoutingData
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

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $submit = $this->_addSubmitElement($xml);
        $this->_addOrderElement($submit);

        return $xml;
    }

    /**
     * [build3DSecure description]
     *
     * @param  [type] $merchantCode   [description]
     * @param  [type] $orderCode      [description]
     * @param  [type] $paymentDetails [description]
     * @param  [type] $paResponse     [description]
     * @param  [type] $echoData       [description]
     * @return [type]                 [description]
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
     * @param stirng $dfReferenceId
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
        $this->_addCDATA($postalCodeElement, $postalCode);

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
     * [_addCDATA description]
     *
     * @param [type] $element [description]
     * @param [type] $content [description]
     */
    protected function _addCDATA($element, $content)
    {
        $node = dom_import_simplexml($element);
        $no   = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($content));
    }
    /**
     * [_amountAsInt description]
     *
     * @param  [type] $amount [description]
     * @return [type]         [description]
     */
    private function _amountAsInt($amount)
    {
        return round($amount, $this->exponent, PHP_ROUND_HALF_EVEN) * pow(10, $this->exponent);
    }
    
    /**
     * [_addStoredCredentials description]
     *
     * @param [type] $paymentDetailsElement [description]
     */
    private function _addStoredCredentials($paymentDetailsElement)
    {
        $storedCredentials  = $paymentDetailsElement->addChild('storedCredentials');
        $storedCredentials['usage'] = "FIRST";
        return $storedCredentials;
    }
    
    /**
     * [_addPaymentDetailsForStoredCredentialsOrder description]
     *
     * @param [type] $paymentDetailsElement [description]
     */
    private function _addPaymentDetailsForStoredCredentialsOrder($paymentDetailsElement)
    {
        $storedCredentials  = $paymentDetailsElement->addChild('storedCredentials');
        $storedCredentials['usage'] = "USED";
        $storedCredentials->addChild('schemeTransactionIdentifier', $this->paymentDetails['transactionIdentifier']);
        return $storedCredentials;
    }
    /**
     * [_addThirdPartyData description]
     *
     * @param [type] $order [description]
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
     * [_addPrimeRoutingElement description]
     *
     * @param [type] $order [description]
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
     * [_addFraudSightData description]
     *
     * @param [type] $order [description]
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
}
