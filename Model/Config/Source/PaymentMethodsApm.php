<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class PaymentMethodsApm extends \Magento\Framework\App\Config\Value
{
    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'CHINAUNIONPAY-SSL', 'label' => __('Union Pay')],
            ['value' => 'IDEAL-SSL', 'label' => __('IDEAL')],
            //['value' => 'YANDEXMONEY-SSL', 'label' => __('Yandex.Money')],
            ['value' => 'PAYPAL-EXPRESS', 'label' => __('PayPal Express')],
            ['value' => 'SOFORT-SSL', 'label' => __('SoFort EU')],
            //['value' => 'BOLETO-SSL', 'label' => __('Boleto Bancairo')],
            ['value' => 'ALIPAY-SSL', 'label' => __('AliPay')],
            ['value' => 'SEPA_DIRECT_DEBIT-SSL', 'label' =>
                __('SEPA (One off transactions)')],['value' => 'KLARNA-SSL', 'label' => __('Klarna (Redirect)')],
            ['value' => 'PRZELEWY-SSL', 'label' => __('P24')],
            ['value' => 'MISTERCASH-SSL', 'label' => __('Mistercash/Bancontact')],
            ['value' => 'ACH_DIRECT_DEBIT-SSL', 'label' => __('ACH Pay')],

        ];
    }
}
