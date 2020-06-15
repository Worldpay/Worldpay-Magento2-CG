<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\Config\Source;

class SubscriptionStatus extends AbstractArraySource
{
    const ACTIVE = 'active';
    const SUSPENDED = 'suspended';
    const CANCELLED = 'cancelled';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::ACTIVE, 'label' => __('Active')],
            ['value' => self::SUSPENDED, 'label' => __('Suspended')],
            ['value' => self::CANCELLED, 'label' => __('Cancelled')]
        ];
    }
}
