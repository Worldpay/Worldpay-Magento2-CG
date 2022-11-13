<?php
/**
 * @copyright 2022 Sapient
 */
namespace Sapient\Worldpay\Model\Multishipping;

class Order extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\Multishipping\Order::class);
    }
    /**
     * Retrieve multishipping details
     *
     * @param string $orderId
     * @return Sapient\Worldpay\Model\Order
     */
    public function loadByOrderId($orderId)
    {
        if (!$orderId) {
            return;
        }
        $id = $this->getResource()->loadByOrderId($orderId);
        return $this->load($id);
    }
}
