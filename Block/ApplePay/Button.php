<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Block\ApplePay;

use Magento\Catalog\Block\Product\View as ProductView;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Sapient\Worldpay\Helper\ProductOnDemand;
use \Sapient\Worldpay\Logger\WorldpayLogger;
use Magento\Customer\Model\Context as CustomerContext;

/**
 * Configuration for JavaScript ApplePay button component.
 *
 * @api
 * @since 100.2.0
 */
class Button extends Template
{
    protected \Sapient\Worldpay\Helper\Data $worldpayHelper;

    protected ProductView $productView;

    protected ProductOnDemand $productOnDemandHelper;

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
        ProductOnDemand $productOnDemandHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->worldpayHelper = $helper;
        $this->productView = $productView;
        $this->productOnDemandHelper = $productOnDemandHelper;
    }

    /**
     * Checks if button enabled.
     *
     * @return bool
     * @since 100.2.0
     */
    public function isEnabled(): bool
    {
        return $this->worldpayHelper->isApplePayEnable() && !$this->isProductOnDemand();
    }

    /**
     * Check if Apple pay is enabled on PDP or not
     */
    public function isApplePayEnableonPdp()
    {
        return $this->worldpayHelper->isApplePayEnableonPdp() && !$this->isProductOnDemand();
    }

    /**
     * Get Apple pay Button Type
     */
    public function isApplePayButtonTypePdp()
    {
        return $this->worldpayHelper->getApplePayButtonTypePdp();
    }

    /**
     * Get Apple pay Button Color
     */
    public function isApplePayButtonColorPdp()
    {
        return $this->worldpayHelper->getApplePayButtonColorPdp();
    }

    /**
     * Get Apple pay Button Locale or not
     */
    public function isApplePayButtonLocalePdp()
    {
        return $this->worldpayHelper->getApplePayButtonLocalePdp();
    }

    private function isProductOnDemand(): bool
    {
        $product = $this->productView->getProduct();

        if ($product) {
            return $this->productOnDemandHelper->isProductOnDemand($product);
        }

        return false;
    }
}
