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
        $this->_init('Sapient\Worldpay\Model\ResourceModel\SavedToken');
    }

    /**
     * Load worldpay token Details
     *
     * @return Sapient\Worldpay\Model\SavedToken
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
     * Load worldpay Storedcredentials Details
     *
     * @return Sapient\Worldpay\Model\SavedToken
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
