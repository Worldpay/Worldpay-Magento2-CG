<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\ResourceModel\HistoryNotification;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * HistoryNotification collection   
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Sapient\Worldpay\Model\HistoryNotification',
            'Sapient\Worldpay\Model\ResourceModel\HistoryNotification'
        );
    }
}
