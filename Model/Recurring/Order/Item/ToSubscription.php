<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\Recurring\Order\Item;

use Sapient\Worldpay\Model\Recurring\Subscription;
use Magento\Sales\Model\Order;

class ToSubscription
{
    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    private $objectCopyService;

    /**
     * @var \Sapient\Worldpay\Model\Recurring\SubscriptionFactory
     */
    private $subscriptionFactory;

    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Sapient\Worldpay\Model\Recurring\SubscriptionFactory $subscriptionFactory
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Sapient\Worldpay\Model\Recurring\SubscriptionFactory $subscriptionFactory,
        \Sapient\Worldpay\Helper\Recurring $recurringHelper,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->objectCopyService = $objectCopyService;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->recurringHelper = $recurringHelper;
        $this->eventManager = $eventManager;
    }

    /**
     * Convert order/order item data to subscription
     *
     * @param Order\Item $item
     * @param Order $order
     * @param array $data
     * @return Subscription
     */
    public function convert(Order\Item $item, Order $order, $data = [])
    {
        $subscription = $this->subscriptionFactory->create();

        $subscriptionDataFromItem = $this->objectCopyService->getDataFromFieldset(
            'sales_convert_order_item',
            'to_worldpay_subscription',
            $item
        );

        $subscriptionDataFromOrder = $this->objectCopyService->getDataFromFieldset(
            'sales_convert_order',
            'to_worldpay_subscription',
            $order
        );

        $subscription->addData(array_merge(
            $subscriptionDataFromItem,
            $subscriptionDataFromOrder,
            $data
        ));

        $this->eventManager->dispatch(
            'sales_convert_order_item_to_worldpay_subscription',
            ['item' => $item, 'subscription' => $subscription]
        );

        $plan = $this->recurringHelper->getOrderItemPlan($item);
        $subscription->setPlan($plan);

        if (!$subscription->hasPlanId()) {
            $subscription->setPlanId($plan->getId());
        }

        if (!$subscription->hasStartDate()
            && ($startDate = $this->recurringHelper->getOrderItemSubscriptionStartDate($item))
        ) {
            $subscription->setStartDate($startDate);
        }
        
        if (!$subscription->hasEndDate()
            && ($endDate = $this->recurringHelper->getOrderItemSubscriptionEndDate($item))
        ) {
            $subscription->setEndDate($endDate);
        }

        if (!$subscription->hasPlanCode()) {
            $subscription->setPlanCode($plan->getCode());
        }

        if (!$subscription->hasIntervalAmount()) {
            $subscription->setIntervalAmount(
                (abs($plan->getIntervalAmount() - $order->getBaseTotalDue()) > 0.0001)
                    ? $order->getBaseTotalDue() : null
            );
        }

        if (!$subscription->hasBillingName() && ($billingAddress = $order->getBillingAddress())) {
            $subscription->setBillingName($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname());
        }

        if (!$subscription->hasShippingName() && ($shippingAddress = $order->getShippingAddress())) {
            $subscription->setShippingName($shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname());
        }

        if (!$subscription->hasStatus()) {
            $subscription->setStatus(\Sapient\Worldpay\Model\Config\Source\SubscriptionStatus::ACTIVE);
        }

        //$this->createAddonsAndDiscounts($subscription, $order);

        return $subscription;
    }
}
