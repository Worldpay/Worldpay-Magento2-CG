<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

use Sapient\Worldpay\Model\XmlBuilder\Config\TokenConfiguration;
use SimpleXMLElement;
use Sapient\Worldpay\Model\XmlBuilder\Config\ThreeDSecure;

/**
 * Build xml for RedirectOrder request
 */
class RedirectOrder extends AbstractXmlBuilder
{
    public const DYNAMIC3DS_DO3DS = 'do3DS';
    public const DYNAMIC3DS_NO3DS = 'no3DS';
    public const TOKEN_SCOPE = 'shopper';

    protected string $merchantCode;
    protected array $orderParameters;
    protected string $installationId;
    protected string $captureDelay;
    private ?string $shopperId;
    public array $paymentDetails;
    public array $cusDetails;
    public array $thirdPartyData;
    private ThreeDSecure $threeDSecureConfig;
    private TokenConfiguration $tokenRequestConfig;

    public function __construct()
    {
        $this->threeDSecureConfig = new ThreeDSecure();
    }

    public function build(
        $merchantCode,
        $orderParameters,
        $installationId,
        $captureDelay,
        array $tokenRequestConfigArgs = [],
    ): SimpleXMLElement
    {
        $this->merchantCode = $merchantCode;
        $this->installationId = $installationId;
        $this->orderParameters = $orderParameters;
        $this->captureDelay = $captureDelay;
        $this->paymentDetails = $orderParameters['paymentDetails'];
        $this->cusDetails = $orderParameters['cusDetails'];
        $this->thirdPartyData = !empty($orderParameters['thirdPartyData']) && $orderParameters['thirdPartyData'] !== ''
            ? $orderParameters['thirdPartyData'] : [];

        if(!empty($tokenRequestConfigArgs)) {
            $this->tokenRequestConfig = new TokenConfiguration($tokenRequestConfigArgs['tokenRequestConfig']);
            $this->shopperId = $tokenRequestConfigArgs['shopperId'];
        }
        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $submit = $xml->addChild('submit');
        $this->_addOrderElement($submit);

        return $xml;
    }

    protected function _addOrderElement(SimpleXMLElement $submit): void
    {
        $order = $submit->addChild('order');
        $order['orderCode'] = $this->orderParameters['orderCode'];

        if ($this->orderParameters['paymentPagesEnabled']) {
            $order['installationId'] = $this->installationId;
            $order['fixContact'] = $this->orderParameters['hideAddress'] ? 'false' : 'true';
            $order['hideContact'] = $this->orderParameters['hideAddress'] ? 'false' : 'true';
        }

        if ($this->captureDelay!="") {
            $order['captureDelay'] = $this->captureDelay;
        }
        $this->_addDescriptionElement($order);
        $this->_addAmountElement($order, $this->orderParameters['amount']);
        $this->_addOrderContentElement($order);
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
        } elseif (!empty($redirectOrderParams['is_paybylink_order'])) {
            $this->addOrderLineItemsXML($order);
        }

