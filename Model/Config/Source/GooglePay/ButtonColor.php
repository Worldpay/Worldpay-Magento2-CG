<?php
namespace Sapient\Worldpay\Model\Config\Source\GooglePay;

class ButtonColor implements \Magento\Framework\Data\OptionSourceInterface
{
 /**
  * Get button Color options
  */
    public function toOptionArray()
    {
        return [
            ['value' => 'default', 'label' => __('default')],
            ['value' => 'white', 'label' => __('white')],
            ['value' => 'black', 'label' => __('black')]
        ];
    }
}
