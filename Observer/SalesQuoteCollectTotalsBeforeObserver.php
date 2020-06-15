<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesQuoteCollectTotalsBeforeObserver implements ObserverInterface
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
     * Restrict usage of alternative payment options (like customer balance, rewards, etc.) if cart contains
     * subscription items
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();

        if (($quote->getUseCustomerBalance() || $quote->getUseRewardPoints() || $quote->getGiftCards())
            && $this->recurringHelper->quoteContainsSubscription($quote)
        ) {
            // restrict customer balance usage
            $quote->setUseCustomerBalance(false);

            // restrict reward points usage
            $quote->setUseRewardPoints(false);

            // remove gift cards
//            $quote->setGiftCards(null)
//                ->setBaseGiftCardsAmount(0)
//                ->setGiftCardsAmount(0)
//                ->setBaseGiftCardsAmountUsed(0)
//                ->setGiftCardsAmountUsed(0);
        }
    }
}
