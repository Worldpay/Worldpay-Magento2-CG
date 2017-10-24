<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * HistoryNotification resource
 */
class HistoryNotification extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('worldpay_notification_history', 'id');
    }
}
