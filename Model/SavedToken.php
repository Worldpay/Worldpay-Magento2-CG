<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Resource Model
 */
class SavedToken extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\SavedToken::class);
    }
    
    /**
     * [loadByTokenCode description]
     *
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function loadByTokenCode($order_id)
    {
        if (!$order_id) {
            return;
        }
        $id = $this->getResource()->loadByTokenCode($order_id);
        return $this->load($id);
    }
    
    /**
     * [loadByStoredCredentials description]
     *
     * @param  [type] $transactionIdentifier [description]
     * @return [type]                        [description]
     */
    public function loadByStoredCredentials($transactionIdentifier)
    {
        if (!$transactionIdentifier) {
            return;
        }
        $id = $this->getResource()->loadByStoredCredentials($transactionIdentifier);
        return $this->load($id);
    }
}
