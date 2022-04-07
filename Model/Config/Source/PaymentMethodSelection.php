<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class PaymentMethodSelection implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public const RADIO_BUTTONS = 'radio';
    public const DROPDOWN_MENU = 'dropdown';
    public function toOptionArray()
    {

        return [
            ['value' => self::RADIO_BUTTONS, 'label' => __('Radio Buttons')],
        ];
    }
}
