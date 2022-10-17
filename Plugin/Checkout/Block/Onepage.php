<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Plugin\Checkout\Block;

use Magento\Framework\Serialize\Serializer\Json;

class Onepage
{
    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Json $serializer
     */
    public function __construct(
        \Sapient\Worldpay\Helper\Recurring $recurringHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        Json $serializer
    ) {
        $this->recurringHelper = $recurringHelper;
        $this->checkoutSession = $checkoutSession;
        $this->serializer = $serializer;
    }

    /**
     * Remove gift card input if cart contains subscription items
     *
     * @param \Magento\Checkout\Block\Onepage $subject
     * @param string $result
     * @return string
     */
    public function afterGetJsLayout(\Magento\Checkout\Block\Onepage $subject, $result)
    {
        $quote = $this->checkoutSession->getQuote();
        if ($quote && $this->recurringHelper->quoteContainsSubscription($quote)) {
            $jsLayout = $this->serializer->unserialize($result);
            if (isset(
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['afterMethods']['children']['giftCardAccount']
            )) {
                unset(
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['afterMethods']['children']['giftCardAccount']
                );
                $result = $this->serializer->serialize($jsLayout);
            }
        }

        return $result;
    }
}
