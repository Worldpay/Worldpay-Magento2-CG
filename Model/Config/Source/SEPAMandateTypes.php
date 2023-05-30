<?php
/**
 * @copyright 2023 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class SEPAMandateTypes extends \Magento\Framework\App\Config\Value
{
  /**
   * ToOption Array
   *
   * @return array
   */
    public function toOptionArray()
    {
        return [
            ['value' => 'ONE-OFF', 'label' => __('ONE-OFF')]
        ];
    }
}