        if (!empty($this->thirdPartyData)) {
            $this->_addThirdPartyData($order);
        }
        $this->_addDynamic3DSElement($order);
        $this->_addCreateTokenElement($order);
        if (!empty($this->statementNarrative)) {
            $this->_addStatementNarrativeElement($order);
        }
        $this->_addFraudSightData($order);
    }

    protected function _addDescriptionElement(SimpleXMLElement $order): void
    {
        $description = $order->addChild('description');
        if (!empty($this->thirdPartyData['statement'])) {
            $this->_addCDATA($description, $this->thirdPartyData['statement']);
        } else {
            $this->_addCDATA($description, $this->orderParameters['orderDescription']);
        }
    }

    protected function _addAmountElement(SimpleXMLElement $order, float $value): void
    {
        $amountElement = $order->addChild('amount');
        $amountElement['currencyCode'] = $this->orderParameters['currencyCode'];
        $amountElement['exponent'] = $this->orderParameters['exponent'];
        $amountElement['value'] = $this->_amountAsInt($value);
    }

    protected function _addStatementNarrativeElement(SimpleXMLElement $order): void
    {
        $order->addChild('statementNarrative', $this->orderParameters['statementNarrative']);
    }

    private function _addDynamic3DSElement(SimpleXMLElement $order): void
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

    private function _addCreateTokenElement(SimpleXMLElement $order): void
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
        if ($this->tokenRequestConfig->getTokenReason($this->orderParameters['orderCode'])) {
            $createTokenElement->addChild(
                'tokenReason',
                $this->tokenRequestConfig->getTokenReason($this->orderParameters['orderCode'])
            );
        }
    }

    protected function _addPaymentMethodMaskElement(SimpleXMLElement $order): void
    {
        $paymentMethodMask = $order->addChild('paymentMethodMask');
        if ($this->orderParameters['saveCardEnabled'] && $this->orderParameters['storedCredentialsEnabled']) {
            $this->_addStoredCredentials($paymentMethodMask);
        }

        $paymentTypes = explode(",", $this->orderParameters['paymentType']);

        foreach ($paymentTypes as $paymentType) {
            $include = $paymentMethodMask->addChild('include');
            $include['code'] = $paymentType;
        }
    }

    protected function _addShopperElement(SimpleXMLElement $order): void
    {
        $shopper = $order->addChild('shopper');
        $shopper->addChild('shopperEmailAddress', $this->orderParameters['shopperEmail']);
        if (!$this->paymentDetails['token_type']) {
            if ($this->tokenRequestConfig->istokenizationIsEnabled()) {
                $shopper->addChild('authenticatedShopperID', $this->shopperId);
            } elseif (isset($this->paymentDetails['tokenCode'])) {
                $shopper->addChild('authenticatedShopperID', $this->paymentDetails['customerId']);
            }
        }

        $browser = $shopper->addChild('browser');

        $acceptHeader = $browser->addChild('acceptHeader');
        $this->_addCDATA($acceptHeader, $this->orderParameters['acceptHeader']);

        $userAgentHeader = $browser->addChild('userAgentHeader');
        $this->_addCDATA($userAgentHeader, $this->orderParameters['userAgentHeader']);
    }

    protected function _addPaymentDetailsElement(SimpleXMLElement $order): void
    {
        $paymentDetailsElement = $order->addChild('paymentDetails');
        $this->_addPaymentDetailsForTokenOrder($paymentDetailsElement);
        $this->_addPaymentDetailsForStoredCredentialsOrder($paymentDetailsElement);
        $session = $paymentDetailsElement->addChild('session');
        $session['id'] = $this->paymentDetails['sessionId'];
        $session['shopperIPAddress'] = $this->paymentDetails['shopperIpAddress'];
    }

    protected function _addStoredCredentials(SimpleXMLElement $paymentDetailsElement): void
    {
        $storedCredentials  = $paymentDetailsElement->addChild('storedCredentials');
        $storedCredentials['usage'] = "FIRST";
        $isSubscriptionOrder = isset($this->paymentDetails['subscription_order'])? true : false;
        if ($isSubscriptionOrder) {
            $storedCredentials['customerInitiatedReason'] = "RECURRING";
        }
    }

    protected function _addPaymentDetailsForStoredCredentialsOrder(SimpleXMLElement $paymentDetailsElement): void
    {
        $storedCredentials  = $paymentDetailsElement->addChild('storedCredentials');
        $storedCredentials['usage'] = "USED";
    }

    protected function _addPaymentDetailsForTokenOrder(SimpleXMLElement $paymentDetailsElement): void
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

    protected function _addThirdPartyData(SimpleXMLElement $order): void
    {
        $thirdParty = $order->addChild('thirdPartyData');
        if (!empty($this->thirdPartyData['instalment'])) {
            $thirdParty->addChild('instalments', $this->thirdPartyData['instalment']);
        }
        if ($this->orderParameters['billingAddress']['countryCode'] == 'BR' &&
            !empty($this->orderParameters['shippingfee']['shippingfee'])) {
            $firstInstallment = $thirdParty->addChild('firstInstalment');
            $firstInstallment = $firstInstallment->addChild('amountNoCurrency');
            $firstInstallment['value'] =$this->_amountAsInt($this->orderParameters['shippingfee']['shippingfee']);
        }
        if (!empty($this->thirdPartyData['cpf'])) {
            $thirdParty->addChild('cpf', $this->thirdPartyData['cpf']);
        }
    }

    private function _addBranchSpecificExtension(SimpleXMLElement $order): void
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
        $this->_addAmountElement($salesTaxElement, $this->paymentDetails['salesTax']);

        if (isset($this->cusDetails['discount_amount'])) {
            $discountAmount = $purchase->addChild('discountAmount');
            $discountAmountElement = $discountAmount->addChild('amount');
            $this->_addAmountElement($discountAmountElement, $this->cusDetails['discount_amount']);
        }
        if (isset($this->cusDetails['shipping_amount'])) {
            $shippingAmount = $purchase->addChild('shippingAmount');
            $shippingAmountElement = $shippingAmount->addChild('amount');
            $this->_addAmountElement($shippingAmountElement, $this->cusDetails['shipping_amount']);
        }

        if (isset($this->paymentDetails['dutyAmount'])) {
            $dutyAmount = $purchase->addChild('dutyAmount');
            $dutyAmountElement = $dutyAmount->addChild('amount');
            $this->_addAmountElement($dutyAmountElement, $this->paymentDetails['dutyAmount']);
        }

        $purchase->addChild('destinationPostalCode', $this->orderParameters['shippingAddress']['postalCode']);
        $purchase->addChild('destinationCountryCode', $this->orderParameters['shippingAddress']['countryCode']);

        $orderDate = $purchase->addChild('orderDate');
        $dateElement = $orderDate->addChild('date');
        $today = new \DateTime();
        $dateElement['dayOfMonth'] = $today->format('d');
        $dateElement['month'] = $today->format('m');
        $dateElement['year'] = $today->format('Y');

        $purchase->addChild('taxExempt', $this->paymentDetails['salesTax'] > 0 ? 'false' : 'true');

        $this->_addL23OrderLineItemElement($purchase);
    }

    private function _addL23OrderLineItemElement(SimpleXMLElement $purchase): void
    {
        $orderLineItems = $this->orderParameters['orderLineItems'];

        foreach ($orderLineItems['lineItem'] as $lineItem) {
            $this->_addLineItemElement($purchase, $lineItem);
        }
    }

    private function _addLineItemElement(SimpleXMLElement $parentElement, array $lineItem): void
    {
        $item = $parentElement->addChild('item');

        $descriptionElement = $item->addChild('description');
        $this->_addCDATA($descriptionElement, $lineItem['description']);

        $productCodeElement = $item->addChild('productCode');
        $this->_addCDATA($productCodeElement, $lineItem['productCode']);

        if ($lineItem['commodityCode']) {
            $commodityCodeElement = $item->addChild('commodityCode');
            $this->_addCDATA($commodityCodeElement, $lineItem['commodityCode']);
        }
        $quantityElement = $item->addChild('quantity');
        $this->_addCDATA($quantityElement, $lineItem['quantity']);

        $unitCostElement = $item->addChild('unitCost');
        $unitCostAmount = $unitCostElement->addChild('amount');
        $this->_addAmountElement($unitCostAmount, $lineItem['unitCost']);

        if ($lineItem['unitOfMeasure']) {
            $unitOfMeasureElement = $item->addChild('unitOfMeasure');
            $this->_addCDATA($unitOfMeasureElement, $lineItem['unitOfMeasure']);
        }

        $itemTotalElement = $item->addChild('itemTotal');
        $itemTotalElementAmount = $itemTotalElement->addChild('amount');
        $this->_addAmountElement($itemTotalElementAmount, $lineItem['itemTotal']);

        $itemTotalWithTaxElement = $item->addChild('itemTotalWithTax');
        $itemTotalWithTaxElementAmount = $itemTotalWithTaxElement->addChild('amount');
        $this->_addAmountElement($itemTotalWithTaxElementAmount, $lineItem['itemTotalWithTax']);

        $itemDiscountAmountElement = $item->addChild('itemDiscountAmount');
        $itemDiscountElementAmount = $itemDiscountAmountElement->addChild('amount');
        $this->_addAmountElement($itemDiscountElementAmount, $lineItem['itemDiscountAmount']);

        $taxAmountElement = $item->addChild('taxAmount');
        $taxAmountElementAmount = $taxAmountElement->addChild('amount');
        $this->_addAmountElement($taxAmountElementAmount, $lineItem['taxAmount']);
    }

    private function addOrderLineItemsXML(SimpleXMLElement $order):void
    {
        $orderLinesElement = $order->addChild('orderLines');

        $orderLineItems = $this->orderParameters['orderLineItems'];

        $orderTaxAmountElement = $orderLinesElement->addChild('orderTaxAmount');
        $this->_addCDATA($orderTaxAmountElement, $this->_amountAsInt($orderLineItems['orderTaxAmount']));

        $termsURLElement = $orderLinesElement->addChild('termsURL');
        $this->_addCDATA($termsURLElement, $orderLineItems['termsURL']);

        foreach ($orderLineItems['lineItem'] as $lineItem) {
            $this->_addOrderLineItemElement($orderLinesElement, $lineItem, $lineItem['totalDiscountAmount'] ?? 0);
        }
    }

    private function _addOrderLineItemElement($parentElement, $lineItem, $totalDiscountAmount = 0) {
        $unitPrice = sprintf('%0.2f', $lineItem['unitPrice']);

        $lineItemElement = $parentElement->addChild('lineItem');

        $productType = $lineItem['productType'];

        if ($productType === 'shipping') {
            $lineItemElement->addChild('shippingFee');
        } elseif ($productType === 'downloadable' || $productType === 'virtual' || $productType === 'giftcard') {
            $lineItemElement->addChild('digital');
        } elseif ($productType === 'Store Credit') {
            $lineItemElement->addChild('storeCredit');
        } elseif ($productType === 'Gift Card') {
            $lineItemElement->addChild('giftCard');
        } else {
            $lineItemElement->addChild('physical');
        }
        $referenceElement = $lineItemElement->addChild('reference');
        $this->_addCDATA($referenceElement, $lineItem['reference']);

        $nameElement = $lineItemElement->addChild('name');
        $this->_addCDATA($nameElement, $lineItem['name']);

        $quantityElement = $lineItemElement->addChild('quantity');
        $this->_addCDATA($quantityElement, $lineItem['quantity']);

        $quantityUnitElement = $lineItemElement->addChild('quantityUnit');
        $this->_addCDATA($quantityUnitElement, $lineItem['quantityUnit']);

        $unitPriceElement = $lineItemElement->addChild('unitPrice');
        $this->_addCDATA($unitPriceElement, $this->_amountAsInt($unitPrice));

        $taxRateElement = $lineItemElement->addChild('taxRate');
        $this->_addCDATA($taxRateElement, $this->_amountAsInt($lineItem['taxRate']));

        $totalAmountElement = $lineItemElement->addChild('totalAmount');
        $this->_addCDATA($totalAmountElement, $this->_amountAsInt($lineItem['totalAmount']));

        $totalTaxAmountElement = $lineItemElement->addChild('totalTaxAmount');
        $this->_addCDATA($totalTaxAmountElement, $this->_amountAsInt($lineItem['totalTaxAmount']));

        if ($totalDiscountAmount > 0) {
            $totalDiscountAmountElement = $lineItemElement->addChild('totalDiscountAmount');
            $this->_addCDATA($totalDiscountAmountElement, $this->_amountAsInt($totalDiscountAmount));
        }
    }

    protected function _addCseElement($paymentDetailsElement)
    {
        $cseElement = $paymentDetailsElement->addChild('CSE-DATA');
        $cseElement->addChild('encryptedData', $this->paymentDetails['encryptedData']);

        return $cseElement;
    }
}
