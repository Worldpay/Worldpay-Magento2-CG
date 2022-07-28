<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sapient\Worldpay\Plugin\Checkout\CustomerData;

use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;

/**
 * Process quote items price, considering tax configuration.
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Cart
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Quote\Model\Quote|null
     */
    protected $quote = null;
    
    /**
     * @var wplogger
     */
    protected $wplogger;

    /**
     * @var quoteIdToMaskedQuoteId
     */
    protected $quoteIdToMaskedQuoteId;

    /**
     * Constructor function
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $wpHelper
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $wpHelper,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->checkoutHelper = $checkoutHelper;
        $this->wplogger = $wplogger;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
        $this->wpHelper = $wpHelper;
    }

    /**
     * Add quote id and quote mask id to result
     *
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, $result)
    {
        if ($this->wpHelper->isGooglePayEnableonPdp() && $this->wpHelper->isGooglePayEnable()) {
            $quote = $this->getQuote();
            if ($quote) {
                if ($quote->getId()) {
                    $result['quote_id'] = $quote->getId();
                    $result['quote_masked_id'] = $this->getQuoteMaskId($quote->getId());
                }
            }
        }
        return $result;
    }

    /**
     * Get Quote
     */
    protected function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

    /**
     * Get Masked id by Quote Id
     *
     * @param string $quoteId
     * @return string|null
     * @throws LocalizedException
     */
    public function getQuoteMaskId($quoteId)
    {
        $maskedId = null;
        try {
            $maskedId = $this->quoteIdToMaskedQuoteId->execute($quoteId);
        } catch (NoSuchEntityException $exception) {
            $this->wplogger->info(__('Current user does not have an active cart.'));
        }
 
        return $maskedId;
    }
}
