<?php

/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

/**
 * Build xml for Capture request
 */
class Capture
{
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
    private $currencyCode;
    /**
     * @var float
     */
    private $amount;
    /**
     * @var string|array|float
     */
    private $exponent;

    /**
     * Build xml for processing Request
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @param string $currencyCode
     * @param float $amount
     * @param string|array|float $exponent
     * @param Order $order
     * @param mixed $captureType
     * @param bool $isMultishippingOrder
     * @param string|null $paymentType
     * @param array|null $invoicedItems
     * @return SimpleXMLElement $xml
     */
    public function build(
        $merchantCode,
        $orderCode,
        $currencyCode,
        $amount,
        $exponent,
        $order,
        $captureType,
        $isMultishippingOrder,
        $paymentType = null,
        $invoicedItems = null
    ) {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->exponent = $exponent;
        $this->order = $order;
        $this->captureType = $captureType;
        $this->isMultishippingOrder = $isMultishippingOrder;
        $this->invoicedItems = $invoicedItems;

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $modify = $this->_addModifyElement($xml);
        $orderModification = $this->_addOrderModificationElement($modify);
        $capture = $this->_addCapture($orderModification);
        $this->_addCaptureElement($capture);
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        
        $level23DataEnabled = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                        ->getValue('worldpay/level23_config/level23', $storeScope);
        
         //Level23 data changes
        $countryCode = $order->getBillingAddress()->getCountryId();

        if ($level23DataEnabled
           && ($paymentType === 'ECMC-SSL' || $paymentType === 'VISA-SSL')
           && ($countryCode === 'US' || $countryCode === 'CA')) {
            $this->_addBranchSpecificExtension($order, $capture);
        }
        
        if (!empty($paymentType) && strpos($paymentType, "KLARNA") !== false
            && is_array($this->invoicedItems) && !empty($this->invoicedItems['trackingId'])) {
            $this->_addShippingElement($capture, $order, $this->invoicedItems);
            $this->_addKlarnaOrderLineItemElement($order, $capture, $this->invoicedItems);
        }

        return $xml;
    }

    /**
     * Add tag modify to xml
     *
     * @param SimpleXMLElement $xml
     * @return SimpleXMLElement
     */
    private function _addModifyElement($xml)
    {
        return $xml->addChild('modify');
    }

    /**
     * Add tag orderModification to xml
     *
     * @param SimpleXMLElement $modify
     * @return SimpleXMLElement $orderModification
     */
    private function _addOrderModificationElement($modify)
    {
        $orderModification = $modify->addChild('orderModification');
        $orderModification['orderCode'] = $this->orderCode;

        return $orderModification;
    }

    /**
     * Add tag capture to xml
     *
     * @param SimpleXMLElement $orderModification
     * @return SimpleXMLElement $capture
     */
    private function _addCapture($orderModification)
    {
        $capture = $orderModification->addChild('capture');
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        
        $autoInvoice = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                ->getValue('worldpay/general_config/capture_automatically', $storeScope);
        $partialCapture = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                ->getValue('worldpay/partial_capture_config/partial_capture', $storeScope);
        //check the partial capture
        if ($this->captureType == 'partial' && $partialCapture) {
            $capture['reference']= 'Partial Capture';
        }
        /** Start Multishipping code */
        if ($this->captureType == 'partial' && $partialCapture && $this->isMultishippingOrder) {
            $capture['reference']= 'Partial Capture - '.$this->order->getIncrementId();
        }
        if ($this->captureType == 'full' && $this->isMultishippingOrder) {
            $capture['reference']= 'Capture - '.$this->order->getIncrementId();
        }
        /** End Multishipping code */
        return $capture;
    }

    /**
     * Add tag date, amount to xml
     *
     * @param SimpleXMLElement $capture
     */
    private function _addCaptureElement($capture)
    {
        // data
        $today = new \DateTime();
        $date = $capture->addChild('date');
        $date['dayOfMonth'] = $today->format('d');
        $date['month'] = $today->format('m');
        $date['year'] = $today->format('Y');

        $amountElement = $capture->addChild('amount');
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = $this->exponent;
        $amountElement['value'] = $this->_amountAsInt($this->amount);
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        
        $autoInvoice = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                        ->getValue('worldpay/general_config/capture_automatically', $storeScope);
        $partialCapture = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                        ->getValue('worldpay/partial_capture_config/partial_capture', $storeScope);
       
        //check the partial capture
        if ($this->captureType == 'partial' && $partialCapture) {
            $amountElement['debitCreditIndicator'] = 'credit';
        }
    }

    /**
     * Returns the rounded value of num to specified precision
     *
     * @param float $amount
     * @return int
     */
    private function _amountAsInt($amount)
    {
        if (!empty($amount)) {
            return round($amount, $this->exponent, PHP_ROUND_HALF_EVEN) * pow(10, $this->exponent);
        } else {
            return (int)($amount);
        }
    }

