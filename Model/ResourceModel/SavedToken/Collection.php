<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\ResourceModel\SavedToken;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * SavedToken collection   
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
            'Sapient\Worldpay\Model\SavedToken',
            'Sapient\Worldpay\Model\ResourceModel\SavedToken'
        );
    }
}
