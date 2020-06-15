<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\ObserverInterface;

class PaymentMethodIsActiveObserver implements ObserverInterface
{
    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Sapient\Worldpay\Helper\Recurring $recurringHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->recurringHelper = $recurringHelper;
        $this->registry = $registry;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Disable payment methods not suitable for subscriptions
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $result = $observer->getEvent()->getResult();
        if (!$result->getData('is_available')) {
            return;
        }

        if (!$quote) {
            $quote = $this->checkoutSession->getQuote();
        }

        $paymentMethod = $observer->getEvent()->getMethodInstance();
        if ($paymentMethod->getCode() != \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE
            && $this->recurringHelper->quoteContainsSubscription($quote)
        ) {
            $result->setData('is_available', (bool)$paymentMethod->getConfigData('can_use_for_worldpay_subscription'));
        }
    }
}
