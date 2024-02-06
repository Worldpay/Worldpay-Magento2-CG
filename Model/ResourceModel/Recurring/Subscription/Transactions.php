<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription;

class Transactions extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * Fields that should be serialized before persistence
     *
     * @var array
     */
    protected $_serializableFields = [
        'product_options' => [[], []],
        'payment_additional_information' => [[], []]
    ];

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->orderFactory = $orderFactory;
    }

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('worldpay_recurring_transactions', 'entity_id');
    }
    
    /**
     * Load transcation detail by $orderIncrementId
     *
     * @param int $orderIncrementId
     * @return int $id
     */
    public function loadByOriginalOrderIncrementId($orderIncrementId)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("original_order_increment_id = ?", $orderIncrementId);
        $sql = $this->getConnection()->select()->from($table, ['entity_id'])->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }
    
    /**
     * Load transcation detail by $subscriptionId
     *
     * @param int $subscriptionId
     * @return int $id
     */
    public function loadBySubscriptionId($subscriptionId)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("subscription_id = ?", $subscriptionId);
        $sql = $this->getConnection()->select()->from($table, ['entity_id'])->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }
    
    /**
     * Load transcation detail by $entityId
     *
     * @param int $entityId
     * @return int $id
     */
    public function loadById($entityId)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("entity_id = ?", $entityId);
        $sql = $this->getConnection()->select()->from($table, ['entity_id'])->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }

    /**
     * Load Active Transcation detail by $subscriptionId
     *
     * @param int $subscriptionId
     * @return int $id
     */
    public function loadBySubscriptionIdActive($subscriptionId)
    {
        $active = "active";
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("subscription_id = ?", $subscriptionId);
        $whereTwo = $this->getConnection()->quoteInto("status = ?", $active);
        $sql = $this->getConnection()->select()->from($table, ['entity_id'])->where($where)->where($whereTwo);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }
}
