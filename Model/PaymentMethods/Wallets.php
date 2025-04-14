<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\PaymentMethods;

/**
 * WorldPay CreditCards class extended from WorldPay Abstract Payment Method.
 */
class Wallets extends \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod
{
    /**
     * Payment code
     * @var string
     */
    protected $_code = 'worldpay_wallets';
    /**
     * Enable the gateway
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * Use the authorization
     * @var bool
     */
    protected $_canAuthorize = true;
    /**
     * Disabled internal use
     * @var bool
     */
    protected $_canUseInternal = false;
    /**
     * Disabled checkout use
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
        $this->_wplogger->info('WorldPay Wallets Payment Method Executed:');
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
        return $this->walletService;
    }

    /**
     * Check if apm is enabled
     *
     * @param string|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        /* Start Multishipping code */
        if ($this->worlpayhelper->isMultiShipping()) {
            if ($this->worlpayhelper->isMultiShippingEnabledInWallets()) {
                if ($this->worlpayhelper->isGooglePayEnable() ||
                $this->worlpayhelper->isMsSamsungPayEnable() ||
                $this->worlpayhelper->isMsApplePayEnable()
                ) {
                    return true;
                }
            }
            return false;
        }
        /* End Multishipping code */

        if ($this->worlpayhelper->isWorldPayEnable() && $this->worlpayhelper->isWalletsEnabled()
                && !$this->worlpayhelper->getsubscriptionStatus()) {
            if ($this->worlpayhelper->isGooglePayEnable() ||
                $this->worlpayhelper->isSamsungPayEnable() ||
                $this->worlpayhelper->isApplePayEnable()
            ) {
                   return true;
            }
        }

        if ($this->worlpayhelper->isWorldPayEnable() && $this->worlpayhelper->isCheckoutPaypalSmartButtonEnabled()
            && !$this->worlpayhelper->getsubscriptionStatus())
        {
            return true;
        }

        return false;
    }

    /**
     * Get payment title
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
            return $this->worlpayhelper->getWalletsTitle();
        }
    }
}