    /**
     * Add tag Shipping to xml
     *
     * @param SimpleXMLElement $capture
     * @param Order $order
     * @param array $invoicedItems
     *
     * Descrition : Adding additional shipping tag for Klarna
     */
    private function _addShippingElement($capture, $order, $invoicedItems)
    {
        // data
        $shippingElement = $capture->addChild('shipping');
        $shippingInfoElement = $shippingElement->addChild('shippingInfo');
        $shippingInfoElement['trackingId'] = $invoicedItems['trackingId'];
    }
    
     /**
      * Add branchSpecificExtension and its child tag to xml
      *
      * @param SimpleXMLElement $order
      * @param mixed $capture
      */
    private function _addBranchSpecificExtension($order, $capture)
    {
        $branchSpecificExtension = $capture->addChild('branchSpecificExtension');
        $purchase = $branchSpecificExtension->addChild('purchase');
        if ($order->getCustomerIsGuest()) {
            $purchase->addChild('customerReference', 'guest');
        } else {
            $purchase->addChild('customerReference', $order->getCustomerId());
        }
         
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        
        $cardAcceptorTaxId = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                        ->getValue('worldpay/level23_config/CardAcceptorTaxId', $storeScope);
        
        $dutyAmount = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                        ->getValue('worldpay/level23_config/duty_amount', $storeScope);
        
         $taxAmount = $order->getTaxAmount();
         $taxAmountNew = abs($taxAmount);
         
         $taxApplied = 'false';
        if ($taxAmountNew != 0) {
            $taxApplied = 'true';
        }
        
        if ($taxApplied == 'true') {
            $purchase->addChild('cardAcceptorTaxId', $cardAcceptorTaxId);
        }
         
        if ($taxAmountNew) {
            $salesTax = $purchase->addChild('salesTax');
            $this->_addAmountElementCapture($salesTax, $this->currencyCode, $this->exponent, $taxAmountNew);
        }
         
         $orderDiscountAmount = abs($order->getDiscountAmount());
        if (($orderDiscountAmount)) {
            $discountAmountXml = $purchase->addChild('discountAmount');
            $this->_addAmountElementCapture(
                $discountAmountXml,
                $this->currencyCode,
                $this->exponent,
                $order->getDiscountAmount()
            );
        }
        
         $shippingAmnt = (float)$order->getShippingAmount();
         
        if (($shippingAmnt)) {
            $shippingAmountXml = $purchase->addChild('shippingAmount');
            $this->_addAmountElementCapture(
                $shippingAmountXml,
                $this->currencyCode,
                $this->exponent,
                $shippingAmnt
            );
        }

        if ($dutyAmount) {
            $dutyAmountXml = $purchase->addChild('dutyAmount');
            $this->_addAmountElementCapture($dutyAmountXml, $this->currencyCode, $this->exponent, $dutyAmount);
        }
        
        $billingaddress = $order->getBillingAddress();
        $billingpostcode = $billingaddress->getPostcode();
        //$purchase->addChild('shipFromPostalCode', '');
        $purchase->addChild('destinationPostalCode', $billingpostcode);
        
        if ($order->getShippingAddress() != null) {
            $countryCode = $order->getShippingAddress()->getCountryId();
            $purchase->addChild('destinationCountryCode', $countryCode);
        }

        $orderDate = $purchase->addChild('orderDate');
        $dateElement = $orderDate->addChild('date');
        $today = new \DateTime();
        $dateElement['dayOfMonth'] = $today->format('d');
        $dateElement['month'] = $today->format('m');
        $dateElement['year'] = $today->format('Y');
        
        $purchase->addChild('taxExempt', $taxApplied);
    
        $this->_addL23OrderLineItemElement($order, $purchase, $capture);
    }
    
