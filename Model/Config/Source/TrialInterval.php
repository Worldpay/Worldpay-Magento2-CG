<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Model\Config\Source;

class TrialInterval extends AbstractArraySource
{
    const DAY = 'DAY';
    const MONTH = 'MONTH';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::DAY, 'label' => __('Day')],
            ['value' => self::MONTH, 'label' => __('Month')],
        ];
    }
}
