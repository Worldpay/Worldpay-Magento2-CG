<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class IntegrationMode implements \Magento\Framework\Option\ArrayInterface
{
  /**
   * ToOption Array
   *
   * @return array
   */
    public function toOptionArray()
    {

        return [
            ['value' => 'direct', 'label' => __('Direct')],
            ['value' => 'redirect', 'label' => __('Redirect')],
        ];
    }
}
