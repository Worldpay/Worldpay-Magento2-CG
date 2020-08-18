<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sapient\Worldpay\Model\Recurring\Subscription;

class ToOrderItem
{
    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    private $objectCopyService;

    /**
     * @var \Magento\Sales\Api\Data\OrderItemInterfaceFactory
     */
    private $orderItemFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterfaceFactory $orderItemFactory
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderItemInterfaceFactory $orderItemFactory,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->orderItemFactory = $orderItemFactory;
        $this->objectCopyService = $objectCopyService;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->eventManager = $eventManager;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Create order item based on subscription data
     *
     * @param \Sapient\Worldpay\Model\Recurring\Subscription $subscription
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $data
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     */
    public function convert(
        \Sapient\Worldpay\Model\Recurring\Subscription $subscription,
        \Magento\Sales\Api\Data\OrderInterface $order,
        $data = []
    ) {
        $orderItemData = $this->objectCopyService->getDataFromFieldset(
            'worldpay_subscription_convert',
            'to_order_item',
            $subscription
        );

        $orderItem = $this->orderItemFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $orderItem,
            array_merge($orderItemData, $data),
            \Magento\Sales\Api\Data\OrderItemInterface::class
        );

        $orderItem->setProductOptions($subscription->getProductOptions());

        $this->eventManager->dispatch(
            'worldpay_subscription_convert_to_order_item',
            ['order_item' => $orderItem, 'subscription' => $subscription]
        );

        if (!$orderItem->hasQtyOrdered()) {
            $orderItem->setQtyOrdered(1);
        }

        if (!$orderItem->hasPrice() && $orderItem->hasBasePrice()) {
            $orderItem->setPrice($orderItem->getBasePrice() * $order->getBaseToOrderRate());
        }

        if (!$orderItem->hasOriginalPrice() && $orderItem->hasBaseOriginalPrice()) {
            $orderItem->setOriginalPrice(
                $this->priceCurrency->round($orderItem->getBaseOriginalPrice() * $order->getBaseToOrderRate())
            );
        }

        if (!$orderItem->hasTaxAmount() && $orderItem->hasBaseTaxAmount()) {
            $orderItem->setTaxAmount(
                $this->priceCurrency->round($orderItem->getBaseTaxAmount() * $order->getBaseToOrderRate())
            );
        }

        if (!$orderItem->hasDiscountAmount() && $orderItem->hasBaseDiscountAmount()) {
            $orderItem->setDiscountAmount(
                $this->priceCurrency->round($orderItem->getBaseDiscountAmount() * $order->getBaseToOrderRate())
            );
        }

        if (!$orderItem->hasBaseRowTotal() && $orderItem->hasBasePrice()) {
            $orderItem->setBaseRowTotal($orderItem->getBasePrice());
        }

        if (!$orderItem->hasRowTotal() && $orderItem->hasPrice()) {
            $orderItem->setRowTotal($orderItem->getPrice());
        }

        if (!$orderItem->hasBaseTaxBeforeDiscount() && $orderItem->hasBaseTaxAmount()) {
            $orderItem->setBaseTaxBeforeDiscount($orderItem->getBaseTaxAmount());
        }

        if (!$orderItem->hasTaxBeforeDiscount() && $orderItem->hasTaxAmount()) {
            $orderItem->setBaseTaxBeforeDiscount($orderItem->getTaxAmount());
        }

        if (!$orderItem->hasBasePriceInclTax()) {
            $orderItem->setBasePriceInclTax($orderItem->getBasePrice() + $orderItem->getBaseTaxAmount());
        }

        if (!$orderItem->hasPriceInclTax()) {
            $orderItem->setPriceInclTax($orderItem->getPrice() + $orderItem->getTaxAmount());
        }

        if (!$orderItem->hasBaseRowTotalInclTax()) {
            $orderItem->setBaseRowTotalInclTax($orderItem->getBasePriceInclTax());
        }

        if (!$orderItem->hasRowTotalInclTax()) {
            $orderItem->setRowTotalInclTax($orderItem->getPriceInclTax());
        }

        if (!$orderItem->hasLockedDoInvoice()) {
            $orderItem->setLockedDoInvoice(true);
        }

        return $orderItem;
    }
}
