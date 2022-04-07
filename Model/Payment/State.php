<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

/**
 * Describe what can be read from WP's xml response
 */

interface State
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
   
    public function getPaymentStatus();
    public function getOrderCode();
    public function getAmount();
    public function getMerchantCode();
    public function getRiskScore();
    public function getPaymentMethod();
    public function getCardNumber();
    public function getAvsResultCode();
    public function getCvcResultCode();
    public function getAdvancedRiskProvider();
    public function getAdvancedRiskProviderId();
    public function getAdvancedRiskProviderThreshold();
    public function getAdvancedRiskProviderScore();
    public function getAdvancedRiskProviderFinalScore();
    public function getPaymentRefusalCode();
    public function getPaymentRefusalDescription();
    public function getJournalReference($state);
    public function getFullRefundAmount();
    public function isAsyncNotification();
    public function isDirectReply();
    public function getAAVAddressResultCode();
    public function getAAVPostcodeResultCode();
    public function getAAVCardholderNameResultCode();
    public function getAAVTelephoneResultCode();
    public function getAAVEmailResultCode();
    public function getCurrency();
    public function getNetworkUsed();
    public function getSourceType();
    public function getAvailableBalance();
    public function getPrepaidCardType();
    public function getReloadable();
    public function getCardProductType();
    public function getAffluence();
    public function getAccountRangeId();
    public function getIssuerCountry();
    public function getVirtualAccountNumber();
    public function getFraudsightMessage();
    public function getFraudsightScore();
    public function getFraudsightReasonCode();
}
