<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

/**
 * Build xml for Refund request
 */
class Refund
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
     * @var string
     */
    private $refundReference;
    /**
     * @var mixed
     */
    private $exponent;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
      protected $scopeConfig;

    /**
     * Refund constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }
    
    /**
     * Build xml for processing Request
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @param string $currencyCode
     * @param float $amount
     * @param string $refundReference
     * @param mixed $exponent
     * @param Order $order
     * @param string|null $paymentType
     * @return SimpleXMLElement $xml
     */
    public function build(
        $merchantCode,
        $orderCode,
        $currencyCode,
        $amount,
        $refundReference,
        $exponent,
        $order,
        $paymentType = null
    ) {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->refundReference = $refundReference;
        $this->exponent = $exponent;

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $modify = $this->_addModifyElement($xml);
        $orderModification = $this->_addOrderModificationElement($modify);
        $this->_addRefundElement($orderModification, $order, $paymentType);
        
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
     * Add tag refund to xml
     *
     * @param SimpleXMLElement $orderModification
     * @param Order $order
     * @param string $paymentType
     */
    private function _addRefundElement($orderModification, $order, $paymentType)
    {
        $refund = $orderModification->addChild('refund');
        $refund["reference"] = $this->refundReference ;

        $amountElement = $refund->addChild('amount');
        $amountElement['value'] = $this->_amountAsInt($this->amount);
        $amountElement['currencyCode'] = $this->currencyCode;
        $amountElement['exponent'] = $this->exponent;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        
        $level23DataEnabled = $this->scopeConfig
                        ->getValue('worldpay/level23_config/level23', $storeScope);
        
         //Level23 data changes
        $countryCode = $order->getBillingAddress()->getCountryId();

        if ($level23DataEnabled
           && ($paymentType === 'ECMC-SSL' || $paymentType === 'VISA-SSL')
           && ($countryCode === 'US' || $countryCode === 'CA')) {
            
             $this->_addBranchSpecificExtension($order, $refund);
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
        return round((float)$amount, $this->exponent, PHP_ROUND_HALF_EVEN) * pow(10, $this->exponent);
    }
    
     /**
      * Add branchSpecificExtension and its child tag to xml
      *
      * @param SimpleXMLElement $order
      * @param array $refund
      */
    private function _addBranchSpecificExtension($order, $refund)
    {
        $branchSpecificExtension = $refund->addChild('branchSpecificExtension');
        $purchase = $branchSpecificExtension->addChild('purchase');
        if ($order->getCustomerIsGuest()) {
            $purchase->addChild('customerReference', 'guest');
        } else {
            $purchase->addChild('customerReference', $order->getCustomerId());
        }
         
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        
        $cardAcceptorTaxId = $this->scopeConfig
                        ->getValue('worldpay/level23_config/CardAcceptorTaxId', $storeScope);
        
        $dutyAmount = $this->scopeConfig
                        ->getValue('worldpay/level23_config/duty_amount', $storeScope);
        
        $purchase->addChild('cardAcceptorTaxId', $cardAcceptorTaxId);
        
        $salesTax = $purchase->addChild('salesTax');
          
         $taxAmount = $order->getTaxAmount();
         $taxAmountNew = abs($taxAmount);
         
         $taxApplied = 'false';
        if ($taxAmountNew != 0) {
            $taxApplied = 'true';
        }
         $this->_addAmountElementCapture($salesTax, $this->currencyCode, $this->exponent, $taxAmountNew);
         
         $orderDiscountAmount = abs($order->getDiscountAmount());
         
        if ($orderDiscountAmount) {
            $discountAmountXml = $purchase->addChild('discountAmount');
            $this->_addAmountElementCapture(
                $discountAmountXml,
                $this->currencyCode,
                $this->exponent,
                $order->getDiscountAmount()
            );
        }
        
        $shippingAmnt = (float)$order->getShippingAmount();
        
        if ($shippingAmnt) {
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
    
        $this->_addL23OrderLineItemElement($order, $purchase, $refund);
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
        
        if ($description) {
            $descriptionElement = $item->addChild('description');
            $this->_addCDATA($descriptionElement, substr($description, 0, 12));
        }
        
        if ($productCode) {
            $productCodeElement = $item->addChild('productCode');
            $this->_addCDATA($productCodeElement, substr($productCode, 0, 12));
        }
        
        if ($commodityCode) {
            $commodityCodeElement = $item->addChild('commodityCode');
            $this->_addCDATA($commodityCodeElement, $commodityCode);
        }
        
        $quantityElement = $item->addChild('quantity');
        
        $quantityNew = abs($quantity);
         
        $this->_addCDATA($quantityElement, $quantityNew);

        $unitCostElement = $item->addChild('unitCost');
        $this->_addAmountElement($unitCostElement, $this->currencyCode, $this->exponent, $unitCost);
        
        if ($unitOfMeasure) {
            $unitOfMeasureElement = $item->addChild('unitOfMeasure');
            $this->_addCDATA($unitOfMeasureElement, $unitOfMeasure);
        }
        
        $itemTotalElement = $item->addChild('itemTotal');
        $this->_addAmountElement($itemTotalElement, $this->currencyCode, $this->exponent, $itemTotal);

        $itemTotalWithTaxElement = $item->addChild('itemTotalWithTax');
        $this->_addAmountElement($itemTotalWithTaxElement, $this->currencyCode, $this->exponent, $itemTotalWithTax);

        $itemDiscountAmountElement = $item->addChild('itemDiscountAmount');
        $this->_addAmountElement($itemDiscountAmountElement, $this->currencyCode, $this->exponent, $itemDiscountAmount);

        $taxAmountElement = $item->addChild('taxAmount');
        $this->_addAmountElement($taxAmountElement, $this->currencyCode, $this->exponent, $taxAmount);
    }
    
    /**
     * Add amount and its child tag to xml
     *
     * @param SimpleXMLElement $capture
     * @param string $currencyCode
     * @param mixed $exponent
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
      * @param mixed $exponent
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
