<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\PaymentMethods;

/**
 * WorldPay CreditCards class extended from WorldPay Abstract Payment Method.
 */
class AlternativePaymentMethods extends \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'worldpay_apm';
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = true;
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = false;
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Authorize payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_wplogger->info('WorldPay Alternative Payment Method Executed:');
        parent::authorize($payment, $amount);
        return $this;
    }

    /**
     * Authorisation service abstract method
     *
     * @param int $storeId
     * @return bool
     */
    public function getAuthorisationService($storeId)
    {
        $apmmethods = $this->paymentdetailsdata['additional_data']['cc_type'];
        $isPaypalSmartButton = $apmmethods === "PAYPAL-SSL" && isset($this->paymentdetailsdata['additional_data']['paypal_smart']);
        if ($apmmethods === "ACH_DIRECT_DEBIT-SSL" || $apmmethods === "SEPA_DIRECT_DEBIT-SSL" || $isPaypalSmartButton) {
            return $this->directservice;
        }
        return $this->redirectservice;
    }

    /**
     * Check if apm is enabled
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        /* Start Multishipping code */
        if ($this->worlpayhelper->isMultiShipping()) {
            if ($this->worlpayhelper->isMultiShippingEnabledInApm()) {
                return true;
            }
            return false;
        }
        /* End Multishipping code */
        if ($this->worlpayhelper->isWorldPayEnable() && $this->worlpayhelper->isApmEnabled()
                && !$this->worlpayhelper->getsubscriptionStatus()) {
            return true;
        }
        return false;
    }

    /**
     * Get the apm title
     *
     * @return string
     */
    public function getTitle()
    {
        if ($order = $this->registry->registry('current_order')) {
            return $this->worlpayhelper->getPaymentTitleForOrders($order, $this->_code, $this->worldpaypayment);
        } elseif ($invoice = $this->registry->registry('current_invoice')) {
            $order = $this->worlpayhelper->getOrderByOrderId($invoice->getOrderId());
            return $this->worlpayhelper->getPaymentTitleForOrders($order, $this->_code, $this->worldpaypayment);
        } elseif ($creditMemo = $this->registry->registry('current_creditmemo')) {
            $order = $this->worlpayhelper->getOrderByOrderId($creditMemo->getOrderId());
            return $this->worlpayhelper->getPaymentTitleForOrders($order, $this->_code, $this->worldpaypayment);
        } else {
            return $this->worlpayhelper->getApmTitle();
        }
    }
}
