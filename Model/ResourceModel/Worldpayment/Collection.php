<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\ResourceModel\Collection;

/**
 * Worldpay payment collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Sapient\Worldpay\Model\Worldpayment::class,
            \Sapient\Worldpay\Model\ResourceModel\Worldpayment::class
        );
    }
}
