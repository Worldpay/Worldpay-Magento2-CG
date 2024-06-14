<?php
/**
 * @copyright 2024 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class PendingOrderCleanupOption extends \Magento\Framework\App\Config\Value
{
    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('24 hours')],
            ['value' => '2', 'label' => __('48 hours')],
        ];
    }
}
