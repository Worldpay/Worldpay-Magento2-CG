<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Address;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Reset items data changed flag
     *
     * @var boolean
     */
    protected $_resetItemsDataChanged = true;

    /**
     * Define model and resource model, set default order
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Sapient\Worldpay\Model\Recurring\Subscription\Address::class,
            \Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Address::class
        );
    }

    /**
     * Set a subscription filter to use for the current callback request.
     *
     * @param \Sapient\Worldpay\Model\Recurring\Subscription $subscription
     * @return $this
     */
    public function setSubscriptionFilter(\Sapient\Worldpay\Model\Recurring\Subscription $subscription)
    {
        if ($subscription->getId()) {
            $this->addFieldToFilter('subscription_id', $subscription->getId());
        }
        return $this;
    }
}
