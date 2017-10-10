<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;

use Magento\Framework\Model\AbstractModel;

class SavedToken extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Sapient\Worldpay\Model\ResourceModel\SavedToken');
    }

    public function loadByTokenCode($order_id)
    {
       if (!$order_id) {
           return;         
        }
        $id = $this->getResource()->loadByTokenCode($order_id);
        return $this->load($id);  
    }
}
