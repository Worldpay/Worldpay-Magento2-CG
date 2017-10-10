<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;

class HistoryNotification extends \Magento\Framework\Model\AbstractModel 
{
    protected function _construct()
    {
        $this->_init('Sapient\Worldpay\Model\ResourceModel\HistoryNotification');
    }
}