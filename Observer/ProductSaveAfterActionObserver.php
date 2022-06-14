<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProductSaveAfterActionObserver implements ObserverInterface
{
    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\Recurring\Plan\CollectionFactory
     */
    private $plansCollectionFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;
    
    /**
     * Worldpay helper
     *
     * @var \Magento\Catalog\Helper\Data
     */
    private $helper;

    /**
     * ProductSaveAfterActionObserver constructor
     *
     * @param \Sapient\Worldpay\Model\ResourceModel\Recurring\Plan\CollectionFactory $plansCollectionFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Sapient\Worldpay\Helper\GeneralException $helper
     */
    public function __construct(
        \Sapient\Worldpay\Model\ResourceModel\Recurring\Plan\CollectionFactory $plansCollectionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Sapient\Worldpay\Helper\GeneralException $helper
    ) {
        $this->plansCollectionFactory = $plansCollectionFactory;
        $this->messageManager = $messageManager;
        $this->helper = $helper;
    }

    /**
     * Save plans data
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Sapient\Worldpay\Observer\ProductSaveAfterActionObserver
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        
        $request = $observer->getEvent()->getController()->getRequest();
        $plansData = $request->getPost('worldpay_recurring_plans');
        if (is_array($plansData) && isset($plansData['plans']) && is_array($plansData['plans'])) {
            $plansData = $plansData['plans'];
            $hashedArray = [];
            foreach ($plansData as $planData) {
                if (!is_array($planData) || !isset($planData['plan_id'])) {
                    continue;
                }
                $hashedArray[$planData['plan_id']] = $planData;
            }

            $plansData = $hashedArray;
        } else {
            $plansData = [];
        }

        if (!$plansData) {
            return $this;
        }

        $planCollection = $this->plansCollectionFactory->create()
            ->addFieldToFilter('plan_id', ['in' => array_keys($plansData)]);
        foreach ($planCollection as $plan) {
            $id = $plan->getId();
            $save = false;

            if ((isset($plansData[$id]['sort_order']) && $plansData[$id]['sort_order'] != $plan->getSortOrder())) {
                $plan->setSortOrder($plansData[$id]['sort_order']);
                $save = true;
            }

            if ((isset($plansData[$id]['website_id']) && $plansData[$id]['website_id'] != $plan->getWebsiteId())) {
                $plan->setWebsiteId($plansData[$id]['website_id']);
                $save = true;
            }

            if ((isset($plansData[$id]['active']) && $plansData[$id]['active'] != $plan->getActive())) {
                $plan->setActive($plansData[$id]['active']);
                $save = true;
            }

            if ($save) {
                try {
                    $plan->save();
                } catch (\Exception $e) {
                    $this->messageManager->addError(
                        __($this->helper->getConfigValue('ACAM10'), $plan->getCode(), $e->getMessage())
                    );
                }
            }
        }

        return $this;
    }
}
