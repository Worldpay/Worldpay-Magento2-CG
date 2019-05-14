<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class PaymentMethods extends \Magento\Framework\App\Config\Value
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'AMEX', 'label' => __('American Express')],
            ['value' => 'VISA', 'label' => __('Visa')],
            ['value' => 'DISCOVER', 'label' => __('Discover')],
            ['value' => 'JCB', 'label' => __('Japanese Credit Bank')],
            ['value' => 'MASTERCARD', 'label' => __('Master Card')]
        ];
    }
}
