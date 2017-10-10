<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

class StateResponse implements \Sapient\Worldpay\Model\Payment\State
{
    private $orderCode;
    private $paymentStatus;
    private $amount;

    private function __construct($orderCode, $merchantCode, $paymentStatus, $amount)
    {
        $this->orderCode = $orderCode;
        $this->merchantCode = $merchantCode;
        $this->paymentStatus = $paymentStatus;
        $this->amount = $amount;
    }
    
    public static function createFromCancelledResponse($params)
    {
        $orderCode = \Sapient\Worldpay\Model\Payment\StateResponse::_extractOrderCode($params['orderKey']);
        $merchantCode = \Sapient\Worldpay\Model\Payment\StateResponse::_extractMerchantCode($params['orderKey']);

        return new self(
            $orderCode,
            $merchantCode,
            \Sapient\Worldpay\Model\Payment\State::STATUS_CANCELLED,
            null
        );
    }

    public static function createFromPendingResponse($params)
    {
        $orderCode = \Sapient\Worldpay\Model\Payment\StateResponse::_extractOrderCode($params['orderKey']);
        $merchantCode = \Sapient\Worldpay\Model\Payment\StateResponse::_extractMerchantCode($params['orderKey']);

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

    public function getOrderCode()
    {
        return $this->orderCode;
    }

    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getMerchantCode()
    {
        return $this->merchantCode;
    }

    public function getRiskScore()
    {
        return null;
    }

    public function getAdvancedRiskProvider()
    {
        return null;
    }

    public function getAdvancedRiskProviderId()
    {
        return null;
    }

    public function getAdvancedRiskProviderThreshold()
    {
        return null;
    }

    public function getAdvancedRiskProviderScore()
    {
        return null;
    }

    public function getAdvancedRiskProviderFinalScore()
    {
        return null;
    }

    public function getPaymentMethod()
    {
        return null;
    }

    public function getCardNumber()
    {
        return null;
    }

    public function getAvsResultCode()
    {
        return null;
    }

    public function getCvcResultCode()
    {
        return null;
    }

    private static function _extractOrderCode($orderKey)
    {
        $array = explode('^', $orderKey);
        return end($array);
    }

    private static function _extractMerchantCode($orderKey)
    {
        $array = explode('^', $orderKey);
        return $array[1];
    }

    public function getPaymentRefusalCode()
    {
        return null;
    }

    public function getPaymentRefusalDescription()
    {
        return null;
    }

    public function getJournalReference($state)
    {
        return null;
    }

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

    public function getAAVAddressResultCode()
    {
       return null;
    }

    public function getAAVPostcodeResultCode()
    {
        return null;
    }

    public function getAAVCardholderNameResultCode()
    {
        return null;
    }

    public function getAAVTelephoneResultCode()
    {
        return null;
    }
    
    public function getAAVEmailResultCode()
    {
        return null;
    }

    public function getCurrency()
    {
        return null;
    }
}
