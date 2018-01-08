<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

/**
 * Reading xml
 */
class StateResponse implements \Sapient\Worldpay\Model\Payment\State
{
    private $orderCode;
    private $paymentStatus;
    private $amount;

    /**
     * Constructor
     * @param string $orderCode
     * @param string $merchantCode
     * @param string $paymentStatus
     * @param float $amount
     */
    private function __construct($orderCode, $merchantCode, $paymentStatus, $amount)
    {
        $this->orderCode = $orderCode;
        $this->merchantCode = $merchantCode;
        $this->paymentStatus = $paymentStatus;
        $this->amount = $amount;
    }
    
    public static function createFromCancelledResponse($params)
    {
        $orderkey = $params['orderKey'];
        $orderCode = \Sapient\Worldpay\Model\Payment\StateResponse::_extractOrderCode($orderkey);
        $merchantCode = \Sapient\Worldpay\Model\Payment\StateResponse::_extractMerchantCode($orderkey);

        return new self(
            $orderCode,
            $merchantCode,
            \Sapient\Worldpay\Model\Payment\State::STATUS_CANCELLED,
            null
        );
    }

    public static function createFromPendingResponse($params)
    {
        $orderkey = $params['orderKey'];
        $orderCode = \Sapient\Worldpay\Model\Payment\StateResponse::_extractOrderCode($orderkey);
        $merchantCode = \Sapient\Worldpay\Model\Payment\StateResponse::_extractMerchantCode($orderkey);

        return new self(
            $orderCode,
            $merchantCode,
            \Sapient\Worldpay\Model\Payment\State::STATUS_PENDING_PAYMENT,
            null
        );
    }

    
    public static function createFrom3DError($orderCode, $merchantCode, $paymentStatus)
    {
        return new self(
            $orderCode,
            $merchantCode,
            $paymentStatus,
            null
        );
    }

    /**
     * @return string 
     */
    public function getOrderCode()
    {
        return $this->orderCode;
    }

    /**
     * @return string 
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * @return float 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string 
     */
    public function getMerchantCode()
    {
        return $this->merchantCode;
    }

    /**
     * @return null 
     */
    public function getRiskScore()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getAdvancedRiskProvider()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getAdvancedRiskProviderId()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getAdvancedRiskProviderThreshold()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getAdvancedRiskProviderScore()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getAdvancedRiskProviderFinalScore()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getPaymentMethod()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getCardNumber()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getAvsResultCode()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getCvcResultCode()
    {
        return null;
    }

    /**
     * @return string 
     */
    private static function _extractOrderCode($orderKey)
    {
        $array = explode('^', $orderKey);
        return end($array);
    }

    /**
     * @return string 
     */
    private static function _extractMerchantCode($orderKey)
    {
        $array = explode('^', $orderKey);
        return $array[1];
    }

    /**
     * @return null 
     */
    public function getPaymentRefusalCode()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getPaymentRefusalDescription()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getJournalReference($state)
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getFullRefundAmount()
    {
        return null;
    }

    /**
     * Tells if this response is an async notification xml sent from WP server
     *
     * @return bool
     */
    public function isAsyncNotification()
    {
        return isset($this->_xml->notify);
    }

    /**
     * Tells if this response is a direct reply xml sent from WP server
     *
     * @return bool
     */
    public function isDirectReply()
    {
        return ! $this->isAsyncNotification();
    }

    /**
     * @return null 
     */
    public function getAAVAddressResultCode()
    {
       return null;
    }

    /**
     * @return null 
     */
    public function getAAVPostcodeResultCode()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getAAVCardholderNameResultCode()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getAAVTelephoneResultCode()
    {
        return null;
    }
    
    /**
     * @return null 
     */
    public function getAAVEmailResultCode()
    {
        return null;
    }

    /**
     * @return null 
     */
    public function getCurrency()
    {
        return null;
    }
}
