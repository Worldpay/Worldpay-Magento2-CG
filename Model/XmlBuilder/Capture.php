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
    const ROOT_ELEMENT = <<<EOD
<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC '-//WorldPay/DTD WorldPay PaymentService v1//EN'
        'http://dtd.worldpay.com/paymentService_v1.dtd'> <paymentService/>
EOD;

    private $merchantCode;
    private $orderCode;
    private $currencyCode;
    private $amount;
    private $exponent;

    /**
     * Build xml for processing Request
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @param string $currencyCode
     * @param float $amount
     * @return SimpleXMLElement $xml
     */
    public function build($merchantCode, $orderCode, $currencyCode, $amount, $exponent, $paymentType = null, $order, $captureType)
    {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->exponent = $exponent;
        $this->order = $order;
        $this->captureType = $captureType;

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
        
   
        if (!empty($paymentType) && $paymentType == "KLARNA-SSL") {
            $this->_addShippingElement($capture);
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
     * @param float $amount
     * @return int
     */
    private function _amountAsInt($amount)
    {
        return round($amount, $this->exponent, PHP_ROUND_HALF_EVEN) * pow(10, $this->exponent);
    }

    /**
     * Add tag Shipping to xml
     *
     * @param SimpleXMLElement $capture
     *
     * Descrition : Adding additional shipping tag for Klarna
     */
    private function _addShippingElement($capture)
    {
        // data
        $shippingElement = $capture->addChild('shipping');
        $shippingInfoElement = $shippingElement->addChild('shippingInfo');
        $shippingInfoElement['trackingId'] = "";
    }
    
     /**
      * Add branchSpecificExtension and its child tag to xml
      *
      * @param SimpleXMLElement $order
      */
    private function _addBranchSpecificExtension($order, $capture)
    {
        
   
       // $this->paymentDetails['salesTax'] = 2022;
        //$order_details = $this->cusDetails['order_details'];
        $branchSpecificExtension = $capture->addChild('branchSpecificExtension');
        $purchase = $branchSpecificExtension->addChild('purchase');
        //$purchase->addChild('invoiceReferenceNumber',null);
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
        
        if($taxApplied == 'true') {
         $purchase->addChild('cardAcceptorTaxId', $cardAcceptorTaxId);
        }
         
        if ($taxAmountNew) {
            $salesTax = $purchase->addChild('salesTax');
            $this->_addAmountElementCapture($salesTax, $this->currencyCode, $this->exponent, $taxAmountNew);
        }
         
         $orderDiscountAmount = abs($order->getDiscountAmount());
        if (($orderDiscountAmount)) {
            $discountAmountXml = $purchase->addChild('discountAmount');
            $this->_addAmountElementCapture($discountAmountXml, $this->currencyCode, $this->exponent, $order->getDiscountAmount());
        }
        
         $shippingAmnt = (float)$order->getShippingAmount();
         
        if (($shippingAmnt)) {
            $shippingAmountXml = $purchase->addChild('shippingAmount');
            $this->_addAmountElementCapture($shippingAmountXml, $this->currencyCode, $this->exponent, $shippingAmnt);
        }
        

        if ($dutyAmount) {
            $dutyAmountXml = $purchase->addChild('dutyAmount');
            $this->_addAmountElementCapture($dutyAmountXml, $this->currencyCode, $this->exponent, $dutyAmount);
        }
        
        $billingaddress = $order->getBillingAddress();
        $billingpostcode = $billingaddress->getPostcode();
        //$purchase->addChild('shipFromPostalCode', '');
        $purchase->addChild('destinationPostalCode', $billingpostcode);
        
        $countryCode = $order->getShippingAddress()->getCountryId();
                
               
        $purchase->addChild('destinationCountryCode', $countryCode);
        
        $orderDate = $purchase->addChild('orderDate');
        $dateElement = $orderDate->addChild('date');
        $today = new \DateTime();
        $dateElement['dayOfMonth'] = $today->format('d');
        $dateElement['month'] = $today->format('m');
        $dateElement['year'] = $today->format('Y');
        
        $purchase->addChild('taxExempt', $taxApplied);
    
        $this->_addL23OrderLineItemElement($order, $purchase, $capture);
    }
    
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
            $this->_addAmountElement($itemDiscountAmountElement, $this->currencyCode, $this->exponent, $itemDiscountAmount);
        }
        
        if ($taxAmount) {
            $taxAmountElement = $item->addChild('taxAmount');
            $this->_addAmountElement($taxAmountElement, $this->currencyCode, $this->exponent, $taxAmount);
        }
    }
    
    /**
     * Add amount and its child tag to xml
     *
     * @param SimpleXMLElement $order
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
      * @param SimpleXMLElement $order
      */
    private function _addAmountElementCapture($discountAmountXml, $currencyCode, $exponent, $discountAmount)
    {
        $amountElement = $discountAmountXml->addChild('amount');
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = $this->exponent;
        $amountElement['value'] = $this->_amountAsInt($discountAmount);
    }
    
    
     /**
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
