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
     *
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

    public static function createFromPendingResponse($params, $paymentType = null)
    {
        $orderkey = $params['orderKey'];
        $orderCode = \Sapient\Worldpay\Model\Payment\StateResponse::_extractOrderCode($orderkey);
        $merchantCode = \Sapient\Worldpay\Model\Payment\StateResponse::_extractMerchantCode($orderkey);
        if (!empty($paymentType) && $paymentType == "KLARNA-SSL") {
            return new self(
                $orderCode,
                $merchantCode,
                \Sapient\Worldpay\Model\Payment\State::STATUS_SENT_FOR_AUTHORISATION,
                null
            );
        } else {
            return new self(
                $orderCode,
                $merchantCode,
                \Sapient\Worldpay\Model\Payment\State::STATUS_PENDING_PAYMENT,
                null
            );
        }
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
     * Getter
     *
     * @return string
     */
    public function getOrderCode()
    {
        return $this->orderCode;
    }

    /**
     * Getter
     *
     * @return string
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * Getter
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Getter
     *
     * @return string
     */
    public function getMerchantCode()
    {
        return $this->merchantCode;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getRiskScore()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getAdvancedRiskProvider()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getAdvancedRiskProviderId()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getAdvancedRiskProviderThreshold()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getAdvancedRiskProviderScore()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getAdvancedRiskProviderFinalScore()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getPaymentMethod()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getCardNumber()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getAvsResultCode()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getCvcResultCode()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return string
     */
    private static function _extractOrderCode($orderKey)
    {
        $array = explode('^', $orderKey);
        return end($array);
    }

    /**
     * Getter
     *
     * @return string
     */
    private static function _extractMerchantCode($orderKey)
    {
        $array = explode('^', $orderKey);
        return $array[1];
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getPaymentRefusalCode()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getPaymentRefusalDescription()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getJournalReference($state)
    {
        return null;
    }

    /**
     * Getter
     *
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
     * Getter
     *
     * @return null
     */
    public function getAAVAddressResultCode()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getAAVPostcodeResultCode()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getAAVCardholderNameResultCode()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getAAVTelephoneResultCode()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getAAVEmailResultCode()
    {
        return null;
    }

    /**
     * Getter
     *
     * @return null
     */
    public function getCurrency()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getNetworkUsed()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getSourceType()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getAvailableBalance()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getPrepaidCardType()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getReloadable()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getCardProductType()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getAffluence()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getAccountRangeId()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getIssuerCountry()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getVirtualAccountNumber()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getFraudsightMessage()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getFraudsightScore()
    {
        return null;
    }
    
    /**
     * Getter
     *
     * @return null
     */
    public function getFraudsightReasonCode()
    {
        return null;
    }
}
