<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\Pricing\Price;

use Sapient\Worldpay\Model\Recurring\Plan;

interface PlanPriceInterface
{
    /**
     * Get the plan amount
     *
     * @param Plan $plan
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    public function getPlanAmount(Plan $plan);
}
