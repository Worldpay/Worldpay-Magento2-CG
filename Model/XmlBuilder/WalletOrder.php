<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

/**
 * Build xml for RedirectOrder request
 */
class WalletOrder
{
    const DYNAMIC3DS_DO3DS = 'do3DS';
    const DYNAMIC3DS_NO3DS = 'no3DS';
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
    private $exponent;
    protected $paResponse = null;
    protected $dfReferenceId = null;
    private $sessionId;
    protected $threeDSecureConfig;
    private $cusDetails;
    private $shopperIpAddress;
    private $paymentDetails;
    private $shippingAddress;
    protected $acceptHeader;
    protected $userAgentHeader;
    
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
        $acceptHeader,
        $userAgentHeader,
        $protocolVersion,
        $signature,
        $signedMessage,
        $shippingAddress,
        $billingAddress,
        $cusDetails,
        $shopperIpAddress,
        $paymentDetails,
        $exponent
    ) {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->orderDescription = $orderDescription;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->paymentType = $paymentType;
        $this->shopperEmail = $shopperEmail;
        $this->acceptHeader = $acceptHeader;
        $this->userAgentHeader = $userAgentHeader;
        $this->protocolVersion = $protocolVersion;
        $this->signature = $signature;
        $this->signedMessage = $signedMessage;
        $this->shippingAddress = $shippingAddress;
        $this->billingAddress = $billingAddress;
        $this->cusDetails = $cusDetails;
        $this->shopperIpAddress = $shopperIpAddress;
        $this->paymentDetails = $paymentDetails;
        $this->exponent = $exponent;
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
     * Add order tag to xml
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
        $order['shopperLanguageCode'] = "en";
        $this->_addDescriptionElement($order);
        $this->_addAmountElement($order);
        $this->_addPaymentDetailsElement($order);
        $this->_addShopperElement($order);
        $this->_addDynamic3DSElement($order);
        $this->_addCustomerRiskData($order);
        $this->_addAdditional3DsElement($order);
        return $order;
    }
    /**
     * Add Customer Risk Data  and its child tag to xml
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
        $transactionRiskData['deliveryEmailAddress'] = $this->shopperEmail;
        $transactionRiskData['reorderingPreviousPurchases'] = $this->cusDetails['order_details']['previous_purchase'];
        $transactionRiskData['preOrderPurchase'] = 'false';
        $transactionRiskData['giftCardCount'] = 0;
        return $riskData;
    }
    /**
     * Add Additional3Ds data and its child tag to xml
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
     * Add PaymentDetails and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addPaymentDetailsElement($order)
    {
        $paymentDetails = $order->addChild('paymentDetails');

        $paymentType = $paymentDetails->addChild($this->paymentType);
        $paymentType->addChild('protocolVersion', $this->protocolVersion);
        $paymentType->addChild('signature', $this->signature);
        $paymentType->addChild('signedMessage', $this->signedMessage);
        //$paymentType->addChild('session', $this->signedMessage);
        $session = $paymentDetails->addChild('session');
        $session['id'] = $this->paymentDetails['sessionId'];
        $session['shopperIPAddress'] = $this->shopperIpAddress;
        if ($this->paResponse) {
            $info3DSecure = $paymentType->addChild('info3DSecure');
            $info3DSecure->addChild('paResponse', $this->paResponse);
        }
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

        $acceptHeader = $browser->addChild('acceptHeader');
        $this->_addCDATA($acceptHeader, $this->acceptHeader);

        $userAgentHeader = $browser->addChild('userAgentHeader');
        $this->_addCDATA($userAgentHeader, $this->userAgentHeader);
        return $shopper;
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
}
