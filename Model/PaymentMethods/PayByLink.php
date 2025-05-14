<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\PaymentMethods;

/**
 * WorldPay PayByLink class extended from WorldPay Abstract Payment Method.
 */
class PayByLink extends \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'worldpay_paybylink';
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
        $this->_wplogger->info('WorldPay Payment Pay By Link Authorise Method Executed:');
        parent::authorize($payment, $amount);
        return $this;
    }

    /**
     * Check if Pay by link is enabled
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function isAvailable(?\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($this->productOnDemandHelper->isProductOnDemandQuote()) {
            return false;
        }

        if ($this->worlpayhelper->isWorldPayEnable() && $this->worlpayhelper->isPayByLinkEnable()) {
            return true;
        }
        return false;
    }
    /**
     * Authorisation service abstract method
     *
     * @param int $storeId
     * @return bool
     */
    public function getAuthorisationService($storeId)
    {
        return $this->paybylinkservice;
    }
}
