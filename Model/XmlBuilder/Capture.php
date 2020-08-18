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
    public function build($merchantCode, $orderCode, $currencyCode, $amount, $exponent, $paymentType = null)
    {
        $this->merchantCode = $merchantCode;
        $this->orderCode = $orderCode;
        $this->currencyCode = $currencyCode;
        $this->amount = $amount;
        $this->exponent = $exponent;

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
        //check the partial capture is enabled and auto invocie is disabled. if so do the partial capture.
        if ($partialCapture && !$autoInvoice) {
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
       
        //check the partial capture is enabled and auto invocie is disabled. if so do the partial capture.
        if ($partialCapture && !$autoInvoice) {
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
}
