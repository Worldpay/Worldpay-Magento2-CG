<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Plugin\Checkout\Controller\Cart;

class Index
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
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Sapient\Worldpay\Helper\Recurring $recurringHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->recurringHelper = $recurringHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Remove gift card input if cart contains subscription items
     *
     * @param \Magento\Checkout\Controller\Cart\Index $subject
     * @param \Magento\Framework\View\Result\Page $result
     * @return \Magento\Framework\View\Result\Page
     */
    public function afterExecute(\Magento\Checkout\Controller\Cart\Index $subject, $result)
    {
        if (!($result instanceof \Magento\Framework\View\Result\Page
            && $result->getLayout()
            && ($giftCardBlock = $result->getLayout()->getBlock('checkout.cart.giftcardaccount')))
        ) {
            return $result;
        }

        $quote = $this->checkoutSession->getQuote();
        if ($quote && $this->recurringHelper->quoteContainsSubscription($quote)) {
            $giftCardBlock->setTemplate('');
        }

        return $result;
    }
}
