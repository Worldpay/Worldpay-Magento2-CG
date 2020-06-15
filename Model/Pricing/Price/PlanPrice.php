<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\Pricing\Price;

use Magento\Catalog\Pricing\Price\RegularPrice;
use Sapient\Worldpay\Model\Recurring\Plan;

class PlanPrice extends RegularPrice implements PlanPriceInterface
{
    /**
     * @param Plan $plan
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    public function getPlanAmount(Plan $plan)
    {
        $price = $plan->getIntervalAmount();
        $convertedPrice = $this->priceCurrency->convertAndRound($price);
        return $this->calculator->getAmount($convertedPrice, $plan->getProduct());
    }
}
