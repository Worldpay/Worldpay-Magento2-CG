<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\ResourceModel\Recurring;

class Subscription extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var array
     */
    protected $_serializableFields = [
        'product_options' => [[], []],
        'payment_additional_information' => [[], []]
    ];

    /**
     * [__construct description]
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context        [description]
     * @param \Magento\Sales\Model\OrderFactory                 $orderFactory   [description]
     * @param string                                            $connectionName [description]
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
        $this->_init('worldpay_subscriptions', 'subscription_id');
    }
    
    /**
     * Update subscription id in order table
     *
     * @param int $orderId
     * @param int $subscriptionId
     * @return $this
     */
    public function updateOrderRelation($orderId, $subscriptionId)
    {
        $orderResource = $this->orderFactory->create()->getResource();
        
        $orderResource->getConnection()->update(
            $orderResource->getMainTable(),
            ['worldpay_subscription_id' => $subscriptionId],
            ['entity_id = ?' => $orderId]
        );
        $orderResource->getConnection()->update(
            'sales_order_grid',
            ['worldpay_subscription_id' => $subscriptionId],
            ['entity_id = ?' => $orderId]
        );
        return $this;
    }

    /**
     * Load subscription detail by $orderIncrementId
     *
     * @param int $orderIncrementId
     * @return int $id
     */
    public function loadByOriginalOrderIncrementId($orderIncrementId)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("original_order_increment_id = ?", $orderIncrementId);
        $sql = $this->getConnection()->select()->from($table, ['subscription_id'])->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }
}
