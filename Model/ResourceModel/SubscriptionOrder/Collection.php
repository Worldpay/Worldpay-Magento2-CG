<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\ResourceModel\SubscriptionOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * subscription order collection
 */
class Collection extends AbstractCollection
{
    /**
     * Flag that indicates if plans table has been joined
     *
     * @var bool
     */
    private $plansJoined = false;

    /**
     * Flag that indicates if subscriptions table has been joined
     *
     * @var bool
     */
    private $subscriptionsJoined = false;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Sapient\Worldpay\Model\SubscriptionOrder::class,
            \Sapient\Worldpay\Model\ResourceModel\SubscriptionOrder::class
        );
    }
    
   /**
     * Filter collection by customer id
     *
     * @param int $customerId
     * @return $this
     */
    public function addCustomerIdFilter($customerId)
    {
        if ($customerId) {
            $this->addFieldToFilter('main_table.customer_id', $customerId);
        }

        return $this;
    }

    /**
     * Join plans table
     *
     * @param array|string $cols
     * @return $this
     */
    public function joinPlans($cols = \Magento\Framework\DB\Select::SQL_WILDCARD)
    {
        if ($this->plansJoined) {
            return $this;
        }

        $this->getSelect()->joinLeft(
            ['plans' => 'worldpay_recurring_plans'],
            'plans.plan_id = main_table.plan_id',
            $cols );
        $this->plansJoined = true;

        return $this;
    }
    /**
     * Join Subscriptions table
     *
     * @param array|string $cols
     * @return $this
     */
    public function joinSubscriptions($cols = \Magento\Framework\DB\Select::SQL_WILDCARD)
    {
        if ($this->subscriptionsJoined) {
            return $this;
        }

        $this->getSelect()->joinLeft(
            ['subscriptions' => 'worldpay_subscriptions'],
            'subscriptions.subscription_id = main_table.subscription_id',
            $cols );
        $this->subscriptionsJoined = true;

        return $this;
    }
 
}
