<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;

class Worldpayment extends \Magento\Framework\Model\AbstractModel 
{
    protected function _construct()
    {
        $this->_init('Sapient\Worldpay\Model\ResourceModel\Worldpayment');
    }
    public function loadByPaymentId($orderId)
    {

        if (!$orderId) {
            return;         
        }
        $id = $this->getResource()->loadByPaymentId($orderId);
        return $this->load($id);
        
    }

    public function loadByWorldpayOrderId($order_id)
    {
        if(!$order_id){
            return;         
        }
        $id = $this->getResource()->loadByWorldpayOrderId($order_id);
        return $this->load($id);  
    }
 
   
}