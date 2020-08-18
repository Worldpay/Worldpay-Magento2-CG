<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class EnvironmentMode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {

        return [
            ['value' => 'Test Mode', 'label' => __('Test Mode')],
            ['value' => 'Live Mode', 'label' => __('Live Mode')],
        ];
    }
}
