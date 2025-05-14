<?php

namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Sapient\Worldpay\Helper\ProductOnDemand;

class ModifyOrderAmount implements ObserverInterface
{
    private ProductOnDemand $productOnDemandHelper;
    private CartRepositoryInterface $cartRepository;

    public function __construct(ProductOnDemand $productOnDemandHelper, CartRepositoryInterface $cartRepository)
    {
        $this->productOnDemandHelper = $productOnDemandHelper;
        $this->cartRepository = $cartRepository;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quoteId = $order->getQuoteId();
        $quote = $this->cartRepository->get($quoteId);

        if ($this->productOnDemandHelper->quoteContainsProductOnDemand($quote)) {
            $order->setTotalPaid(0);
            $order->setTotalInvoiced(0);
        }

        return $this;
    }
}
