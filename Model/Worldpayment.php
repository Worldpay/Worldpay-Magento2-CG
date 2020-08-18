<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;

/**
 * Resource Model
 */
class Worldpayment extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\Worldpayment::class);
    }

    /**
     * Retrieve worldpay payment Details
     *
     * @return Sapient\Worldpay\Model\Worldpayment
     */
    public function loadByPaymentId($orderId)
    {

        if (!$orderId) {
            return;
        }
        $id = $this->getResource()->loadByPaymentId($orderId);
        return $this->load($id);
    }

    /**
     * Load worldpay payment Details
     *
     * @return Sapient\Worldpay\Model\Worldpayment
     */
    public function loadByWorldpayOrderId($order_id)
    {
        if (!$order_id) {
            return;
        }
        $id = $this->getResource()->loadByWorldpayOrderId($order_id);
        return $this->load($id);
    }
}
