<?php

namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddWorldpayPaymentJoin implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $collection = $observer->getEvent()->getCollection();
        if ($collection->getMainTable() === 'sales_order_grid') {
            $collection->getSelect()->joinLeft(
                ['wp' => $this->getTableName('worldpay_payment')],
                'main_table.entity_id = wp.order_id',
                ['custom_column' => 'wp.custom_column']
            );
        }
    }
}
