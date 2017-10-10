<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\ResourceModel\Collection;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Sapient\Worldpay\Model\Worldpayment','Sapient\Worldpay\Model\ResourceModel\Worldpayment');
    }
}