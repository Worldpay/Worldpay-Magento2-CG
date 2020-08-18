<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Model\Config\Source;

class InstalmentCountryType extends \Magento\Framework\App\Config\Value
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return
        [
            ['value' => 'Type1', 'label' => __('Type 1 Installment')],
            ['value' => 'Type2', 'label' => __('Type 2 Installment')],
            ['value' => 'Type3', 'label' => __('Type 3 Installment')],
            ['value' => 'Type4', 'label' => __('Type 4 Installment')]
        ];
    }
}
