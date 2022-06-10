<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesServiceQuoteSubmitBeforeObserver implements ObserverInterface
{
    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var \Sapient\Worldpay\Model\Recurring\SubscriptionFactory
     */
    private $orderItemToSubscription;

    /**
     * @var \Sapient\Worldpay\Model\Recurring\Order\Address\ToSubscriptionAddress
     */
    private $orderAddressToSubscriptionAddress;

    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     * @param \Sapient\Worldpay\Model\Recurring\Order\Item\ToSubscription $orderItemToSubscription
     * @param \Sapient\Worldpay\Model\Recurring\Order\Address\ToSubscriptionAddress $orderAddressToSubscriptionAddress
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     */
    public function __construct(
        \Sapient\Worldpay\Helper\Recurring $recurringHelper,
        \Sapient\Worldpay\Model\Recurring\Order\Item\ToSubscription $orderItemToSubscription,
        \Sapient\Worldpay\Model\Recurring\Order\Address\ToSubscriptionAddress $orderAddressToSubscriptionAddress,
        \Magento\Framework\DataObject\Copy $objectCopyService
    ) {
        $this->recurringHelper = $recurringHelper;
        $this->orderItemToSubscription = $orderItemToSubscription;
        $this->orderAddressToSubscriptionAddress = $orderAddressToSubscriptionAddress;
        $this->objectCopyService = $objectCopyService;
    }
    
    /**
     * Set corresponding flag to order if quote contains subscription items
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();
        
        if ($this->recurringHelper->quoteContainsSubscription($quote)) {
            $order->setContainsWorldpaySubscription(true);
            foreach ($order->getAllItems() as $item) {
                if ($plan = $this->recurringHelper->getOrderItemPlan($item)) {
                    /** @var \Sapient\Worldpay\Model\Recurring\Subscription $subscription */
                    $subscription = $this->orderItemToSubscription->convert($item, $order);
                    $this->convertAddresses($subscription, $order, $item);
                    $item->setWorldpaySubscription($subscription);
                    //$subscriptionss = $item->getWorldpaySubscription();
                    $this->saveSubscriptionData($subscription, $order, $item);
                }
            }
        }
    }

    /**
     * [convertAddresses description]
     *
     * @param  \Sapient\Worldpay\Model\Recurring\Subscription $subscription [description]
     * @param  \Magento\Sales\Model\Order                     $order        [description]
     * @param  \Magento\Sales\Model\Order\Item                $item         [description]
     * @return [type]                                                       [description]
     */
    private function convertAddresses(
        \Sapient\Worldpay\Model\Recurring\Subscription $subscription,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Item $item
    ) {
        $addresses = [];
        $billingAddress = $this->orderAddressToSubscriptionAddress->convert(
            $order->getBillingAddress(),
            ['address_type' => 'billing', 'email' => $order->getCustomerEmail()]
        );
        $addresses[] = $billingAddress;
        if (!$item->getIsVirtual()) {
            $shippingAddress = $this->orderAddressToSubscriptionAddress->convert(
                $order->getBillingAddress(),
                ['address_type' => 'shipping', 'email' => $order->getCustomerEmail()]
            );
            $addresses[] = $shippingAddress;
        }
        $subscription->setAddresses($addresses);
    }
    /**
     * [saveSubscriptionData description]
     *
     * @param  \Sapient\Worldpay\Model\Recurring\Subscription $subscription [description]
     * @param  \Magento\Sales\Model\Order                     $order        [description]
     * @param  \Magento\Sales\Model\Order\Item                $item         [description]
     * @return [type]                                                       [description]
     */
    public function saveSubscriptionData(
        \Sapient\Worldpay\Model\Recurring\Subscription $subscription,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Item $item
    ) {
        
        if (($subscription = $item->getWorldpaySubscription()) && $subscription->getWorldpaySubscriptionId()
            && !$subscription->getId()
        ) {
            //$order = $item->getOrder();
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
