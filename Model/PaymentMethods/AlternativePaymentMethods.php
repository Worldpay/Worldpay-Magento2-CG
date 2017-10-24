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
     * @var string
     */
    protected $_code = 'worldpay_apm';
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
        $this->_wplogger->info('WorldPay Alternative Payment Method Executed:');
        parent::authorize($payment, $amount);
        return $this;
    }

    public function getAuthorisationService($storeId)
    {
        return $this->redirectservice;
    }

    /**
     * check if apm is enabled   
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {

       if ($this->worlpayhelper->isWorldPayEnable() && $this->worlpayhelper->isApmEnabled()) {
         return true;
       } 
       return false;
         
    }
}
