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
    protected $_code = 'worldpay_moto';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = false;

    protected $_formBlockType = 'Sapient\Worldpay\Block\Form\Card';

    public function getPaymentMethodsType()
    {
        return 'worldpay_cc';
    }

    public function getTitle()
    {
        return  $this->_scopeConfig->getValue('worldpay/moto_config/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getAuthorisationService($storeId)
    {
        $checkoutpaymentdata = $this->paymentdetailsdata;
        if (($checkoutpaymentdata['additional_data']['cc_type'] == 'cc_type') && empty($checkoutpaymentdata['additional_data']['tokenCode'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                        __('Saved cards not found')
                );
        }
        if (!empty($checkoutpaymentdata['additional_data']['tokenCode'])) {
            return $this->tokenservice;
        }
        if ($this->_isRedirectIntegrationModeEnabled($storeId)) {
            return $this->motoredirectservice;
        }
        return $this->directservice;
    }

    private function _isRedirectIntegrationModeEnabled($storeId)
    {
        $integrationModel = $this->worlpayhelper->getCcIntegrationMode($storeId);

        return $integrationModel === 'redirect';
    }

}
