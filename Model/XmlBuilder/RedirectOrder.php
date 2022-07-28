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
     * @var string
     */
    private $thirdparty;
    /**
     * @var mixed
     */
    private $shippingfee;
    /**
     * @var mixed
     */
    private $exponent;
    /**
     * @var string
     */
    private $cusDetails;
    /**
     * @var array
     */
    private $orderLineItems;

    /**
     * @var Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecure
     */
    private $threeDSecureConfig;
    /**
     * @var Sapient\Worldpay\Model\XmlBuilder\Config\TokenConfiguration
     */
    private $tokenRequestConfig;

    /**
     * @var array
     */
    private $saveCardEnabled;

     /**
     * @var array
     */
    private $storedCredentialsEnabled;

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
     * @param string $shopperEmail
     * @param string $statementNarrative
     * @param string $acceptHeader
     * @param string $userAgentHeader
     * @param string $shippingAddress
     * @param string $billingAddress
     * @param float $paymentPagesEnabled
     * @param string $installationId
     * @param string $hideAddress
     * @param array $paymentDetails
     * @param string $thirdparty
     * @param mixed $shippingfee
     * @param mixed $exponent
     * @param string $cusDetails
     * @param array $orderLineItems
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
        $cusDetails,
        $orderLineItems,
        $savemyCard = null,
        $storedCredentialsEnabled = null
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
        $this->orderLineItems = $orderLineItems;

        $this->saveCardEnabled = $savemyCard;
        $this->storedCredentialsEnabled = $storedCredentialsEnabled;

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
        $amountElement = $order->addChild('amount');
        $this->_addAmountElement($amountElement, $this->currencyCode, $this->exponent, $this->amount);
        if (isset($this->paymentDetails['paymentType']) && $this->paymentDetails['paymentType'] == "TOKEN-SSL") {
            $this->_addPaymentDetailsElement($order);
        } else {
            $this->_addPaymentMethodMaskElement($order);
        }
        $this->_addShopperElement($order);
        $this->_addShippingElement($order);
        $this->_addBillingElement($order);
        
        //Level 23 data request body
        if (!empty($this->paymentDetails['isLevel23Enabled']) && $this->paymentDetails['isLevel23Enabled']
            && ($this->paymentDetails['cardType'] === 'ECMC-SSL' || $this->paymentDetails['cardType'] === 'VISA-SSL')
           && ($this->paymentDetails['countryCode'] === 'US' || $this->paymentDetails['countryCode'] === 'CA')) {
            $this->_addBranchSpecificExtension($order);
        }
        
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
     * @param SimpleXMLElement $amountElement
     * @param string $currencyCode
     * @param mixed $exponent
     * @param float $amount
     */
    private function _addAmountElement($amountElement, $currencyCode, $exponent, $amount)
    {
        $amountElement['currencyCode'] = $currencyCode;
        $amountElement['exponent'] = $exponent;
        $amountElement['value'] = $this->_amountAsInt($amount);
    }

    /**
     * Add dynamicInteractionType and its attribute tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addDynamic3DSElement($order)
    {
        if (isset($this->paymentDetails['PaymentMethod'])
            && $this->paymentDetails['PaymentMethod'] == 'worldpay_moto') {
            $threeDSElement = $order->addChild('dynamic3DS');
            $threeDSElement['overrideAdvice'] = self::DYNAMIC3DS_NO3DS;
        }
        
        if ($this->threeDSecureConfig->isDynamic3DEnabled() === false) {
            return;
        }

        $threeDSElement = $order->addChild('dynamic3DS');
        if ($this->threeDSecureConfig->is3DSecureCheckEnabled()
            && (isset($this->paymentDetails['PaymentMethod'])
                && $this->paymentDetails['PaymentMethod'] !== 'worldpay_moto')) {
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
        if ($this->saveCardEnabled && $this->storedCredentialsEnabled) {
            $this->_addStoredCredentials($paymentMethodMask);
        }
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
     * Add cdata to xml
     *
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
     * Add paymentDetails and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    protected function _addPaymentDetailsElement($order)
    {
        $paymentDetailsElement = $order->addChild('paymentDetails');
        $this->_addPaymentDetailsForTokenOrder($paymentDetailsElement);        
        $this->_addPaymentDetailsForStoredCredentialsOrder($paymentDetailsElement);
        $session = $paymentDetailsElement->addChild('session');
        $session['id'] = $this->paymentDetails['sessionId'];
        $session['shopperIPAddress'] = $this->paymentDetails['shopperIpAddress'];
    }
     /**
     * Add stored credentials data and its child tag to xml
     *
     * @param element $paymentDetailsElement
     * @return string
     */
    protected function _addStoredCredentials($paymentDetailsElement)
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
    protected function _addPaymentDetailsForStoredCredentialsOrder($paymentDetailsElement)
    {
        $storedCredentials  = $paymentDetailsElement->addChild('storedCredentials');
        $storedCredentials['usage'] = "USED";
        return $storedCredentials;
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
            $this->paymentDetails['ccIntegrationMode'] == "redirect") {
            $tokenNode['captureCvc'] = "true";
        }
        
        $tokenNode->addChild('paymentTokenID', $this->paymentDetails['tokenCode']);
    }
    
    /**
     * Add third party data and its child tag to xml
     *
     * @param element $order
     * @return string
     */
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
        if (!empty($order_details['customer_id'])) {
            $purchase->addChild('customerReference', $order_details['customer_id']);
        } else {
            $purchase->addChild('customerReference', 'guest');
        }
        $purchase->addChild('cardAcceptorTaxId', $this->paymentDetails['cardAcceptorTaxId']);
        
        $salesTax = $purchase->addChild('salesTax');
        $salesTaxElement = $salesTax->addChild('amount');
        $this->_addAmountElement(
            $salesTaxElement,
            $this->currencyCode,
            $this->exponent,
            $this->paymentDetails['salesTax']
        );
        
        if (isset($this->cusDetails['discount_amount'])) {
            $discountAmount = $purchase->addChild('discountAmount');
            $discountAmountElement = $discountAmount->addChild('amount');
            $this->_addAmountElement(
                $discountAmountElement,
                $this->currencyCode,
                $this->exponent,
                $this->cusDetails['discount_amount']
            );
        }
        if (isset($this->cusDetails['shipping_amount'])) {
            $shippingAmount = $purchase->addChild('shippingAmount');
            $shippingAmountElement = $shippingAmount->addChild('amount');
            $this->_addAmountElement(
                $shippingAmountElement,
                $this->currencyCode,
                $this->exponent,
                $this->cusDetails['shipping_amount']
            );
        }
        
        if (isset($this->paymentDetails['dutyAmount'])) {
            $dutyAmount = $purchase->addChild('dutyAmount');
            $dutyAmountElement = $dutyAmount->addChild('amount');
            $this->_addAmountElement(
                $dutyAmountElement,
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
        
        $purchase->addChild('taxExempt', $this->paymentDetails['salesTax'] > 0 ? 'false' : 'true');
        
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
        $unitCostAmount = $unitCostElement->addChild('amount');
        $this->_addAmountElement($unitCostAmount, $this->currencyCode, $this->exponent, $unitCost);
        
        if ($unitOfMeasure) {
            $unitOfMeasureElement = $item->addChild('unitOfMeasure');
            $this->_addCDATA($unitOfMeasureElement, $unitOfMeasure);
        }
        
        $itemTotalElement = $item->addChild('itemTotal');
        $itemTotalElementAmount = $itemTotalElement->addChild('amount');
        $this->_addAmountElement($itemTotalElementAmount, $this->currencyCode, $this->exponent, $itemTotal);

        $itemTotalWithTaxElement = $item->addChild('itemTotalWithTax');
        $itemTotalWithTaxElementAmount = $itemTotalWithTaxElement->addChild('amount');
        $this->_addAmountElement(
            $itemTotalWithTaxElementAmount,
            $this->currencyCode,
            $this->exponent,
            $itemTotalWithTax
        );

        $itemDiscountAmountElement = $item->addChild('itemDiscountAmount');
        $itemDiscountElementAmount = $itemDiscountAmountElement->addChild('amount');
        $this->_addAmountElement($itemDiscountElementAmount, $this->currencyCode, $this->exponent, $itemDiscountAmount);

        $taxAmountElement = $item->addChild('taxAmount');
        $taxAmountElementAmount = $taxAmountElement->addChild('amount');
        $this->_addAmountElement($taxAmountElementAmount, $this->currencyCode, $this->exponent, $taxAmount);
    }
}
