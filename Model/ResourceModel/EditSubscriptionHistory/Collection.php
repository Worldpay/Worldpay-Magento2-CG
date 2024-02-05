<?php

namespace Sapient\Worldpay\Model\ResourceModel\EditSubscriptionHistory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * EditSubscriptionHistory Collection
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
            \Sapient\Worldpay\Model\EditSubscriptionHistory::class,
            \Sapient\Worldpay\Model\ResourceModel\EditSubscriptionHistory::class
        );
    }
}