    /**
     * Add all order line item element values to xml
     *
     * @param Order $order
     * @param mixed $purchase
     * @param mixed $capture
     */
    private function _addL23OrderLineItemElement($order, $purchase, $capture)
    {
        
        foreach ($order->getAllItems() as $lineitem) {
            $this->_addLineItemElement(
                $purchase,
                $lineitem['name'],
                $lineitem['sku'],
                $lineitem['commodityCode'],
                $lineitem['qty_ordered'],
                $lineitem['base_price'],
                $lineitem['unitOfMeasure'],
                $lineitem['base_row_total'],
                $lineitem['row_total_incl_tax'],
                $lineitem['discount_amount'],
                $lineitem['base_tax_amount']
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
        
        if ($description == '') {
            
            $description = 'No description available';
        }
        $descriptionElement = $item->addChild('description');
        $this->_addCDATA($descriptionElement, substr($description, 0, 12));
        
        $productCodeElement = $item->addChild('productCode');
        $this->_addCDATA($productCodeElement, substr($productCode, 0, 12));
        
        if ($commodityCode) {
            $commodityCodeElement = $item->addChild('commodityCode');
            $this->_addCDATA($commodityCodeElement, substr($commodityCode, 0, 12));
        }
        
        $quantityElement = $item->addChild('quantity');
        
        $quantityNew = abs($quantity);
         
        $this->_addCDATA($quantityElement, $quantityNew);

        $unitCostElement = $item->addChild('unitCost');
        $this->_addAmountElement($unitCostElement, $this->currencyCode, $this->exponent, $unitCost);
        
        if ($unitOfMeasure) {
            $unitOfMeasureElement = $item->addChild('unitOfMeasure');
            $this->_addCDATA($unitOfMeasureElement, substr($unitOfMeasure, 0, 12));
        }
        
        $itemTotalElement = $item->addChild('itemTotal');
        $this->_addAmountElement($itemTotalElement, $this->currencyCode, $this->exponent, $itemTotal);

        $itemTotalWithTaxElement = $item->addChild('itemTotalWithTax');
        $this->_addAmountElement($itemTotalWithTaxElement, $this->currencyCode, $this->exponent, $itemTotalWithTax);

        if ($itemDiscountAmount) {
            $itemDiscountAmountElement = $item->addChild('itemDiscountAmount');
            $this->_addAmountElement(
                $itemDiscountAmountElement,
                $this->currencyCode,
                $this->exponent,
                $itemDiscountAmount
            );
        }
        
        if ($taxAmount) {
            $taxAmountElement = $item->addChild('taxAmount');
            $this->_addAmountElement($taxAmountElement, $this->currencyCode, $this->exponent, $taxAmount);
        }
    }
    
    /**
     * Adding Klarna order line items tag to xml
     *
     * @param Order $order
     * @param SimpleXMLElement $capture
     * @param array $invoicedItems
     */
    private function _addKlarnaOrderLineItemElement($order, $capture, $invoicedItems)
    {
        $orderLinesElement = $capture->addChild('orderLines');
        
        //orderTaxAmount
        $orderTaxAmountElement = $orderLinesElement->addChild('orderTaxAmount');
        $this->_addCDATA($orderTaxAmountElement, $this->_amountAsInt($order->getTaxAmount()));
        
        //terms url
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)->getStore();
        $termsURLElement = $orderLinesElement->addChild('termsURL');
        $this->_addCDATA($termsURLElement, $store->getBaseUrl());
        
        foreach ($invoicedItems['invoicedItems'] as $lineitem) {
            $rowtotal = $lineitem['row_total'];
            $unitTaxAmount = $this->truncateNumber($lineitem['tax_amount'] / $lineitem['qty_ordered']);
            $totaltax = ($unitTaxAmount * $lineitem['qty_invoiced']) + $lineitem['weee_tax_applied_row_amount'];
            $unitPrice = $rowtotal / $lineitem['qty_ordered'];
            $unitDiscountAmount = $this->truncateNumber($lineitem['discount_amount'] / $lineitem['qty_ordered']);
            $totalamount = ($unitPrice * $lineitem['qty_invoiced']) - ($unitDiscountAmount * $lineitem['qty_invoiced']);
            
            $this->_addKlarnaLineItemElement(
                $orderLinesElement,
                $lineitem['product_id'],                            //reference
                $lineitem['name'],                                  //name
                $lineitem['product_type'],                          //productType
                $lineitem['qty_invoiced'],                          //quantity
                'product',                                          //quantityUnit
                $unitPrice,                                         //unitPrice
                $totaltax,                                          //taxRate
                $totalamount + $totaltax,                           //totalAmount
                $totaltax,                                          //totalTaxAmount
                $unitDiscountAmount * $lineitem['qty_invoiced']     //totalDiscountAmount
            );
        }
    }

    /**
     * Calculates the integral part of a specified double-precision floating-point number.
     *
     * @param int|float $number
     * @return int|float
     */
    private function truncateNumber($number)
    {
        return (floor($number * pow(10, 2)))/100;
    }

    /**
     * Adding Klarna line items tag to xml
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
    private function _addKlarnaLineItemElement(
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
     * Add amount and its child tag to xml
     *
     * @param SimpleXMLElement $capture
     * @param string $currencyCode
     * @param string|array|float $exponent
     * @param float $amount
     */
    private function _addAmountElement($capture, $currencyCode, $exponent, $amount)
    {
        $amountElement = $capture->addChild('amount');
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = $this->exponent;
        $amountElement['value'] = $this->_amountAsInt($amount);
    }
    
     /**
      * Add amount and its child tag to xml
      *
      * @param SimpleXMLElement $discountAmountXml
      * @param string $currencyCode
      * @param string|array|float $exponent
      * @param float $discountAmount
      */
    private function _addAmountElementCapture($discountAmountXml, $currencyCode, $exponent, $discountAmount)
    {
        $amountElement = $discountAmountXml->addChild('amount');
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = $this->exponent;
        $amountElement['value'] = $this->_amountAsInt($discountAmount);
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
}
