<?php

namespace Sapient\Worldpay\Model\Config\Source;

/**
 * Description of DebitNetworks
 *
 * @author aatrai
 */
class DebitNetworks extends \Magento\Framework\App\Config\Value
{
     /**
      * Configurations for debit networks
      *
      * @return array
      */
    public function toOptionArray()
    {

        return [
            ['value' => 'Accel', 'label' => __('Accel')],
            ['value' => 'AFFN', 'label' => __('AFFN')],
            ['value' => 'CU24', 'label' => __('CU24')],
            ['value' => 'Jeanie', 'label' => __('Jeanie')],
            ['value' => 'NYCE', 'label' => __('NYCE')],
            ['value' => 'Pulse', 'label' => __('Pulse')],
            ['value' => 'Shazam', 'label' => __('Shazam')],
            ['value' => 'Star SouthEast', 'label' => __('Star SouthEast')],
            ['value' => 'Star West', 'label' => __('Star West')],
            ['value' => 'Star NorthEast', 'label' => __('Star NorthEast')]
        ];
    }
}
