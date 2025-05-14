<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class CatalogProductSaveBeforeObserver implements ObserverInterface
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
     * Set has_options if subscriptions are enabled for product
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Sapient\Worldpay\Observer\CatalogProductSaveBeforeObserver
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        if (in_array($product->getTypeId(), $this->recurringHelper->getAllowedProductTypeIds())
            && $product->getWorldpayRecurringEnabled()
        ) {
            $product->setHasOptions(true);
        }

        $productOnDemand = $product->getData('product_on_demand');
        $recurringEnabled = $product->getData('worldpay_recurring_enabled');

        if ($productOnDemand && $recurringEnabled) {
            throw new LocalizedException(
                __('Both "Product on Demand" and "Worldpay Recurring Enabled" cannot be enabled on the same product.')
            );
        }

        return $this;
    }
}
