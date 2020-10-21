<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sapient\Worldpay\Model\Product\Type;

class SimplePlugin
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Sapient\Worldpay\Helper\Recurring $recurringHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->recurringHelper = $recurringHelper;
        $this->storeManager = $storeManager;
        $this->localeDate = $localeDate;
        $this->logger = $logger;
    }

    /**
     * Plugin for:
     * Initialize product(s) for add to cart process.
     * Advanced version of func to prepare product for cart - processMode can be specified there.
     *
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\DataObject $buyRequest
     * @param \Magento\Catalog\Model\Product $product
     * @param null|string $processMode
     * @return array|string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundPrepareForCartAdvanced(
        \Magento\Catalog\Model\Product\Type\AbstractType $subject,
        \Closure $proceed,
        \Magento\Framework\DataObject $buyRequest,
        $product,
        $processMode = null
    ) {
        if (!(in_array($product->getTypeId(), $this->recurringHelper->getAllowedProductTypeIds())
            && $this->recurringHelper->getSubscriptionValue('worldpay/subscriptions/active')
            && $product->getWorldpayRecurringEnabled()
            && ($planId = $buyRequest->getWorldpaySubscriptionPlan()))
        ) {
            return $proceed($buyRequest, $product, $processMode);
        }
        
        $product->addCustomOption('worldpay_subscription_plan_id', $planId);

        if ($product->getWorldpayRecurringAllowStart() && $buyRequest->getSubscriptionDate()) {
            $startDate = ($buyRequest->getSubscriptionDate())
                ? $buyRequest->getSubscriptionDate() : date('d-m-yy');
            $product->addCustomOption(
                'subscription_date',
                $startDate
            );
        }
        
        if ($buyRequest->getSubscriptionEndDate()) {
            $endDate = ($buyRequest->getSubscriptionEndDate())
                ? $buyRequest->getSubscriptionEndDate() : date('d-m-yy');
            $product->addCustomOption(
                'subscription_end_date',
                $endDate
            );
        }
        
        $result = $proceed($buyRequest, $product, $processMode);

        if (!$buyRequest->getResetCount() && ($item = $this->checkoutSession->getQuote()->getItemByProduct($product))) {
            return __($this->recurringHelper->getMyAccountExceptions('MCAM21'));
        } else {
            if ($processMode == \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_FULL) {
                $product->setCartQty(1);
            }
            $product->setQty(1);
            $buyRequest->setQty(1);
        }

        return $result;
    }

    /**
     * Plugin for:
     * Check if product can be bought
     *
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param $product
     * @return \Magento\Catalog\Model\Product\Type\AbstractType
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCheckProductBuyState(
        \Magento\Catalog\Model\Product\Type\AbstractType $subject,
        $product
    ) {
        $planOption = $product->getCustomOption('worldpay_subscription_plan_id');
        if (!($planOption && $planOption->getValue())) {
            return;
        }

        $plan = $this->recurringHelper->getSelectedPlan($product);
        if (!$plan
            || !$this->recurringHelper->getSubscriptionValue('worldpay/subscriptions/active')
            || !$product->getWorldpayRecurringEnabled()
            || !$plan->getActive()
            || $plan->getProductId() != $product->getId()
            || !in_array($plan->getWebsiteId(), [0, $this->storeManager->getStore()->getWebsiteId()])
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($this->recurringHelper->getMyAccountExceptions('MCAM20'))
            );
        }

        $quoteItem = $planOption->getItem();
        
        $quoteItem->setHasError(false)->setMessage(
                __('Please verify subscription data, before placing the order')
            );
        if ($quoteItem->getQuote()->getItemsQty() > 1) {
            $quoteItem->setHasError(true)->setMessage(
                __($this->recurringHelper->getMyAccountExceptions('MCAM19'))
            );
            $quoteItem->getQuote()->setHasError(true);
        }
    }

    /**
     * Plugin for:
     * Prepare selected options for product
     *
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param \Closure $proceed
     * @param $product
     * @param $buyRequest
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundProcessBuyRequest(
        \Magento\Catalog\Model\Product\Type\AbstractType $subject,
        \Closure $proceed,
        $product,
        $buyRequest
    ) {
        $result = $proceed($product, $buyRequest);

        $subscriptionOptions = [];
        $planId = $buyRequest->getWorldpaySubscriptionPlan();
        if ($planId) {
            $subscriptionOptions['worldpay_subscription_plan'] = $planId;
        }
        $planStartDate = $buyRequest->getSubscriptionDate();
        if ($planStartDate) {
            $subscriptionOptions['subscription_date'] = $planStartDate;
        }
        $planEndDate = $buyRequest->getSubscriptionEndDate();
        if ($planEndDate) {
            $subscriptionOptions['subscription_end_date'] = $planEndDate;
        }

        return array_merge($subscriptionOptions, $result);
    }

    /**
     * Plugin for:
     * Prepare additional options/information for order item which will be
     * created from this product
     *
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function aroundGetOrderOptions(
        \Magento\Catalog\Model\Product\Type\AbstractType $subject,
        \Closure $proceed,
        $product
    ) {
        $result = $proceed($product);

        $planOption = $product->getCustomOption('worldpay_subscription_plan_id');
        if (!($planOption && $planOption->getValue())) {
            return $result;
        }
        $subscriptionOptions = [];
        $plan = $this->recurringHelper->getSelectedPlan($product);
        if ($plan && $plan->getId()) {
            $subscriptionOptions['worldpay_subscription_options'] = [
                'plan_id' => $plan->getId(),
                'options_to_display' => [
                    'subscription_details' => $this->recurringHelper->getSelectedPlanOptionInfo($product)
                ]
            ];
            $planStartDateOption = $product->getCustomOption('subscription_date');
            if ($planStartDateOption && $planStartDateOption->getValue()
                    && $product->getWorldpayRecurringAllowStart()) {
                $startDate = $planStartDateOption->getValue() ? $planStartDateOption->getValue() : date('d-m-yy');
                $subscriptionOptions['worldpay_subscription_options']['subscription_date']
                    = $startDate;
                $subscriptionOptions['worldpay_subscription_options']['options_to_display']['subscription_date']
                        = $this->recurringHelper->getSelectedPlanStartDateOptionInfo($product);
            }
            $planEndDateOption = $product->getCustomOption('subscription_end_date');
            if ($planEndDateOption && $planEndDateOption->getValue()) {
                $endDate = $planEndDateOption->getValue() ? $planEndDateOption->getValue() : date('d-m-yy');
                $subscriptionOptions['worldpay_subscription_options']['subscription_end_date']
                    = $endDate;
                $subscriptionOptions['worldpay_subscription_options']['options_to_display']['subscription_end_date']
                        = $this->recurringHelper->getSelectedPlanEndDateOptionInfo($product);
            }
        }

        return array_merge($result, $subscriptionOptions);
    }

    /**
     * Plugin for:
     * Check if product can be configured
     *
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param \Closure $proceed
     * @param $product
     * @return bool
     */
    public function aroundCanConfigure(
        \Magento\Catalog\Model\Product\Type\AbstractType $subject,
        \Closure $proceed,
        $product
    ) {
        $result = $proceed($product);

        if ($result
            || !$this->recurringHelper->getSubscriptionValue('worldpay/subscriptions/active')
            || !in_array($product->getTypeId(), $this->recurringHelper->getAllowedProductTypeIds())
        ) {
            return $result;
        }

        return $product->getWorldpayRecurringEnabled() && $this->recurringHelper->getProductSubscriptionPlans($product);
    }
}
