<?php

/**
 * @copyright 2022 Sapient
 */

namespace Sapient\Worldpay\Model\ResourceModel\Multishipping;

class Order extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('worldpay_multishipping', 'id');
    }
    /**
     * Load worldpayment detail by orderId
     *
     * @param int $orderId
     * @return int $id
     */
    public function loadByOrderId($orderId)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("order_id = ?", $orderId);
        $sql = $this->getConnection()->select()->from($table, ['id'])->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }
}
