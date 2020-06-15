<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class ExemptionTypes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {

        return [
            ['value' => 'LV', 'label' => __('LV')],
            ['value' => 'LR', 'label' => __('LR')],
            ['value' => 'OP', 'label' => __('OP')],
        ];
    }
}