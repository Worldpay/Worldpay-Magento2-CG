<?php
namespace Sapient\Worldpay\Model\XmlBuilder;

use Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecureConfig;

class RedirectKlarnaOrder
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
     * @var mixed
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
     * @var string
     */
    private $sessionData;
    /**
     * Order Datas
     *
     * @var array|string
     */
    private $orderContent;
    /**
     * @var string $captureDelay
     */
    private $captureDelay;
    /**
     * @var array
     */
    private $orderlineitems;

    /**
     * RedirectKlarnaOrder constructor
     */
    public function __construct()
    {
         $this->threeDSecureConfig = new \Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecure();

        $this->tokenRequestConfig = false;
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
     * @param array $orderlineitems
     * @param array|string $exponent
     * @param string $sessionData
     * @param array|string $orderContent
     * @param string $captureDelay
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
        $orderlineitems,
        $exponent,
        $sessionData,
        $orderContent,
        $captureDelay
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
        $this->orderlineitems = $orderlineitems;
        $this->exponent = $exponent;
        $this->sessionData = $sessionData;
        $this->orderContent = $orderContent;
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
        $this->_addPaymentMethodMaskElement($order);
        $this->_addShopperElement($order);
        $this->_addShippingElement($order);
        $this->_addBillingElement($order);
        if (!empty($this->statementNarrative)) {
            $this->_addStatementNarrativeElement($order);
        }
        $this->_addOrderLineItemElement($order);
        $this->_addDynamic3DSElement($order);
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
        //$amountElement['value'] = $this->_amountAsInt($this->_roundOfTotal($order));
    }
    
    /**
     * Add orderContent and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addOrderContentElement($order)
    {
        $orderContent = $order->addChild('orderContent');
        $this->_addCDATA($orderContent, $this->orderDescription);
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
     * Add paymentMethodMask and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addPaymentMethodMaskElement($order)
    {
        $paymentMethodMask = $order->addChild('paymentDetails');
        $urls = $this->orderlineitems['urls'];
        $locale = $this->orderlineitems['locale_code'];
        $include = $paymentMethodMask->addChild($this->paymentType);
        $include['shopperCountryCode'] = $this->billingAddress['countryCode'];
        $include['locale'] = $locale;
        
        $successUrl = $include->addChild('successURL', $urls['successURL']);
        $cancelURL = $include->addChild('cancelURL', $urls['cancelURL']);
        $pendingURL = $include->addChild('pendingURL', $urls['pendingURL']);
        $failureURL = $include->addChild('failureURL', $urls['failureURL']);
        if (!empty($this->paymentType) && $this->paymentType === "KLARNA_PAYLATER-SSL") {
            $customerToken = $include->addChild('customerToken');
            $customerToken['usage'] = "SUBSCRIPTION";
            $customerToken->addChild('description', 'Customer Subscription');
            $klarnaExtraMerchantData = $include->addChild('klarnaExtraMerchantData');
            $this->_addSubscriptionElement($klarnaExtraMerchantData);
        }
        $session = $paymentMethodMask->addChild('session');
        $session['shopperIPAddress'] = $this->sessionData['shopperIpAddress'];
        $session['id'] = $this->sessionData['sessionId'];
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
            $this->shippingAddress['address1'],
            $this->shippingAddress['postalCode'],
            $this->shippingAddress['city'],
            $this->shippingAddress['state'],
            $this->shippingAddress['countryCode'],
            $this->shippingAddress['telephoneNumber']
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
            $this->billingAddress['address1'],
            $this->billingAddress['postalCode'],
            $this->billingAddress['city'],
            $this->billingAddress['state'],
            $this->billingAddress['countryCode'],
            $this->billingAddress['telephoneNumber']
        );
    }

    /**
     * Add address and its child tag to xml
     *
     * @param SimpleXMLElement $parentElement
     * @param string $firstName
     * @param string $lastName
     * @param string $address1
     * @param string $postalCode
     * @param string $city
     * @param string $state
     * @param string $countryCode
     * @param string $telephoneNumber
     */
    private function _addAddressElement(
        $parentElement,
        $firstName,
        $lastName,
        $address1,
        $postalCode,
        $city,
        $state,
        $countryCode,
        $telephoneNumber
    ) {
        $address = $parentElement->addChild('address');

        $firstNameElement = $address->addChild('firstName');
        $this->_addCDATA($firstNameElement, $firstName);

        $lastNameElement = $address->addChild('lastName');
        $this->_addCDATA($lastNameElement, $lastName);

        $streetElement1 = $address->addChild('address1');
        $this->_addCDATA($streetElement1, $address1);
        
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

        if ($state) {
            $stateElement = $address->addChild('state');
            $this->_addCDATA($stateElement, $state);
        }
        
        $countryCodeElement = $address->addChild('countryCode');
        $this->_addCDATA($countryCodeElement, $countryCode);
        
        $telephoneElement = $address->addChild('telephoneNumber');
        $this->_addCDATA($telephoneElement, $telephoneNumber);
    }

    /**
     * Add order line item and its child tag to xml
     *
     * @param SimpleXMLElement $order
     */
    private function _addOrderLineItemElement($order)
    {
        $diffAmt = 0;
        $totalAmount = 0;
        if ($this->amount < $this->_roundOfTotal($order)) {
            $diffAmt = $this->_roundOfTotal($order) - $this->amount;
        }

        $orderLinesElement = $order->addChild('orderLines');

        $orderlineitems = $this->orderlineitems;

        $orderTaxAmountElement = $orderLinesElement->addChild('orderTaxAmount');
        $this->_addCDATA($orderTaxAmountElement, $this->_amountAsInt($orderlineitems['orderTaxAmount']));

        $termsURLElement = $orderLinesElement->addChild('termsURL');
        $this->_addCDATA($termsURLElement, $orderlineitems['termsURL']);

        foreach ($orderlineitems['lineItem'] as $lineitem) {
            
            $totaldiscountamount = (isset($lineitem['totalDiscountAmount'])) ? $lineitem['totalDiscountAmount'] : 0;
            if ($lineitem['productType'] === 'bundle' && $diffAmt > 0) {
                $totaldiscountamount = $diffAmt;
                $totalAmount = $lineitem['totalAmount'] - $diffAmt;
            } else {
                $totalAmount = 0;
            }
            
            $this->_addLineItemElement(
                $orderLinesElement,
                $lineitem['reference'],
                $lineitem['name'],
                $lineitem['productType'],
                $lineitem['quantity'],
                $lineitem['quantityUnit'],
                $lineitem['unitPrice'],
                $lineitem['taxRate'],
                ($totalAmount > 0 ? $totalAmount : $lineitem['totalAmount']),
                $lineitem['totalTaxAmount'],
                $totaldiscountamount
            );
        }
    }

    /**
     * Add order line item element values to xml
     *
     * @param SimpleXMLElement $parentElement
     * @param string $reference
     * @param string $name
     * @param string $productType
     * @param string $quantity
     * @param string $quantityUnit
     * @param float $unitPrice
     * @param float $taxRate
     * @param float $totalAmount
     * @param float $totalTaxAmount
     * @param float $totalDiscountAmount
     */
    private function _addLineItemElement(
        $parentElement,
        $reference,
        $name,
        $productType,
        $quantity,
        $quantityUnit,
        $unitPrice,
        $taxRate,
        $totalAmount,
        $totalTaxAmount,
        $totalDiscountAmount = 0
    ) {
        $unitPrice = sprintf('%0.2f', $unitPrice);

        $lineitem = $parentElement->addChild('lineItem');

        if ($productType === 'shipping') {
            $lineitem->addChild('shippingFee');
        } elseif ($productType === 'downloadable' || $productType === 'virtual' || $productType === 'giftcard') {
            $lineitem->addChild('digital');
        } elseif ($productType === 'Store Credit') {
            $lineitem->addChild('storeCredit');
        } elseif ($productType === 'Gift Card') {
            $lineitem->addChild('giftCard');
        } else {
            $lineitem->addChild('physical');
        }
        $referenceElement = $lineitem->addChild('reference');
        $this->_addCDATA($referenceElement, $reference);

          $nameElement = $lineitem->addChild('name');
        $this->_addCDATA($nameElement, $name);

          $quantityElement = $lineitem->addChild('quantity');
        $this->_addCDATA($quantityElement, $quantity);

          $quantityUnitElement = $lineitem->addChild('quantityUnit');
        $this->_addCDATA($quantityUnitElement, $quantityUnit);

          $unitPriceElement = $lineitem->addChild('unitPrice');
        $this->_addCDATA($unitPriceElement, $this->_amountAsInt($unitPrice));

          $taxRateElement = $lineitem->addChild('taxRate');
        $this->_addCDATA($taxRateElement, $this->_amountAsInt($taxRate));

          $totalAmountElement = $lineitem->addChild('totalAmount');
        $this->_addCDATA($totalAmountElement, $this->_amountAsInt($totalAmount));

          $totalTaxAmountElement = $lineitem->addChild('totalTaxAmount');
        $this->_addCDATA($totalTaxAmountElement, $this->_amountAsInt($totalTaxAmount));

        if ($totalDiscountAmount > 0) {
            $totalDiscountAmountElement = $lineitem->addChild('totalDiscountAmount');
            $this->_addCDATA($totalDiscountAmountElement, $this->_amountAsInt($totalDiscountAmount));
        }
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
     * Round price
     *
     * @param Order $order
     * @return float $accTotalAmt
     */
    private function _roundOfTotal($order)
    {
        $accTotalAmt = 0;

        $orderlineitems = $this->orderlineitems;
        foreach ($orderlineitems['lineItem'] as $lineitem) {
            $totaldiscountamount = (isset($lineitem['totalDiscountAmount']))
                                    ? sprintf('%0.2f', $lineitem['totalDiscountAmount']) : 0;
            $taxrate = (isset($lineitem['taxRate']))
                                    ? sprintf('%0.2f', $lineitem['taxRate']) : 0;
            $unitPrice = sprintf('%0.2f', $lineitem['unitPrice']);
            $accTotalAmt += $taxrate + ($lineitem['quantity'] * $unitPrice) - $totaldiscountamount;
        }
        return $accTotalAmt;
    }
    
    /**
     * Add subsciption and its child tag to xml
     *
     * @param SimpleXMLElement $parentElement
     */
    private function _addSubscriptionElement($parentElement)
    {
        $fullName = $this->billingAddress['firstName'] . " " . $this->billingAddress['lastName'];
        $subscription = $parentElement->addChild('subscription');
        $nameElement = $subscription->addChild('name', $fullName);
        $subscriptionDays = $this->sessionData['subscriptionDays'];

        $startElement = $subscription->addChild('start');
        $startDate = $startElement->addChild('date');
        $today = new \DateTime();
        $this->_getStartdate($startDate, $today);

        $endElement = $subscription->addChild('end');
        $endDate = $endElement->addChild('date');
        $this->_getEndDate($endDate, $today, $subscriptionDays);
        $autoRenewElement = $subscription->addChild('autoRenew', 'false');
        $affiliateNameElement = $subscription->addChild('affiliateName', $fullName);
    }

    /**
     * Retrieve start date
     *
     * @param string $date
     * @param string $today
     */
    public function _getStartDate($date, $today)
    {
        $date['dayOfMonth'] = $today->format('d');
        $date['month'] = $today->format('m');
        $date['year'] = $today->format('Y');
        $this->_getTime($date, $today);
    }

    /**
     * Retrieve end date
     *
     * @param string $date
     * @param string $today
     * @param string $subscriptionDays
     */
    public function _getEndDate($date, $today, $subscriptionDays)
    {
        if ($subscriptionDays) {
            date_add($today, date_interval_create_from_date_string($subscriptionDays." days"));
        } elseif ($this->billingAddress['countryCode'] === 'US' || $this->billingAddress['countryCode'] === 'GB') {
            date_add($today, date_interval_create_from_date_string("30 days"));
        } else {
            date_add($today, date_interval_create_from_date_string("14 days"));
        }
        $date['dayOfMonth'] = $today->format('d');
        $date['month'] = $today->format('m');
        $date['year'] = $today->format('Y');
        $this->_getTime($date, $today);
    }

    /**
     * Returns time()
     *
     * @param string $date
     * @param today $today
     */
    public function _getTime($date, $today)
    {
        $date['hour'] = $today->format('H');
        $date['minute'] = $today->format('i');
        $date['second'] = $today->format('s');
    }
}
