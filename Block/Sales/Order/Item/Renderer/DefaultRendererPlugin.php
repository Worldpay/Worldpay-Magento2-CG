<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sapient\Worldpay\Block\Sales\Order\Item\Renderer;

class DefaultRendererPlugin
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
     * @param \Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer $subject
     * @param $result
     * @return array
     */
    public function afterGetItemOptions(\Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer $subject, $result)
    {
        return array_merge(
            $this->recurringHelper->prepareOrderItemOptions($subject->getOrderItem()),
            $result
        );
    }
}
