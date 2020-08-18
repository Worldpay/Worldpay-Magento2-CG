<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\Recurring\Subscription;

class ToOrder
{
    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    private $objectCopyService;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $config;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @param \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->orderFactory = $orderFactory;
        $this->objectCopyService = $objectCopyService;
        $this->eventManager = $eventManager;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->config = $scopeConfig;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Create order based on subscription details
     *
     * @param \Sapient\Worldpay\Model\Recurring\Subscription $subscription
     * @param array $data
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function convert(\Sapient\Worldpay\Model\Recurring\Subscription $subscription, $data = [])
    {
        $order = $this->orderFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $order,
            $data,
            \Magento\Sales\Api\Data\OrderInterface::class
        );

        $this->objectCopyService->copyFieldsetToTarget(
            'worldpay_subscription_convert',
            'to_order',
            $subscription,
            $order
        );

        $this->eventManager->dispatch(
            'worldpay_subscription_convert_to_order',
            ['order' => $order, 'subscription' => $subscription]
        );

        $globalCurrencyCode = $this->config->getValue(
            \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
            'default'
        );
        $baseCurrency = $subscription->getStore()->getBaseCurrency();
        $storeCurrency = $subscription->getStore()->getCurrentCurrency();

        if (!$order->hasGlobalCurrencyCode()) {
            $order->setGlobalCurrencyCode($globalCurrencyCode);
        }

        if (!$order->hasBaseCurrencyCode()) {
            $order->setBaseCurrencyCode($baseCurrency->getCode());
        }

        if (!$order->hasStoreCurrencyCode()) {
            $order->setStoreCurrencyCode($storeCurrency->getCode());
        }

        if (!$order->hasOrderCurrencyCode()) {
            $order->setOrderCurrencyCode($order->getStoreCurrencyCode());
        }

        if (!$order->hasBaseToGlobalRate()) {
            $order->setBaseToGlobalRate($baseCurrency->getRate($globalCurrencyCode));
        }

        if (!$order->hasBaseToOrderRate()) {
            $order->setBaseToOrderRate($baseCurrency->getRate($storeCurrency));
        }

        if (!$order->hasDiscountAmount() && $order->hasBaseDiscountAmount()) {
            $order->setDiscountAmount(
                $this->priceCurrency->round($order->getBaseDiscountAmount() * $order->getBaseToOrderRate())
            );
        }

        if (!$order->hasGrandTotal() && $order->hasBaseGrandTotal()) {
            $order->setGrandTotal(
                $this->priceCurrency->round($order->getBaseGrandTotal() * $order->getBaseToOrderRate())
            );
        }

        if (!$order->hasShippingAmount() && $order->hasBaseShippingAmount()) {
            $order->setShippingAmount(
                $this->priceCurrency->round($order->getBaseShippingAmount() * $order->getBaseToOrderRate())
            );
        }

        if (!$order->hasShippingTaxAmount() && $order->hasBaseShippingTaxAmount()) {
            $order->setShippingTaxAmount(
                $this->priceCurrency->round($order->getBaseShippingTaxAmount() * $order->getBaseToOrderRate())
            );
        }

        if (!$order->hasStoreToBaseRate()) {
            $order->setStoreToBaseRate($storeCurrency->getRate($baseCurrency));
        }

        if (!$order->hasStoreToOrderRate()) {
            $order->setStoreToOrderRate(1);
        }

        if (!$order->hasSubtotal() && $order->getBaseSubtotal()) {
            $order->setSubtotal(
                $this->priceCurrency->round($order->getBaseSubtotal() * $order->getBaseToOrderRate())
            );
        }

        if (!$order->hasTaxAmount() && $order->hasBaseTaxAmount()) {
            $order->setTaxAmount(
                $this->priceCurrency->round($order->getBaseTaxAmount() * $order->getBaseToOrderRate())
            );
        }

        if (!$order->hasTotalQtyOrdered()) {
            $order->setTotalQtyOrdered(1);
        }

        if (!$order->hasCanShipPartially()) {
            $order->setCanShipPartially(0);
        }

        if (!$order->hasCanShipPartiallyItem()) {
            $order->setCanShipPartiallyItem(0);
        }

        if (!$order->hasCustomerIsGuest()) {
            $order->setCustomerIsGiest(0);
        }

        if (!$order->hasSubtotalInclTax() && $order->hasBaseSubtotalInclTax()) {
            $order->setSubtotalInclTax(
                $this->priceCurrency->round($order->getBaseSubtotalInclTax() * $order->getBaseToOrderRate())
            );
        }

        if (!$order->hasTotalItemCount()) {
            $order->setTotalItemCount(1);
        }

        if (!$order->hasShippingInclTax() && $order->hasBaseShippingInclTax()) {
            $order->setShippingInclTax(
                $this->priceCurrency->round($order->getBaseShippingInclTax() * $order->getBaseToOrderRate())
            );
        }

        return $order;
    }
}
