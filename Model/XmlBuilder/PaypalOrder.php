<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

use Sapient\Worldpay\Helper\Data;
use Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecureConfig;
use \Sapient\Worldpay\Logger\WorldpayLogger;

/**
 * Build xml for Direct Order request
 */
class PaypalOrder
{

    public const ALLOW_INTERACTION_TYPE = 'MOTO';
    public const DYNAMIC3DS_NO3DS = 'no3DS';
    public const TOKEN_SCOPE = 'shopper';
    public const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    public $_urlBuilder;

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
     * @var string
     */
    private $cusDetails;
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
     * @var string $captureDelay
     */
    protected $captureDelay;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var array $browserFields
     */
    protected $browserFields;

    /**
     * @var string $telephoneNumber
     */
    protected $telephoneNumber;

    private $dataHelper;

    /**
     * Constructor
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $args
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Customer\Model\Session $customerSession,
        Data $dataHelper,
        array $args = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->customerSession = $customerSession;
        $this->dataHelper = $dataHelper;
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
     * @param mixed $shippingfee
     * @param array|string $exponent
     * @param mixed $primeRoutingData
     * @param array $orderLineItems
     * @param string $captureDelay
     * @param array $browserFields
     * @param string $telephoneNumber
     * @return SimpleXMLElement $xml
     */
    public function build(
        $merchantCode,
        $orderCode,
        $orderDescription,
        $currencyCode,
        $amount,
        $orderContent,
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
        $shippingfee,
        $exponent,
        $primeRoutingData,
        $orderLineItems,
        $captureDelay,
        $browserFields,
        $telephoneNumber
    ) {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->orderDescription = $orderDescription;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->orderContent = $orderContent;
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
        $this->shippingfee = $shippingfee;
        $this->exponent = $exponent;
        $this->primeRoutingData =$primeRoutingData;
        $this->orderLineItems = $orderLineItems;
        $this->captureDelay = $captureDelay;
        $this->telephoneNumber = $telephoneNumber;
        $this->browserFields = $browserFields;
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

        if ($this->echoData) {
            $order->addChild('echoData', $this->echoData);
        }

        if (isset($this->primeRoutingData['advanced_primerouting'])) {
            $this->_addPrimeRoutingElement($order);
        }
        $this->_addCustomerRiskData($order);
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

        $this->addPaypalPaymentDetails($paymentDetailsElement);

        $isSendIpAddress = true;
        if (isset($this->paymentDetails['sendShopperIpAddress'])) {
            $isSendIpAddress = $this->paymentDetails['sendShopperIpAddress'];
        }
        if ($isSendIpAddress) {
            $session = $paymentDetailsElement->addChild('session');
            $session['id'] = $this->paymentDetails['sessionId'];
            $session['shopperIPAddress'] = $this->paymentDetails['shopperIpAddress'];
        }
    }

    /**
     * Add paymentType and its child tag to xml
     *
     * @param SimpleXMLElement $paymentDetailsElement
     * @return SimpleXMLElement $paymentTypeElement
     */
    protected function addPaypalPaymentDetails($paymentDetailsElement)
    {
        $paymentTypeElement = $paymentDetailsElement->addChild('PAYPAL-SSL');
        $paymentTypeElement['intent'] = 'authorise';
        $paymentTypeElement->addChild('successURL', $this->_urlBuilder->getUrl('worldpay/redirectresult/success'));
        $paymentTypeElement->addChild('cancelURL', $this->_urlBuilder->getUrl('worldpay/redirectresult/cancel'));
        $paymentTypeElement->addChild('pendingURL', $this->_urlBuilder->getUrl('worldpay/redirectresult/pending'));
        $paymentTypeElement->addChild('failureURL', $this->_urlBuilder->getUrl('worldpay/redirectresult/failure'));

        return $paymentTypeElement;
    }


