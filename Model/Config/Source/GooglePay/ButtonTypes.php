<?php
namespace Sapient\Worldpay\Model\Config\Source\GooglePay;

class ButtonTypes implements \Magento\Framework\Data\OptionSourceInterface
{
 /**
  * Get button Color options
  */
    public function toOptionArray()
    {
        return [
            ['value' => 'book', 'label' => __('Book')],
            ['value' => 'buy', 'label' => __('Buy')],
            ['value' => 'checkout', 'label' => __('Checkout')],
            ['value' => 'donate', 'label' => __('Donate')],
            ['value' => 'order', 'label' => __('Order')],
            ['value' => 'pay', 'label' => __('Pay')],
            ['value' => 'plain', 'label' => __('Plain')],
            ['value' => 'subscribe', 'label' => __('Subscribe')],
        ];
    }
}
