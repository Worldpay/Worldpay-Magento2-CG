<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Model\Config\Source;

class KlarnaShippingType extends \Magento\Framework\App\Config\Value
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
            ['value' => 'normal', 'label' => __('Normal')],
            ['value' => 'express', 'label' => __('Express')]
        ];
    }
}
