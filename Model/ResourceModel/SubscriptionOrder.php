<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\ResourceModel;

/**
 * Resource SubscriptionOrder
 */
class SubscriptionOrder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
  /**
   * Initialize resource model
   *
   * @return void
   */
    protected function _construct()
    {
        $this->_init('worldpay_recurring_transactions', 'entity_id');
    }
}
