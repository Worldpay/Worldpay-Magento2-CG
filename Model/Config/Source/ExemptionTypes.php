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
            ['value' => 'LV', 'label' => __('LV - Low value exemption')],
            ['value' => 'LR', 'label' => __('LR - Low risk exemption')],
            ['value' => 'OP', 'label' => __('OP - Optimised exemption')],
        ];
    }
}
