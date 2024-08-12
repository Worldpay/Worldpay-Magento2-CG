<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Model\Config\Source;

class KlarnaShippingMethod extends \Magento\Framework\App\Config\Value
{
    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return
        [
            ['value' => 'store pick-up', 'label' => __('Store pick-up')],
            ['value' => 'pick-up point', 'label' => __('Pick-up Point')],
            ['value' => 'registered box', 'label' => __('Registered Box')],
            ['value' => 'unregistered box', 'label' => __('Unregistered Box')]
        ];
    }
}
