<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SavedToken extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('worldpay_token', 'id');
    }

    public function loadByTokenCode($tokencode){
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("token_code = ?", $tokencode);
        $sql = $this->getConnection()->select()->from($table,array('id'))->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }
}
