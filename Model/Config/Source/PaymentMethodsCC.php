<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class PaymentMethodsCC extends \Magento\Framework\App\Config\Value
{
    /**
     * @return array
     */
    public function toOptionArray()
    {

        return [
            ['value' => 'AMEX-SSL', 'label' => __('American Express')],
            ['value' => 'VISA-SSL', 'label' => __('Visa')],
            ['value' => 'ECMC-SSL', 'label' => __('MasterCard')],
            ['value' => 'CB-SSL', 'label' => __('Carte Bancaire')],
            ['value' => 'CARTEBLEUE-SSL', 'label' => __('Carte Bleue')],
            ['value' => 'DANKORT-SSL', 'label' => __('Dankort')],
            ['value' => 'DINERS-SSL', 'label' => __('Diners')],
            ['value' => 'DISCOVER-SSL', 'label' => __('Discover')],
            ['value' => 'JCB-SSL', 'label' => __('Japanese Credit Bank')],
            ['value' => 'MAESTRO-SSL', 'label' => __('Maestro')]
        ];
    }

}
