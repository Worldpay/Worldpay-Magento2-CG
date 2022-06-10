<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

/**
 * Reading xml
 */
class StateResponse implements \Sapient\Worldpay\Model\Payment\StateInterface
{
    /**
     * @var orderCode
     */
    public $orderCode;
    /**
     * @var paymentStatus
     */
    public $paymentStatus;
    /**
     * @var amount
     */
    public $amount;

    /**
     * Constructor
     *
     * @param string $orderCode
     * @param string $merchantCode
     * @param string $paymentStatus
     * @param float $amount
     */
    public function __construct($orderCode, $merchantCode, $paymentStatus, $amount)
    {
        $this->orderCode = $orderCode;
        $this->merchantCode = $merchantCode;
        $this->paymentStatus = $paymentStatus;
        $this->amount = $amount;
    }
    /**
     * Create From Cancelled Response
     *
     * @param string $params
     * @return string
     */

    public function createFromCancelledResponse($params)
    {
        $orderkey = $params['orderKey'];
        // extract order code
        $extractOrderCode = explode('^', $orderkey);
        $orderCode = end($extractOrderCode);
        // extract merchantcode
        $extractMerchantCode = explode('^', $orderkey);
        $merchantCode = $extractMerchantCode[1];
        return new self(
            $orderCode,
            $merchantCode,
            \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_CANCELLED,
            null
        );
    }

    /**
     * Create from Pending Response
     *
     * @param string $params
     * @param int|bool|null $paymentType
     * @return string
     */
    public function createFromPendingResponse($params, $paymentType = null)
    {
        $orderkey = $params['orderKey'];
        // extract order code
        $extractOrderCode = explode('^', $orderkey);
        $orderCode = end($extractOrderCode);
        // extract merchantcode
        $extractMerchantCode = explode('^', $orderkey);
        $merchantCode = $extractMerchantCode[1];
        if (!empty($paymentType) && $paymentType == "KLARNA-SSL") {
            return new self(
                $orderCode,
                $merchantCode,
                \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SENT_FOR_AUTHORISATION,
                null
            );
        } else {
            return new self(
                $orderCode,
                $merchantCode,
                \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_PENDING_PAYMENT,
                null
            );
        }
    }
    
    /* Not Used in this app
    public static function createFrom3DError($orderCode, $merchantCode, $paymentStatus)
    {
        return new self(
            $orderCode,
            $merchantCode,
            $paymentStatus,
            null
        );
    } */

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
     * @param string $state
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
