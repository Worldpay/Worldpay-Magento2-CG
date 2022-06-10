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
     * [$paymentType description]
     * @var [type]
     */
    private $paymentType;
    /**
     * [$shopperEmail description]
     * @var [type]
     */
    private $shopperEmail;
    /**
     * [$statementNarrative description]
     * @var [type]
     */
    private $statementNarrative;
    /**
     * [$acceptHeader description]
     * @var [type]
     */
    private $acceptHeader;
    /**
     * [$userAgentHeader description]
     * @var [type]
     */
    private $userAgentHeader;
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
     * [$paymentPagesEnabled description]
     * @var [type]
     */
    private $paymentPagesEnabled;
    /**
     * [$installationId description]
     * @var [type]
     */
    private $installationId;
    /**
     * [$hideAddress description]
     * @var [type]
     */
    private $hideAddress;
    /**
     * [$exponent description]
     * @var [type]
     */
    private $exponent;
    /**
     * [$threeDSecureConfig description]
     * @var [type]
     */
    private $threeDSecureConfig;
    /**
     * [$tokenRequestConfig description]
     * @var [type]
     */
    private $tokenRequestConfig;
    
    /**
     * [__construct description]
     */
    public function __construct()
    {
         $this->threeDSecureConfig = new \Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecure();

        $this->tokenRequestConfig = false;
    }

    /**
     * [build description]
     *
     * @param  [type] $merchantCode        [description]
     * @param  [type] $orderCode           [description]
     * @param  [type] $orderDescription    [description]
     * @param  [type] $currencyCode        [description]
     * @param  [type] $amount              [description]
     * @param  [type] $paymentType         [description]
     * @param  [type] $shopperEmail        [description]
     * @param  [type] $statementNarrative  [description]
     * @param  [type] $acceptHeader        [description]
     * @param  [type] $userAgentHeader     [description]
     * @param  [type] $shippingAddress     [description]
     * @param  [type] $billingAddress      [description]
     * @param  [type] $paymentPagesEnabled [description]
     * @param  [type] $installationId      [description]
     * @param  [type] $hideAddress         [description]
     * @param  [type] $orderlineitems      [description]
     * @param  [type] $exponent            [description]
     * @return [type]                      [description]
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
        $exponent
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

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $submit = $this->_addSubmitElement($xml);
        $this->_addOrderElement($submit);

        return $xml;
    }
    /**
     * [_addSubmitElement description]
     *
     * @param [type] $xml [description]
     */
    private function _addSubmitElement($xml)
    {
        return $xml->addChild('submit');
    }
    /**
     * [_addOrderElement description]
     *
     * @param [type] $submit [description]
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
        $this->_addPaymentMethodMaskElement($order);
        $this->_addShopperElement($order);
        $this->_addShippingElement($order);
        $this->_addBillingElement($order);
        $this->_addOrderLineItemElement($order);
        $this->_addDynamic3DSElement($order);
        if (!empty($this->statementNarrative)) {
            $this->_addStatementNarrativeElement($order);
        }
        return $order;
    }
    /**
     * [_addDescriptionElement description]
     *
     * @param [type] $order [description]
     */
    private function _addDescriptionElement($order)
    {
        $description = $order->addChild('description');
        $this->_addCDATA($description, $this->orderDescription);
    }
    /**
     * [_addAmountElement description]
     *
     * @param [type] $order [description]
     */
    private function _addAmountElement($order)
    {
        $amountElement = $order->addChild('amount');
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = $this->exponent;
        //$amountElement['value'] = $this->_amountAsInt($this->amount);
        $amountElement['value'] = $this->_amountAsInt($this->_roundOfTotal($order));
    }
    /**
     * [_addDynamic3DSElement description]
     *
     * @param [type] $order [description]
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
     * [_addPaymentMethodMaskElement description]
     *
     * @param [type] $order [description]
     */
    private function _addPaymentMethodMaskElement($order)
    {
        $paymentMethodMask = $order->addChild('paymentMethodMask');
        
        $include = $paymentMethodMask->addChild('include');
        $include['code'] = $this->paymentType;
    }
    /**
     * [_addShopperElement description]
     *
     * @param [type] $order [description]
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
     * [_addShippingElement description]
     *
     * @param [type] $order [description]
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
     * [_addBillingElement description]
     *
     * @param [type] $order [description]
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
     * [_addAddressElement description]
     *
     * @param [type] $parentElement [description]
     * @param [type] $firstName     [description]
     * @param [type] $lastName      [description]
     * @param [type] $street        [description]
     * @param [type] $postalCode    [description]
     * @param [type] $city          [description]
     * @param [type] $countryCode   [description]
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
     * [_addOrderLineItemElement description]
     *
     * @param [type] $order [description]
     */
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
    /**
     * [_addLineItemElement description]
     *
     * @param [type]  $parentElement       [description]
     * @param [type]  $reference           [description]
     * @param [type]  $name                [description]
     * @param [type]  $quantity            [description]
     * @param [type]  $quantityUnit        [description]
     * @param [type]  $unitPrice           [description]
     * @param [type]  $taxRate             [description]
     * @param [type]  $totalAmount         [description]
     * @param [type]  $totalTaxAmount      [description]
     * @param integer $totalDiscountAmount [description]
     */
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
        $totalAmount = $quantity * $unitPrice;

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
    /**
     * [_addCDATA description]
     *
     * @param [type] $element [description]
     * @param [type] $content [description]
     */
    private function _addCDATA($element, $content)
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
     * [_roundOfTotal description]
     *
     * @param  [type] $order [description]
     * @return [type]        [description]
     */
    private function _roundOfTotal($order)
    {
        $accTotalAmt = 0;

        $orderlineitems = $this->orderlineitems;
        foreach ($orderlineitems['lineItem'] as $lineitem) {
            $totaldiscountamount = (isset($lineitem['totalDiscountAmount']))
                                    ? sprintf('%0.2f', $lineitem['totalDiscountAmount']) : 0;
            $unitPrice = sprintf('%0.2f', $lineitem['unitPrice']);
            $accTotalAmt = $accTotalAmt + ($lineitem['quantity'] * $unitPrice) - $totaldiscountamount;
        }
        return $accTotalAmt;
    }
}
