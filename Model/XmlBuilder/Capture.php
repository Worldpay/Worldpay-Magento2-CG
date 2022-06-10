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
     * [$exponent description]
     * @var [type]
     */
    private $exponent;
    
    /**
     * [build description]
     *
     * @param  [type] $merchantCode [description]
     * @param  [type] $orderCode    [description]
     * @param  [type] $currencyCode [description]
     * @param  [type] $amount       [description]
     * @param  [type] $exponent     [description]
     * @param  [type] $captureType  [description]
     * @param  [type] $paymentType  [description]
     * @return [type]               [description]
     */
    public function build(
        $merchantCode,
        $orderCode,
        $currencyCode,
        $amount,
        $exponent,
        $captureType,
        $paymentType = null
    ) {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->exponent = $exponent;
        $this->captureType = $captureType;

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $modify = $this->_addModifyElement($xml);
        $orderModification = $this->_addOrderModificationElement($modify);
        $capture = $this->_addCapture($orderModification);
        $this->_addCaptureElement($capture);
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
     * Retrieve amount value
     *
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
}
