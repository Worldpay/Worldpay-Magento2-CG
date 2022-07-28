<?php
namespace Sapient\Worldpay\Model\Config\Source\ApplePay;

class ButtonColor implements \Magento\Framework\Data\OptionSourceInterface
{
 /**
  * Get button Color options
  */
    public function toOptionArray()
    {
        return [
            ['value' => 'black', 'label' => __('Black')],
            ['value' => 'white-outline', 'label' => __('White Outline')],
            ['value' => 'white', 'label' => __('White')],
        ];
    }
}
