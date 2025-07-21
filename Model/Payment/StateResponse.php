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
     * @var string
     */
    public $merchantCode;
    /**
     * @var \SimpleXMLElement
     */
    public $_xml;

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
    /** not in use */
    /*public static function createFrom3DError($orderCode, $merchantCode, $paymentStatus)
    {
        return new self(
            $orderCode,
            $merchantCode,
            $paymentStatus,
            null
        );
    }*/

    /**
     * Get getOrderCode
     *
     * @return string
     */
    public function getOrderCode()
    {
        return $this->orderCode;
    }

    /**
     * Get getPaymentStatus
     *
     * @return string
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * Get getAmount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Get getMerchantCode
     *
     * @return string
     */
    public function getMerchantCode()
    {
        return $this->merchantCode;
    }

    /**
     * Get getRiskScore
     *
     * @return null
     */
    public function getRiskScore()
    {
        return null;
    }

    /**
     * Get getAdvancedRiskProvider
     *
     * @return null
     */
    public function getAdvancedRiskProvider()
    {
        return null;
    }

    /**
     * Get getAdvancedRiskProviderId
     *
     * @return null
     */
    public function getAdvancedRiskProviderId()
    {
        return null;
    }

    /**
     * Get getAdvancedRiskProviderThreshold
     *
     * @return null
     */
    public function getAdvancedRiskProviderThreshold()
    {
        return null;
    }

    /**
     * Get getAdvancedRiskProviderScore
     *
     * @return null
     */
    public function getAdvancedRiskProviderScore()
    {
        return null;
    }

    /**
     * Get getAdvancedRiskProviderFinalScore
     *
     * @return null
     */
    public function getAdvancedRiskProviderFinalScore()
    {
        return null;
    }

    /**
     * Get getPaymentMethod
     *
     * @return null
     */
    public function getPaymentMethod()
    {
        return null;
    }

    /**
     * Get getCardNumber
     *
     * @return null
     */
    public function getCardNumber()
    {
        return null;
    }

    /**
     * Get getAvsResultCode
     *
     * @return null
     */
    public function getAvsResultCode()
    {
        return null;
    }

    /**
     * Get getCvcResultCode
     *
     * @return null
     */
    public function getCvcResultCode()
    {
        return null;
    }

    /**
     * Get getPaymentRefusalCode
     *
     * @return null
     */
    public function getPaymentRefusalCode()
    {
        return null;
    }

    /**
     * Get getPaymentRefusalDescription
     *
     * @return null
     */
    public function getPaymentRefusalDescription()
    {
        return null;
    }

    /**
     * Get getJournalReference
     *
     * @param string $state
     * @return null
     */
    public function getJournalReference($state)
    {
        return null;
    }

    /**
     * Get getFullRefundAmount
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
     * Get getAAVAddressResultCode
     *
     * @return null
     */
    public function getAAVAddressResultCode()
    {
        return null;
    }

    /**
     * Get getAAVPostcodeResultCode
     *
     * @return null
     */
    public function getAAVPostcodeResultCode()
    {
        return null;
    }

    /**
     * Get getAAVCardholderNameResultCode
     *
     * @return null
     */
    public function getAAVCardholderNameResultCode()
    {
        return null;
    }

    /**
     * Get getAAVTelephoneResultCode
     *
     * @return null
     */
    public function getAAVTelephoneResultCode()
    {
        return null;
    }
    
    /**
     * Get getAAVEmailResultCode
     *
     * @return null
     */
    public function getAAVEmailResultCode()
    {
        return null;
    }

    /**
     * Get getCurrency
     *
     * @return null
     */
    public function getCurrency()
    {
        return null;
    }
    /**
     * Get getNetworkUsed
     *
     * @return null
     */
    public function getNetworkUsed()
    {
        return null;
    }

    /**
     * Get getSourceType
     *
     * @return null
     */
    public function getSourceType()
    {
        return null;
    }
    /**
     * Get getAvailableBalance
     *
     * @return null
     */
    public function getAvailableBalance()
    {
        return null;
    }
    /**
     * Get getPrepaidCardType
     *
     * @return null
     */
    public function getPrepaidCardType()
    {
        return null;
    }
    /**
     * Get getReloadable
     *
     * @return null
     */
    public function getReloadable()
    {
        return null;
    }
    /**
     * Get getCardProductType
     *
     * @return null
     */
    public function getCardProductType()
    {
        return null;
    }
    /**
     * Get getAffluence
     *
     * @return null
     */
    public function getAffluence()
    {
        return null;
    }
    /**
     * Get getAccountRangeId
     *
     * @return null
     */
    public function getAccountRangeId()
    {
        return null;
    }
    /**
     * Get getIssuerCountry
     *
     * @return null
     */
    public function getIssuerCountry()
    {
        return null;
    }
    /**
     * Get getVirtualAccountNumber
     *
     * @return null
     */
    public function getVirtualAccountNumber()
    {
        return null;
    }
    /**
     * Get getFraudsightMessage
     *
     * @return null
     */
    public function getFraudsightMessage()
    {
        return null;
    }
    /**
     * Get getFraudsightScore
     *
     * @return null
     */
    public function getFraudsightScore()
    {
        return null;
    }
    /**
     * Get getFraudsightReasonCode
     *
     * @return null
     */
    public function getFraudsightReasonCode()
    {
        return null;
    }
}
