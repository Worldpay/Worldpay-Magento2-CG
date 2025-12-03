<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sapient\Worldpay\Plugin\Checkout\CustomerData;

use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Sapient\Worldpay\Helper\ProductOnDemand;

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
     * @var \Magento\Quote\Model\Quote|null
     */
    protected $quote = null;

    /**
     * @var wplogger
     */
    protected $wplogger;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $wpHelper;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    protected $quoteIdToMaskedQuoteId;

    private ProductOnDemand $productOnDemandHelper;

    /**
     * Constructor function
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $wpHelper
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $wpHelper,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        ProductOnDemand $productOnDemandHelper,
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->wplogger = $wplogger;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
        $this->wpHelper = $wpHelper;
        $this->productOnDemandHelper = $productOnDemandHelper;
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
        if (
            $this->productOnDemandHelper->isProductOnDemandGeneralConfigActive()
            && isset($result['items'])
            && is_array($result['items'])
        ) {
            foreach ($result['items'] as &$item) {
                $quoteItem = $this->getQuote()->getItemById($item['item_id']);
                if ($quoteItem) {
                    $product = $quoteItem->getProduct();
                    $product->load($product->getId());
                    if (
                        $product->getProductOnDemand()
                        || $product->getData('product_on_demand')
                    ) {
                        $item['message'] = $this->productOnDemandHelper->getMiniCartLabel();
                    }
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
