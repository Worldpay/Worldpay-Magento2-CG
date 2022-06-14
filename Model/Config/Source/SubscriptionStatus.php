<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\Config\Source;

class SubscriptionStatus extends AbstractArraySource
{
    /**
     * @var ACTIVE
     */
    public const ACTIVE = 'active';
    /**
     * @var SUSPENDED
     */
    public const SUSPENDED = 'suspended';
    /**
     * @var CANCELLED
     */
    public const CANCELLED = 'cancelled';
    /**
     * @var EXPIRED
     */
    public const EXPIRED = 'expired';

     /**
      * To Option Array
      *
      * @return array
      */
    public function toOptionArray()
    {
        return [
            ['value' => self::ACTIVE, 'label' => __('Active')],
            ['value' => self::SUSPENDED, 'label' => __('Suspended')],
            ['value' => self::EXPIRED, 'label' => __('Expired')],
            ['value' => self::CANCELLED, 'label' => __('Cancelled')]
        ];
    }
}
