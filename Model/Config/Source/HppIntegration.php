<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class HppIntegration implements \Magento\Framework\Option\ArrayInterface
{
  /**
   * @var OPTION_VALUE_FULL_PAGE
   * @var OPTION_VALUE_IFRAME
   */

    public const OPTION_VALUE_FULL_PAGE = 'full_page';
    public const OPTION_VALUE_IFRAME = 'iframe';

  /**
   * ToOption Array
   *
   * @return array
   */
    public function toOptionArray()
    {

        return [
            ['value' => self::OPTION_VALUE_FULL_PAGE, 'label' => __('Full page')],
            ['value' => self::OPTION_VALUE_IFRAME, 'label' => __('Iframe')],
        ];
    }
}