    /**
     * Format Expiry Month
     *
     * @param string $inputMonth
     * @return string
     */
    protected function formatExpiryMonth($inputMonth)
    {
        $month = (int) $inputMonth;
        $formattedMonth = sprintf('%02d', $month);
        return $formattedMonth;
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

        $browser = $shopper->addChild('browser');

        $acceptHeader = $browser->addChild('acceptHeader');
        $this->_addCDATA($acceptHeader, $this->acceptHeader);

        $userAgentHeader = $browser->addChild('userAgentHeader');
        $this->_addCDATA($userAgentHeader, $this->userAgentHeader);

        $browserFields = $this->browserFields;
        $browser->addChild('browserColourDepth', $browserFields['browser_colorDepth']);
        $browser->addChild('browserScreenHeight', $browserFields['browser_screenHeight']);
        $browser->addChild('browserScreenWidth', $browserFields['browser_screenWidth']);

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
            $this->shippingAddress['countryCode'],
            false,
            $this->telephoneNumber
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
            $this->billingAddress['countryCode'],
            false,
            $this->telephoneNumber
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
     * @param string $cardflag
     * @param string $telephoneNumber
     */
    private function _addAddressElement(
        $parentElement,
        $firstName,
        $lastName,
        $street,
        $postalCode,
        $city,
        $countryCode,
        $cardflag,
        $telephoneNumber = ""
    ) {
        $address = $parentElement->addChild('address');

        $firstNameElement = $address->addChild('firstName');
        $this->_addCDATA($firstNameElement, $firstName);

        $lastNameElement = $address->addChild('lastName');
        $this->_addCDATA($lastNameElement, $lastName);

        if ($cardflag) {
            $address1Element = $address->addChild('address1');
            $this->_addCDATA($address1Element, substr($street, 0, 50));
        } else {
            $streetElement = $address->addChild('street');
            $this->_addCDATA($streetElement, $street);
        }

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
        if (!empty($telephoneNumber)) {
            $address->addChild('telephoneNumber', $telephoneNumber);
        }
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

        return $riskData;
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
            $this->billingAddress['countryCode'],
            false,
            $this->telephoneNumber
        );
    }

    /**
     * Add branchSpecificExtension and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addBranchSpecificExtension($order)
    {
        $branchSpecificExtension = $order->addChild('branchSpecificExtension');
        $purchase = $branchSpecificExtension->addChild('purchase');

        $customerId = '';
        if ($this->customerSession->isLoggedIn()) {
            $customerId =  $this->customerSession->getCustomer()->getId();
        }

        if (!empty($customerId)) {
            $purchase->addChild('customerReference', $customerId);
        } else {
            $purchase->addChild('customerReference', 'guest');
        }

        $purchase->addChild('cardAcceptorTaxId', $this->paymentDetails['cardAcceptorTaxId']);

        $salesTax = $purchase->addChild('salesTax');

        $this->_addAmountElementDirect($salesTax, $this->paymentDetails['salesTax']);

        if (isset($this->cusDetails['discount_amount'])) {
            $discountAmount = $purchase->addChild('discountAmount');
            $this->_addAmountElementDirect($discountAmount, $this->cusDetails['discount_amount']);
        }

        if (isset($this->cusDetails['shipping_amount'])) {
            $shippingAmount = $purchase->addChild('shippingAmount');
            $this->_addAmountElementDirect($shippingAmount, $this->cusDetails['shipping_amount']);
        }

        if (isset($this->paymentDetails['dutyAmount'])) {
            $dutyAmount = $purchase->addChild('dutyAmount');
            $this->_addAmountElementDirect($dutyAmount, $this->paymentDetails['dutyAmount']);
        }

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
        $this->_addAmountElementDirect($unitCostElement, $unitCost);

        if ($unitOfMeasure) {
            $unitOfMeasureElement = $item->addChild('unitOfMeasure');
            $this->_addCDATA($unitOfMeasureElement, $unitOfMeasure);
        }

        $itemTotalElement = $item->addChild('itemTotal');
        $this->_addAmountElementDirect($itemTotalElement, $itemTotal);

        $itemTotalWithTaxElement = $item->addChild('itemTotalWithTax');
        $this->_addAmountElementDirect($itemTotalWithTaxElement, $itemTotalWithTax);

        $itemDiscountAmountElement = $item->addChild('itemDiscountAmount');
        $this->_addAmountElementDirect($itemDiscountAmountElement, $itemDiscountAmount);

        $taxAmountElement = $item->addChild('taxAmount');
        $this->_addAmountElementDirect($taxAmountElement, $taxAmount);
    }
    /**
     * Add amount and its child tag to xml
     *
     * @param SimpleXMLElement $orderXml
     * @param string $currencyCode
     * @param bool $exponent
     * @param float $amount
     */
    private function _addAmountElementDirect($orderXml, $amount)
    {
        $amountElement = $orderXml->addChild('amount');
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = $this->exponent;
        $amountElement['value'] = $this->_amountAsInt($amount);
    }
}
