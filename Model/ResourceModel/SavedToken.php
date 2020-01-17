<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * SavedToken resource
 */
class SavedToken extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('worldpay_token', 'id');
    }

    /**
     * Load token detail by tokencode
     *
     * @param int $tokencode        
     * @return int $id
     */
    public function loadByTokenCode($tokencode){
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("token_code = ?", $tokencode);
        $sql = $this->getConnection()->select()->from($table,array('id'))->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }
    
    /**
     * Load token detail by tokencode
     *
     * @param int $transactionIdentifier        
     * @return int $id
     */
    public function loadByStoredCredentials($transactionIdentifier){
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("transaction_identifier = ?", $transactionIdentifier);
        $sql = $this->getConnection()->select()->from($table,array('id'))->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }
}
