<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class AuthMethods extends \Magento\Framework\App\Config\Value
{
    /**
     * Gpay configurations
     *
     * @return array
     */
    public function toOptionArray()
    {

        return [
            ['value' => 'PAN_ONLY', 'label' => __('Pan Only')],
            ['value' => 'CRYPTOGRAM_3DS', 'label' => __('Cryptogram 3ds')]
        ];
    }
}
