<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Model\Config\Source;

class Interval extends AbstractArraySource
{
    const ANNUAL = 'ANNUAL';
    const SEMIANNUAL = 'SEMIANNUAL';
    const QUARTERLY = 'QUARTERLY';
    const MONTHLY = 'MONTHLY';
    const WEEKLY = 'WEEKLY';

    /**
     * @inheritdoc
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
