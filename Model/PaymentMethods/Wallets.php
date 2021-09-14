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
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canUseInternal = false;
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
     * Method to return the authorisation service
     *
     * @param int $storeId
     * @return \Sapient\Worldpay\Model\Authorisation\WalletService
     */
    public function getAuthorisationService($storeId)
    {
        return $this->walletService;
    }

    /**
     * Check if apm is enabled
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($this->worlpayhelper->isWorldPayEnable() && $this->worlpayhelper->isWalletsEnabled()
                && !$this->worlpayhelper->getsubscriptionStatus()) {
            return true;
        }
        return false;
    }

    /**
     * Method to return payment title
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
