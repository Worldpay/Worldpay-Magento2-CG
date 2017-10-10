<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\ResourceModel;

class Worldpayment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('worldpay_payment', 'id');
    }

    public function loadByPaymentId($orderId)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("order_id = ?", $orderId);
        $sql = $this->getConnection()->select()->from($table,array('id'))->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }

    public function loadByWorldpayOrderId($order_id)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("worldpay_order_id = ?", $order_id);
        $sql = $this->getConnection()->select()->from($table,array('id'))->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }
}
