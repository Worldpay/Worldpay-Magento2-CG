<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Model\Config\Source;

class TrialInterval extends AbstractArraySource
{
    /**
     * @var DAY
     */
    public const DAY = 'DAY';

    /**
     * @var MONTH
     */
    public const MONTH = 'MONTH';

    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::DAY, 'label' => __('Day')],
            ['value' => self::MONTH, 'label' => __('Month')],
        ];
    }
}
