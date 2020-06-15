<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Model\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Sapient\Worldpay\Helper\Recurring as RecurringHelper;

class CompositeConfigProviderPlugin
{
    /**
     * @var RecurringHelper
     */
    private $recurringHelper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @param RecurringHelper $recurringHelper
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        RecurringHelper $recurringHelper,
        CheckoutSession $checkoutSession
    ) {
        $this->recurringHelper = $recurringHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Plugin for:
     * Retrieve assoc array of checkout configuration
     *
     * @param \Magento\Checkout\Model\CompositeConfigProvider $subject
     * @param $result
     * @return array
     */
    public function afterGetConfig(\Magento\Checkout\Model\CompositeConfigProvider $subject, $result)
    {
        if (is_array($result)
            && ((isset($result['payment']['customerBalance']['isAvailable'])
                    && $result['payment']['customerBalance']['isAvailable'])
                || (isset($result['payment']['reward']['isAvailable']) && $result['payment']['reward']['isAvailable']))
            && $this->recurringHelper->quoteContainsSubscription($this->getQuote())
        ) {
            if (isset($result['payment']['customerBalance']['isAvailable'])
                && $result['payment']['customerBalance']['isAvailable']
            ) {
                $result['payment']['customerBalance']['isAvailable'] = false;
            }
            if (isset($result['payment']['reward']['isAvailable']) && $result['payment']['reward']['isAvailable']) {
                $result['payment']['reward']['isAvailable'] = false;
            }
        }

        return $result;
    }

    /**
     * Retrieve Quote object
     *
     * @return Quote
     */
    private function getQuote()
    {
        if (!$this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }
}
