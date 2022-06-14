<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sapient\Worldpay\Model;

class ProductPlugin
{
    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     */
    public function __construct(
        \Sapient\Worldpay\Helper\Recurring $recurringHelper
    ) {
        $this->recurringHelper = $recurringHelper;
    }

    /**
     * Plugin for:
     *
     * Get product price
     *
     * @param \Magento\Catalog\Model\Product $subject
     * @param array $result
     * @return float
     */
    public function afterGetPrice(\Magento\Catalog\Model\Product $subject, $result)
    {
        if (in_array($subject->getTypeId(), $this->recurringHelper->getAllowedProductTypeIds())
            && $this->recurringHelper->getSubscriptionValue('worldpay/subscriptions/active')
            && $subject->getWorldpayRecurringEnabled()
            && ($plan = $this->recurringHelper->getSelectedPlan($subject))
        ) {
            $result = $plan->getIntervalAmount();
        }
        return $result;
    }
}
