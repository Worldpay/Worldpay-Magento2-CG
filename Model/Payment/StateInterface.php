<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

/**
 * Describe what can be read from WP's xml response
 */

interface StateInterface
{
    public const STATUS_SENT_FOR_AUTHORISATION = 'SENT_FOR_AUTHORISATION';
    public const STATUS_AUTHORISED = 'AUTHORISED';
    public const STATUS_CAPTURED = 'CAPTURED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';
    public const STATUS_SENT_FOR_REFUND = 'SENT_FOR_REFUND';
    public const STATUS_REFUNDED = 'REFUNDED';
    public const STATUS_REFUND_WEBFORM_ISSUED = 'REFUND_WEBFORM_ISSUED';
    public const STATUS_REFUND_EXPIRED = 'REFUND_EXPIRED';
    public const STATUS_REFUND_FAILED = 'REFUND_FAILED';
    public const STATUS_REFUSED = 'REFUSED';
    public const STATUS_ERROR = 'ERROR';
    public const STATUS_SETTLED = 'SETTLED';
    public const STATUS_SETTLED_BY_MERCHANT = 'SETTLED_BY_MERCHANT';
    public const STATUS_CHARGED_BACK = 'CHARGED_BACK';
    public const STATUS_CHARGEBACK_REVERSED = 'CHARGEBACK_REVERSED';
    public const STATUS_INFORMATION_SUPPLIED = 'INFORMATION_SUPPLIED';
    public const STATUS_INFORMATION_REQUESTED = 'INFORMATION_REQUESTED';
    public const STATUS_REFUNDED_BY_MERCHANT = 'REFUNDED_BY_MERCHANT';
    public const STATUS_VOIDED = 'VOIDED';
    
    /**
     * GetPaymentStatus
     */
    public function getPaymentStatus();
    /**
     * GetOrderCode
     */
    public function getOrderCode();
    /**
     * GetAmount
     */
    public function getAmount();
    /**
     * GetMerchantCode
     */
    public function getMerchantCode();
    /**
     * GetRiskScore
     */
    public function getRiskScore();
    /**
     * GetPaymentMethod
     */
    public function getPaymentMethod();
    /**
     * GetCardNumber
     */
    public function getCardNumber();
    /**
     * GetAvsResultCode
     */
    public function getAvsResultCode();
    /**
     * GetCvcResultCode
     */
    public function getCvcResultCode();
    /**
     * GetAdvancedRiskProvider
     */
    public function getAdvancedRiskProvider();
    /**
     * GetAdvancedRiskProviderId
     */
    public function getAdvancedRiskProviderId();
    /**
     * GetAdvancedRiskProviderThreshold
     */
    public function getAdvancedRiskProviderThreshold();
    /**
     * GetAdvancedRiskProviderScore
     */
    public function getAdvancedRiskProviderScore();
    /**
     * GetAdvancedRiskProviderFinalScore
     */
    public function getAdvancedRiskProviderFinalScore();
    /**
     * GetPaymentRefusalCode
     */
    public function getPaymentRefusalCode();
    /**
     * GetPaymentRefusalDescription
     */
    public function getPaymentRefusalDescription();
    /**
     * GetJournalReference
     *
     * @param string $state
     */
    public function getJournalReference($state);
    /**
     * GetFullRefundAmount
     */
    public function getFullRefundAmount();
    /**
     * GsAsyncNotification
     */
    public function isAsyncNotification();
    /**
     * IsDirectReply
     */
    public function isDirectReply();
    /**
     * GetAAVAddressResultCode
     */
    public function getAAVAddressResultCode();
    /**
     * GetAAVPostcodeResultCode
     */
    public function getAAVPostcodeResultCode();
    /**
     * GetAAVCardholderNameResultCode
     */
    public function getAAVCardholderNameResultCode();
    /**
     * GetAAVTelephoneResultCode
     */
    public function getAAVTelephoneResultCode();
    /**
     * GetAAVEmailResultCode
     */
    public function getAAVEmailResultCode();
    /**
     * GetCurrency
     */
    public function getCurrency();
    /**
     * GetNetworkUsed
     */
    public function getNetworkUsed();
    /**
     * GetSourceType
     */
    public function getSourceType();
    /**
     * GetAvailableBalance
     */
    public function getAvailableBalance();
    /**
     * GetPrepaidCardType
     */
    public function getPrepaidCardType();
    /**
     * GetReloadable
     */
    public function getReloadable();
    /**
     * GetCardProductType
     */
    public function getCardProductType();
    /**
     * GetAffluence
     */
    public function getAffluence();
    /**
     * GetAccountRangeId
     */
    public function getAccountRangeId();
    /**
     * GetIssuerCountry
     */
    public function getIssuerCountry();
    /**
     * GetVirtualAccountNumber
     */
    public function getVirtualAccountNumber();
    /**
     * GetFraudsightMessage
     */
    public function getFraudsightMessage();
    /**
     * GetFraudsightScore
     */
    public function getFraudsightScore();
    /**
     * GetFraudsightReasonCode
     */
    public function getFraudsightReasonCode();
}
