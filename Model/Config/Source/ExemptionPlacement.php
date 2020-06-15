<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class ExemptionPlacement implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {

        return [
            ['value' => 'AUTHORISATION', 'label' => __('AUTHORISATION')],
            ['value' => 'AUTHENTICATION', 'label' => __('AUTHENTICATION')],
            ['value' => 'OPTIMISED', 'label' => __('OPTIMISED')],
        ];
    }
}