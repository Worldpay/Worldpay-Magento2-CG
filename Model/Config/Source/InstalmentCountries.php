<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class InstalmentCountries extends \Magento\Framework\App\Config\Value
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'AR', 'label' => __('Argentina')],
            ['value' => 'BZ', 'label' => __('Belize')],
            ['value' => 'BR', 'label' => __('Brazil')],
            ['value' => 'CL', 'label' => __('Chile')],
            ['value' => 'CO', 'label' => __('Colombia')],
            ['value' => 'CR', 'label' => __('Costa Rica')],
            ['value' => 'SV', 'label' => __('El Salvador')],
            ['value' => 'GT', 'label' => __('Guatemala')],
            ['value' => 'HN', 'label' => __('Honduras')],
            ['value' => 'MX', 'label' => __('Mexico')],
            ['value' => 'NI', 'label' => __('Nicaragua')],
            ['value' => 'PA', 'label' => __('Panama')],
            ['value' => 'PE', 'label' => __('Peru')],

        ];
    }
}
