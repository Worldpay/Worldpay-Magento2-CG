<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class PaymentMethodSelection implements \Magento\Framework\Option\ArrayInterface
{
    
    const RADIO_BUTTONS = 'radio';
    const DROPDOWN_MENU = 'dropdown';
    
    /**
     * Payment selection type
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
