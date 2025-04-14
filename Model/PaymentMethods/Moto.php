<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\PaymentMethods;

/**
 * WorldPay CreditCards class extended from WorldPay Abstract Payment Method.
 */
class Moto extends \Sapient\Worldpay\Model\PaymentMethods\CreditCards
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'worldpay_moto';
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
    protected $_canUseInternal = true;
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = false;

    /**
     * Checkout payment form
     *
     * @var string
     */
    protected $_formBlockType = \Sapient\Worldpay\Block\Form\Card::class;

    /**
     * Get payment method type
     *
     * @return string
     */
    public function getPaymentMethodsType()
    {
        return 'worldpay_cc';
    }

    /**
     * Get the moto title
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
            return $this->worlpayhelper->getMotoTitle();
        }
    }

    /**
     * Authorisation service abstract method
     *
     * @param int $storeId
     * @return bool
     */
    public function getAuthorisationService($storeId)
    {
        $checkoutpaymentdata = $this->paymentdetailsdata;
        if (($checkoutpaymentdata['additional_data']['cc_type'] == 'cc_type')
                && empty($checkoutpaymentdata['additional_data']['tokenCode'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Saved cards not found')
                );
        }
        if (!empty($checkoutpaymentdata['additional_data']['tokenCode'])
             // uncomment to enable moto redirect
             //   && !$this->_isRedirectIntegrationModeEnabled($storeId)
                        ) {
            return $this->tokenservice;
        }
        // uncomment to enable moto redirect
//        if ($this->_isRedirectIntegrationModeEnabled($storeId)) {
//            return $this->motoredirectservice;
//        }
        return $this->directservice;
    }
    /**
     * Check if moto is enabled
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function isAvailable(?\Magento\Quote\Api\Data\CartInterface $quote = null)
    {

        if ($this->worlpayhelper->isWorldPayEnable() && $this->worlpayhelper->isMotoEnabled()) {
            return true;
        }
        return false;
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
}
