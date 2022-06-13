<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class ExemptionPlacement implements \Magento\Framework\Option\ArrayInterface
{
  /**
   * ToOption Array
   *
   * @return array
   */
    public function toOptionArray()
    {

        return [
            ['value' => 'AUTHORISATION', 'label' => __('AUTHORISATION- Applies exemption in authorisation flow')],
            ['value' => 'AUTHENTICATION', 'label' => __('AUTHENTICATION - Applies exemption in authentication flow')],
            ['value' => 'OPTIMISED', 'label' => __('OPTIMISED')],
        ];
    }
}
