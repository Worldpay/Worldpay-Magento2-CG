<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Model\ResourceModel\Recurring;

class Plan extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('worldpay_recurring_plans', 'plan_id');
    }
    
    /**
     * Load plan detail by $planId
     *
     * @param int $planId
     * @return int $id
     */
    public function loadById($planId)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("plan_id = ?", $planId);
        $sql = $this->getConnection()->select()->from($table,array('plan_id'))->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }
}
