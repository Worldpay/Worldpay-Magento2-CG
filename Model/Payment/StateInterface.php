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
    /**
     * @var STATUS_SENT_FOR_AUTHORISATION
     */
    public const STATUS_SENT_FOR_AUTHORISATION = 'SENT_FOR_AUTHORISATION';

    /**
     * @var STATUS_AUTHORISED
     */
    public const STATUS_AUTHORISED = 'AUTHORISED';

    /**
     * @var STATUS_CAPTURED
     */
    public const STATUS_CAPTURED = 'CAPTURED';
    /**
     * @var STATUS_CANCELLED
     */
    public const STATUS_CANCELLED = 'CANCELLED';
    /**
     * @var STATUS_PENDING_PAYMENT
     */
    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';
    /**
     * @var STATUS_SENT_FOR_REFUND
     */
    public const STATUS_SENT_FOR_REFUND = 'SENT_FOR_REFUND';
    /**
     * @var STATUS_REFUNDED
     */
    public const STATUS_REFUNDED = 'REFUNDED';
    /**
     * @var STATUS_REFUND_WEBFORM_ISSUED
     */
    public const STATUS_REFUND_WEBFORM_ISSUED = 'REFUND_WEBFORM_ISSUED';
    /**
     * @var STATUS_REFUND_EXPIRED
     */
    public const STATUS_REFUND_EXPIRED = 'REFUND_EXPIRED';
    /**
     * @var STATUS_REFUND_FAILED
     */
    public const STATUS_REFUND_FAILED = 'REFUND_FAILED';
    /**
     * @var STATUS_REFUSED
     */
    public const STATUS_REFUSED = 'REFUSED';
    /**
     * @var STATUS_ERROR
     */
    public const STATUS_ERROR = 'ERROR';
    /**
     * @var STATUS_SETTLED
     */
    public const STATUS_SETTLED = 'SETTLED';
    /**
     * @var STATUS_SETTLED_BY_MERCHANT
     */
    public const STATUS_SETTLED_BY_MERCHANT = 'SETTLED_BY_MERCHANT';
    /**
     * @var STATUS_CHARGED_BACK
     */
    public const STATUS_CHARGED_BACK = 'CHARGED_BACK';
    /**
     * @var STATUS_CHARGEBACK_REVERSED
     */
    public const STATUS_CHARGEBACK_REVERSED = 'CHARGEBACK_REVERSED';
    /**
     * @var STATUS_INFORMATION_SUPPLIED
     */
    public const STATUS_INFORMATION_SUPPLIED = 'INFORMATION_SUPPLIED';
    /**
     * @var STATUS_INFORMATION_REQUESTED
     */
    public const STATUS_INFORMATION_REQUESTED = 'INFORMATION_REQUESTED';

    /**
     * @var STATUS_REFUNDED_BY_MERCHANT
     */
    public const STATUS_REFUNDED_BY_MERCHANT = 'REFUNDED_BY_MERCHANT';
    /**
     * @var STATUS_VOIDED
     */
    public const STATUS_VOIDED = 'VOIDED';

    /**
     * Get getPaymentStatus
     */

    public function getPaymentStatus();
    /**
     * Get getOrderCode
     */

    public function getOrderCode();
    /**
     * Get getAmount
     *
     * @return string
     */

    public function getAmount();
    /**
     * Get getMerchantCode
     */

    public function getMerchantCode();
    /**
     * Get getRiskScore
     */

    public function getRiskScore();
    /**
     * Get getPaymentMethod
     */

    public function getPaymentMethod();
    /**
     * Get getCardNumber
     */

    public function getCardNumber();
    /**
     * Get getAvsResultCode
     */

    public function getAvsResultCode();
    /**
     * Get getCvcResultCode
     */

    public function getCvcResultCode();
    /**
     * Get getAdvancedRiskProvider
     */

    public function getAdvancedRiskProvider();
    /**
     * Get getAdvancedRiskProviderId
     */

    public function getAdvancedRiskProviderId();
    /**
     * GetgetAdvancedRiskProviderThreshold
     */

    public function getAdvancedRiskProviderThreshold();
    /**
     * Get getAdvancedRiskProviderScore
     */

    public function getAdvancedRiskProviderScore();
    /**
     * Get getAdvancedRiskProviderFinalScore
     */

    public function getAdvancedRiskProviderFinalScore();
    /**
     * Get getPaymentRefusalCode
     */

    public function getPaymentRefusalCode();
    /**
     * Get getPaymentRefusalDescription
     */

    public function getPaymentRefusalDescription();
    /**
     * Get getJournalReference
     *
     * @param string $state
     */

    public function getJournalReference($state);
    /**
     * Get getFullRefundAmount
     */

    public function getRefundAuthorisationJournalReference($state);

    public function getFullRefundAmount();
    /**
     * Get isAsyncNotification
     */

    public function isAsyncNotification();
    /**
     * Get isDirectReply
     */

    public function isDirectReply();
    /**
     * Get getAAVAddressResultCode
     */

    public function getAAVAddressResultCode();
    /**
     * Get getAAVPostcodeResultCode
     */

    public function getAAVPostcodeResultCode();
    /**
     * Get getAAVCardholderNameResultCode
     */

    public function getAAVCardholderNameResultCode();
    /**
     * Get getAAVTelephoneResultCode
     */

    public function getAAVTelephoneResultCode();
    /**
     * Get getAAVEmailResultCode
     */

    public function getAAVEmailResultCode();
    /**
     * Get getCurrency
     */

    public function getCurrency();
    /**
     * Get getNetworkUsed
     */

    public function getNetworkUsed();
    /**
     * Get getSourceType
     */

    public function getSourceType();
    /**
     * Get getAvailableBalance
     */

    public function getAvailableBalance();
    /**
     * Get getPrepaidCardType
     */

    public function getPrepaidCardType();
    /**
     * Get getReloadable
     */

    public function getReloadable();
    /**
     * Get getCardProductType
     */

    public function getCardProductType();
    /**
     * Get getAffluence
     */

    public function getAffluence();
    /**
     * Get getAccountRangeId
     */

    public function getAccountRangeId();
    /**
     * Get getIssuerCountry
     */

    public function getIssuerCountry();
    /**
     * Get getVirtualAccountNumber
     */

    public function getVirtualAccountNumber();
    /**
     * Get getFraudsightMessage
     */

    public function getFraudsightMessage();
    /**
     * Get getFraudsightScore
     */

    public function getFraudsightScore();
    /**
     * Get getFraudsightReasonCode
     */

    public function getFraudsightReasonCode();

    public function getJournalReferenceDescription($getPaymentStatus);
}
