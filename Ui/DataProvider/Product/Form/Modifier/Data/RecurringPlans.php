<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Ui\DataProvider\Product\Form\Modifier\Data;

use Magento\Framework\Escaper;
use Magento\Catalog\Model\Locator\LocatorInterface;

class RecurringPlans
{
    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\Recurring\Plan\CollectionFactory
     */
    private $plansCollectionFactory;

    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @param Escaper $escaper
     * @param LocatorInterface $locator
     * @param \Sapient\Worldpay\Model\ResourceModel\Recurring\Plan\CollectionFactory $plansCollectionFactory
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     */
    public function __construct(
        Escaper $escaper,
        LocatorInterface $locator,
        \Sapient\Worldpay\Model\ResourceModel\Recurring\Plan\CollectionFactory $plansCollectionFactory,
        \Sapient\Worldpay\Helper\Recurring $recurringHelper
    ) {
        $this->escaper = $escaper;
        $this->locator = $locator;
        $this->plansCollectionFactory = $plansCollectionFactory;
        $this->recurringHelper = $recurringHelper;
    }

    /**
     * Get the plans data
     *
     * @return array
     */
    public function getPlansData()
    {
        $plansData = [];
        $productId = $this->locator->getProduct()->getId();
        if (!$productId) {
            return $plansData;
        }

        $plans = $this->plansCollectionFactory->create()->addProductIdFilter($productId);
        foreach ($plans as $plan) {
            $plansData[] = $this->preparePlanData($plan);
        }

        return $plansData;
    }

    /**
     * Prepare plan data for output
     *
     * @param \Sapient\Worldpay\Model\Recurring\Plan $plan
     * @return array
     */
    public function preparePlanData(\Sapient\Worldpay\Model\Recurring\Plan $plan)
    {
        return [
            'plan_id' => $plan->getId(),
            'code' => $this->escaper->escapeHtml($plan->getCode()),
            'name' => $this->escaper->escapeHtml($plan->getName()),
            'description' => $this->escaper->escapeHtml($plan->getDescription()),
            'number_of_payments' => $this->escaper->escapeHtml($plan->getNumberOfPayments()),
            'interval' => $this->recurringHelper->getPlanIntervalLabel($plan->getInterval()),
            'interval_amount' => number_format($plan->getIntervalAmount(), 2, null, ''),
            'trial_interval' => $this->recurringHelper->getPlanTrialIntervalLabel($plan->getTrialInterval()),
            'number_of_trial_intervals' => $this->escaper->escapeHtml($plan->getNumberOfTrialIntervals()),
            'sort_order' => $plan->getSortOrder(),
            'website_id' => $plan->getWebsiteId(),
            'active' => $plan->getActive()
        ];
    }
}
