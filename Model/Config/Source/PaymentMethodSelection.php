<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class PaymentMethodSelection implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var RADIO_BUTTONS
     */
    public const RADIO_BUTTONS = 'radio';
    /**
     * @var DROPDOWN_MENU
     */
    public const DROPDOWN_MENU = 'dropdown';
    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray()
    {

        return [
            ['value' => self::RADIO_BUTTONS, 'label' => __('Radio Buttons')],
        ];
    }
}
