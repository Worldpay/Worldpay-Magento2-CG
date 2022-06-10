<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\ObserverInterface;

class CatalogProductGetFinalPriceObserver implements ObserverInterface
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
     * [execute description]
     *
     * @param  \Magento\Framework\Event\Observer $observer [description]
     * @return [type]                                      [description]
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        $plan = $this->recurringHelper->getSelectedPlan($product);
        if ($plan) {
            $product->setFinalPrice($plan->getIntervalAmount());
        }

        return $this;
    }
}
