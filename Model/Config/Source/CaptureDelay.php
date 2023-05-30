<?php
/**
 * @copyright 2023 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class CaptureDelay extends \Magento\Framework\App\Config\Value
{
    public const CUSTOM_CAPTURE_DELAY_KEY = "custom";
  /**
   * ToOption Array
   *
   * @return array
   */
    public function toOptionArray()
    {

        return [
            ['value' => '0', 'label' => __('0')],
            ['value' => self::CUSTOM_CAPTURE_DELAY_KEY, 'label' => __('1-14')],
            ['value' => 'off', 'label' => __('OFF')],
            ['value' => 'default', 'label' => __('DEFAULT')]
        ];
    }
}
