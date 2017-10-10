<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class HistoryNotification extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('worldpay_notification_history', 'id');
    }
}
