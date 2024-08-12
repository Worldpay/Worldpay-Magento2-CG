<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\System\Config\Backend;

class KlarnaAllowedCountries implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'AT', 'label' => __('Austria')],
            ['value' => 'BE', 'label' => __('Belgium')],
            ['value' => 'DK', 'label' => __('Denmark')],
            ['value' => 'FI', 'label' => __('Finland')],
            ['value' => 'FR', 'label' => __('France')],
            ['value' => 'DE', 'label' => __('Germany')],
            ['value' => 'IE', 'label' => __('Ireland')],
            ['value' => 'IT', 'label' => __('Italy')],
            ['value' => 'NL', 'label' => __('Netherlands')],
            ['value' => 'NO', 'label' => __('Norway')],
            ['value' => 'PL', 'label' => __('Poland')],
            ['value' => 'PT', 'label' => __('Portugal')],
            ['value' => 'ES', 'label' => __('Spain')],
            ['value' => 'SE', 'label' => __('Sweden')],
            ['value' => 'GB', 'label' => __('United Kingdom')],
            ['value' => 'CZ', 'label' => __('Czech Republic')],
            ['value' => 'RO', 'label' => __('Romania')],
            ['value' => 'GR', 'label' => __('Greece')],
            ['value' => 'CH', 'label' => __('Switzerland')]
        ];
    }
}
