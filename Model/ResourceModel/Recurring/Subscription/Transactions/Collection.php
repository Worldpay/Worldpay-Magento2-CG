<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Transactions;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string $_idFieldName
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
            \Sapient\Worldpay\Model\Recurring\Subscription\Transactions::class,
            \Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Transactions::class
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

    /**
     * Set Status Filter
     *
     * @param Subscription $subscription
     * @return $this
     */
    public function setStatusFilter(\Sapient\Worldpay\Model\Recurring\Subscription $subscription)
    {
        if ($subscription->getId()) {
            $this->addFieldToFilter('status', 'active');
        }
        return $this;
    }
}
