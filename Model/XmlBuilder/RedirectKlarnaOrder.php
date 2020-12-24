<?php
namespace Sapient\Worldpay\Model\XmlBuilder;

use Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecureConfig;

class RedirectKlarnaOrder
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
    private $exponent;
    private $threeDSecureConfig;
    private $tokenRequestConfig;
    private $sessionData;
    private $orderContent;

    public function __construct()
    {
         $this->threeDSecureConfig = new \Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecure();

        $this->tokenRequestConfig = false;
    }

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
        $orderContent
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

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $submit = $this->_addSubmitElement($xml);
        $this->_addOrderElement($submit);

        return $xml;
    }

    private function _addSubmitElement($xml)
    {
        return $xml->addChild('submit');
    }

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

    private function _addDescriptionElement($order)
    {
        $description = $order->addChild('description');
        $this->_addCDATA($description, $this->orderDescription);
    }

    private function _addAmountElement($order)
    {
        $amountElement = $order->addChild('amount');
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = $this->exponent;
        //$amountElement['value'] = $this->_amountAsInt($this->amount);
        $amountElement['value'] = $this->_amountAsInt($this->_roundOfTotal($order));
    }
    
    private function _addOrderContentElement($order)
    {
        $orderContent = $order->addChild('orderContent');
        $this->_addCDATA($orderContent, $this->orderDescription);
    }

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
        $this->_addCDATA($postalCodeElement, $postalCode);

        $cityElement = $address->addChild('city');
        $this->_addCDATA($cityElement, $city);

        $stateElement = $address->addChild('state');
        $this->_addCDATA($stateElement, $state);
        
        $countryCodeElement = $address->addChild('countryCode');
        $this->_addCDATA($countryCodeElement, $countryCode);
        
        $telephoneElement = $address->addChild('telephoneNumber');
        $this->_addCDATA($telephoneElement, $telephoneNumber);
    }

    private function _addOrderLineItemElement($order)
    {
        $orderLinesElement = $order->addChild('orderLines');

        $orderlineitems = $this->orderlineitems;

        $orderTaxAmountElement = $orderLinesElement->addChild('orderTaxAmount');
        $this->_addCDATA($orderTaxAmountElement, $this->_amountAsInt($orderlineitems['orderTaxAmount']));

         $termsURLElement = $orderLinesElement->addChild('termsURL');
        $this->_addCDATA($termsURLElement, $orderlineitems['termsURL']);

        foreach ($orderlineitems['lineItem'] as $lineitem) {
            $totaldiscountamount = (isset($lineitem['totalDiscountAmount'])) ? $lineitem['totalDiscountAmount'] : 0;
            $this->_addLineItemElement(
                $orderLinesElement,
                $lineitem['reference'],
                $lineitem['name'],
                $lineitem['quantity'],
                $lineitem['quantityUnit'],
                $lineitem['unitPrice'],
                $lineitem['taxRate'],
                $lineitem['totalAmount'],
                $lineitem['totalTaxAmount'],
                $totaldiscountamount
            );
        }
    }

    private function _addLineItemElement(
        $parentElement,
        $reference,
        $name,
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

        $lineitem->addChild('physical');

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

    private function _addCDATA($element, $content)
    {
        $node = dom_import_simplexml($element);
        $no   = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($content));
    }

    private function _amountAsInt($amount)
    {
        return round($amount, $this->exponent, PHP_ROUND_HALF_EVEN) * pow(10, $this->exponent);
    }

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

    public function _getStartDate($date, $today)
    {
        $date['dayOfMonth'] = $today->format('d');
        $date['month'] = $today->format('m');
        $date['year'] = $today->format('Y');
        $this->_getTime($date, $today);
    }

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

    public function _getTime($date, $today)
    {
        $date['hour'] = $today->format('H');
        $date['minute'] = $today->format('i');
        $date['second'] = $today->format('s');
    }
}
