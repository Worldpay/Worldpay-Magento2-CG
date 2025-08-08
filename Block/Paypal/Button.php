<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Block\Paypal;

use Magento\Catalog\Block\Product\View as ProductView;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Sapient\Worldpay\Helper\Data;
use Sapient\Worldpay\Helper\ProductOnDemand;

/**
 * Configuration for JavaScript instant purchase button component.
 *
 * @api
 * @since 100.2.0
 */
class Button extends Template
{
    protected Data $worldpayHelper;

    protected ProductView $productView;

    protected ProductOnDemand $productOnDemand;
    protected StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        Data $helper,
        ProductView $productView,
        ProductOnDemand $productOnDemand,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->worldpayHelper = $helper;
        $this->productView = $productView;
        $this->productOnDemand = $productOnDemand;
        $this->storeManager = $storeManager;
    }

    public function isPaypalEnabledOnPdp()
    {
        return $this->worldpayHelper->isPaypalOnPdpEnabled() && $this->isAllowedCurrency() && !$this->isProductOnDemand();
    }

    public function getPaypalClientId(): ?string
    {
        return $this->worldpayHelper->getPaypalClientId();
    }

    public function getCurrencyCode(): string
    {
        return $this->worldpayHelper->getPaypalCurrency();
    }

    public function isAllowedCurrency(): bool
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode() === $this->getCurrencyCode();
    }

    private function isProductOnDemand(): bool
    {
        $product = $this->productView->getProduct();

        if ($product) {
            return $this->productOnDemand->isProductOnDemand($product);
        }

        return false;
    }
}
