<?php
/**
 * @copyright 2023 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class SamsungPayButtonTypes extends \Magento\Framework\App\Config\Value
{
  /**
   * ToOption Array
   *
   * @return array
   */
    public function toOptionArray()
    {
        return [
        ['value' => 'pay-card', 'label' => __('Normal')],
        ['value' => 'pay-card-dark', 'label' => __('Normal Dark')],
        ['value' => 'pay-card-ver', 'label' => __('Vertical')],
        ['value' => 'pay-card-ver-dark', 'label' => __('Vertical Dark ')],

        ];
    }
}
