<?php
namespace Sapient\Worldpay\Model\Config\Source\ApplePay;

class ButtonTypes implements \Magento\Framework\Data\OptionSourceInterface
{
 /**
  * Get button Color options
  */
    public function toOptionArray()
    {
        return [
            ['value' => 'buy', 'label' => __('Buy')],
            ['value' => 'add-money', 'label' => __('Add Money')],
            ['value' => 'book', 'label' => __('Book')],
            ['value' => 'check-out', 'label' => __('Check Out')],
            ['value' => 'continue', 'label' => __('Continue')],
            ['value' => 'contribute', 'label' => __('Contribute')],
            ['value' => 'donate', 'label' => __('Donate')],
            ['value' => 'order', 'label' => __('Order')],
            ['value' => 'pay', 'label' => __('Pay')],
            ['value' => 'plain', 'label' => __('Plain')],
            ['value' => 'reload', 'label' => __('Reload')],
            ['value' => 'rent', 'label' => __('Rent')],
            ['value' => 'set-up', 'label' => __('Set Up')],
            ['value' => 'subscribe', 'label' => __('Subscribe')],
            ['value' => 'support', 'label' => __('Support')],
            ['value' => 'tip', 'label' => __('Tip')],
            ['value' => 'top-up', 'label' => __('Top Up')],
        ];
    }
}
