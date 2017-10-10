<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\ResourceModel\HistoryNotification;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Sapient\Worldpay\Model\HistoryNotification',
            'Sapient\Worldpay\Model\ResourceModel\HistoryNotification'
        );
    }
}
