<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'subscription_id';

    /**
     * Store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Flag that indicates if plans table has been joined
     *
     * @var bool
     */
    private $plansJoined = false;

    /**
     * Collection constructor.
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Define model and resource model, set default order
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Sapient\Worldpay\Model\Recurring\Subscription::class,
            \Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription::class
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
     * Filter collection by website
     *
     * @param null|int $websiteId
     * @return $this
     */
    public function addWebsiteFilter($websiteId = null)
    {
        if ($websiteId === null) {
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
        }

        $this->addFieldToFilter(
            'main_table.store_id',
            ['in' => $this->storeManager->getWebsite($websiteId)->getStoreIds()]
        );

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
            $cols
        )->columns(
            [
                'interval_amount' => $this->getConnection()->getCheckSql(
                    'main_table.interval_amount IS NULL',
                    'plans.interval_amount',
                    'main_table.interval_amount'
                )
            ]
        );

        $this->plansJoined = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _translateCondition($field, $condition)
    {
        if ($field == 'interval_amount') {
            $this->joinPlans();
            return '('
                . $this->_getConditionSql(
                    new \Zend_Db_Expr('main_table.interval_amount IS NOT NULL AND main_table.interval_amount'),
                    $condition
                )
                . ')'
                . ' '
                . \Magento\Framework\DB\Select::SQL_OR . ' '
                . '('
                . $this->_getConditionSql(
                    new \Zend_Db_Expr('main_table.interval_amount IS NULL AND plans.interval_amount'),
                    $condition
                )
                . ')';
        }

        return parent::_translateCondition($field, $condition);
    }
}
