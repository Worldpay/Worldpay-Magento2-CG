<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sapient\Worldpay\Block\Sales\Order\Email\Items;

class DefaultItemsPlugin
{
    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     */
    public function __construct(\Sapient\Worldpay\Helper\Recurring $recurringHelper)
    {
        $this->recurringHelper = $recurringHelper;
    }

    /**
     * After GetItem Options Plugin
     *
     * @param array $subject
     * @param array $result
     * @return array
     */
    public function afterGetItemOptions(\Magento\Sales\Block\Order\Email\Items\DefaultItems $subject, $result)
    {
        return array_merge(
            $this->recurringHelper->prepareOrderItemOptions($subject->getItem()->getOrderItem()),
            $result
        );
    }
}
