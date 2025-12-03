<?php

namespace Sapient\Worldpay\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

class SalesOrderGridPlugin
{
    /**
     * Add merchant code filter before load Sales Order Grid
     *
     * @param Collection $subject
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     * @throws LocalizedException
     */
    public function beforeLoad(Collection $subject, bool $printQuery = false, bool $logQuery = false): array
    {
        if (!$subject->isLoaded()) {
            $orderIncrementColumnName = 'increment_id';
            $tableName = $subject->getResource()->getTable('worldpay_payment');

            $subject->getSelect()->joinLeft(
                $tableName,
                $tableName . '.order_id = main_table.' . $orderIncrementColumnName,
                [$tableName . '.merchant_id' => new \Zend_Db_Expr('GROUP_CONCAT(merchant_id)')]
            );
            $subject->getSelect()->group('main_table.entity_id');
        }
        return [$printQuery, $logQuery];
    }
}
