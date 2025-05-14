<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Block\Catalog\Product;

class ProductOnDemand extends \Magento\Catalog\Block\Product\AbstractProduct
{
    private \Sapient\Worldpay\Helper\ProductOnDemand $productOnDemandHelper;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Sapient\Worldpay\Helper\ProductOnDemand $productOnDemandHelper,
        array $data = [],
    )
    {
        $this->productOnDemandHelper = $productOnDemandHelper;
        parent::__construct($context, $data);
    }

    public function getCustomLabel()
    {
        return $this->productOnDemandHelper->getPdpLabel();
    }

    public function canShowProductOnDemandTextField(): bool
    {
        return
            $this->productOnDemandHelper->isProductOnDemandGeneralConfigActive()
            && $this->productOnDemandHelper->isProductOnDemand($this->getProduct());
    }
}
