<?php

namespace Sapient\Worldpay\Model\ResourceModel\ProductOnDemand;

class Order extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    protected function _construct()
    {
        $this->_init('worldpay_product_on_demand_order', 'id');
    }

    public function loadByOrderId($orderId)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("order_id = ?", $orderId);
        $sql = $this->getConnection()->select()->from($table)->where($where);

        return $this->getConnection()->fetchAll($sql);
    }

    public function getZeroAuthOrder($orderId)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("order_id = ?", $orderId);
        $whereTwo = $this->getConnection()->quoteInto("is_zero_auth_order", true);
        $sql = $this->getConnection()->select()->from($table)->where($where)->where($whereTwo);

        return $this->getConnection()->fetchRow($sql);
    }
}
