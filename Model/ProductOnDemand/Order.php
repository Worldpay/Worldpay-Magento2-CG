<?php

namespace Sapient\Worldpay\Model\ProductOnDemand;

class Order extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\ProductOnDemand\Order::class);
    }

    public function loadByOrderId($orderId)
    {
        if (!$orderId) {
            return;
        }
        $id = $this->getResource()->loadByOrderId($orderId);
        return $this->load($id);
    }
}
