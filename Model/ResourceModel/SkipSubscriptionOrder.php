<?php

namespace Sapient\Worldpay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * SkipSubscriptionOrder resource
 */
class SkipSubscriptionOrder extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('worldpay_subscription_skip_orders', 'entity_id');
    }
}
