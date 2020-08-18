<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\ObserverInterface;

class SaveOrderItemCommitAfterObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    private $objectCopyService;

    /**
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     */
    public function __construct(\Magento\Framework\DataObject\Copy $objectCopyService)
    {
        $this->objectCopyService = $objectCopyService;
    }

    /**
     * Save subscription
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $orderItem = $observer->getEvent()->getItem();
        $subscription = $orderItem->getWorldpaySubscription();
        //$subscription = $orderItem->getWorldpaySubscription();
        
//        if (($subscription = $orderItem->getWorldpaySubscription()) && $subscription->getWorldpaySubscriptionId()
//            && !$subscription->getId()
//        ) {
            //$logger->info('$subscription->getWorldpaySubscriptionId()--'.print_r($subscription->getWorldpaySubscriptionId(),true));
        //$logger->info('$subscription->getId()--'.print_r($subscription->getId(),true));
        if ($orderItem->getWorldpaySubscription()) {
            $order = $orderItem->getOrder();
            $paymentData = $this->objectCopyService->getDataFromFieldset(
                'sales_convert_order_payment',
                'to_worldpay_subscription',
                $order->getPayment()
            );
            $subscription->addData($paymentData)
                ->setOriginalOrderId($order->getId())
                ->setStoreName($order->getStoreName())
                ->save()
                ->updateOriginalOrderRelation();
        }
    }
}
