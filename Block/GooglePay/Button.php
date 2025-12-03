<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Block\GooglePay;

use Magento\Catalog\Block\Product\View as ProductView;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Sapient\Worldpay\Helper\ProductOnDemand;

/**
 * Configuration for JavaScript instant purchase button component.
 *
 * @api
 * @since 100.2.0
 */
class Button extends Template
{
    protected \Sapient\Worldpay\Helper\Data $worldpayHelper;

    protected ProductView $productView;

    protected ProductOnDemand $productOnDemand;

    /**
     * Button constructor.
     * @param Context $context
     * @param \Sapient\Worldpay\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Sapient\Worldpay\Helper\Data $helper,
        ProductView $productView,
        ProductOnDemand $productOnDemand,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->worldpayHelper = $helper;
        $this->productView = $productView;
        $this->productOnDemand = $productOnDemand;
    }

    /**
     * Checks if button enabled.
     *
     * @return bool
     * @since 100.2.0
     */
    public function isEnabled(): bool
    {
        return $this->worldpayHelper->isGooglePayEnable() && !$this->isProductOnDemand();
    }

    /**
     * Check if Google pay is enabled on PDP or not
     */
    public function isGooglePayEnableonPdp()
    {
        return $this->worldpayHelper->isGooglePayEnableonPdp() && !$this->isProductOnDemand();
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
