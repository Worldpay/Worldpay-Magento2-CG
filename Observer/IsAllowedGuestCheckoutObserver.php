<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\ObserverInterface;

class IsAllowedGuestCheckoutObserver implements ObserverInterface
{
    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    protected $recurringHelper;

    /**
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     */
    public function __construct(\Sapient\Worldpay\Helper\Recurring $recurringHelper)
    {
        $this->recurringHelper = $recurringHelper;
    }

    /**
     * Restrict guest checkout if quote contain subscription product
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $result = $observer->getEvent()->getResult();

        if ($this->recurringHelper->quoteContainsSubscription($observer->getEvent()->getQuote())) {
            $result->setIsAllowed(false);
        }

        return $this;
    }
}
