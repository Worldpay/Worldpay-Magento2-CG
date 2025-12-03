<?php

namespace Sapient\Worldpay\Plugin\Checkout;

use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Message\ManagerInterface;
use Sapient\Worldpay\Helper\ProductOnDemand;

class DisableMultishipping
{
    protected RedirectInterface $redirect;

    protected ManagerInterface $messageManager;

    private ProductOnDemand $productOnDemandHelper;

    public function __construct(
        RedirectInterface $redirect,
        ManagerInterface $messageManager,
        ProductOnDemand $productOnDemandHelper,
    ) {
        $this->redirect = $redirect;
        $this->messageManager = $messageManager;
        $this->productOnDemandHelper = $productOnDemandHelper;
    }

    public function beforeExecute(\Magento\Multishipping\Controller\Checkout\Addresses $subject)
    {
        if ($this->productOnDemandHelper->isProductOnDemandQuote()) {
            $this->messageManager->addErrorMessage($this->productOnDemandHelper->getMultiShippingDisabledLabel());
            $this->redirect->redirect($subject->getResponse(), 'checkout/cart');
        }
    }
}
