<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\PaymentMethods;

/**
 * WorldPay CreditCards class extended from WorldPay Abstract Payment Method.
 */
class CreditCards extends \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'worldpay_cc';
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
        $this->_wplogger->info('WorldPay Payment CreditCards Authorise Method Executed:');
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
        $integrationModel = $this->worlpayhelper->getCcIntegrationMode($storeId);

        $checkoutpaymentdata = $this->paymentdetailsdata;
        if ((!empty($checkoutpaymentdata['additional_data']['isSavedCardPayment'])
             && !empty($checkoutpaymentdata['additional_data']['tokenCode'])
             && $integrationModel == 'direct')
                || (!empty($checkoutpaymentdata['additional_data']['isRecurringOrder']))) {
            return $this->tokenservice;
        }
        if ($this->_isRedirectIntegrationModeEnabled($storeId)) {
            if ($this->_isEmbeddedIntegrationModeEnabled($storeId)) {
                return $this->hostedpaymentpageservice;
            }
            return $this->redirectservice;
        }
        return $this->directservice;
    }

    /**
     * Return the integration mode
     *
     * @param int $storeId
     * @return bool
     */
    private function _isRedirectIntegrationModeEnabled($storeId)
    {
        $integrationModel = $this->worlpayhelper->getCcIntegrationMode($storeId);

        return $integrationModel === 'redirect';
    }

    /**
     * Check if cc is enabled
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        /* Start Multishipping code */
        if ($this->worlpayhelper->isMultiShipping()) {
            if ($this->worlpayhelper->isMultiShippingEnabledInCc()) {
                return true;
            }
            return false;
        }
        /* End Multishipping code */
        if ($this->worlpayhelper->isWorldPayEnable() && $this->worlpayhelper->isCreditCardEnabled()) {
            return true;
        }
        return false;
    }

    /**
     * Is embedded integartion mode enabled?
     *
     * @param int $storeId
     * @return bool
     */
    private function _isEmbeddedIntegrationModeEnabled($storeId)
    {
        return $this->worlpayhelper->isIframeIntegration($storeId);
    }

    /**
     * Get the cc title
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
            return $this->worlpayhelper->getCcTitle();
        }
    }
}
