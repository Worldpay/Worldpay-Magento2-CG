<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Model\Config\Source;

class Interval extends AbstractArraySource
{
    public const ANNUAL = 'ANNUAL';
    public const SEMIANNUAL = 'SEMIANNUAL';
    public const QUARTERLY = 'QUARTERLY';
    public const MONTHLY = 'MONTHLY';
    public const WEEKLY = 'WEEKLY';

  /**
   * ToOption Array
   *
   * @return array
   */
    public function toOptionArray()
    {
        return [
            ['value' => self::WEEKLY, 'label' => __('Weekly')],
            ['value' => self::MONTHLY, 'label' => __('Monthly')],
            ['value' => self::QUARTERLY, 'label' => __('Quarterly')],
            ['value' => self::SEMIANNUAL, 'label' => __('Semiannually')],
            ['value' => self::ANNUAL, 'label' => __('Annually')],
        ];
    }
}
