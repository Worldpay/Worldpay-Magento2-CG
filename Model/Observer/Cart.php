<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Sapient\Worldpay\Helper\Data;
use Sapient\Worldpay\Helper\ProductOnDemand;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Model\Checkout\Service as CheckoutService;
use Sapient\Worldpay\Model\Order\Service as OrderService;

class Cart implements ObserverInterface
{
    private WorldpayLogger $wpLogger;
    private OrderService $orderService;
    private CheckoutService $checkoutService;
    private Session $checkoutSession;
    private ProductOnDemand $productOnDemandHelper;
    private Data $dataHelper;

    public function __construct(
        WorldpayLogger  $wpLogger,
        OrderService    $orderService,
        CheckoutService $checkoutService,
        Session         $checkoutSession,
        ProductOnDemand $productOnDemandHelper,
        Data            $dataHelper
    ) {
        $this->orderService = $orderService;
        $this->wpLogger = $wpLogger;
        $this->checkoutService = $checkoutService;
        $this->checkoutSession = $checkoutSession;
        $this->productOnDemandHelper = $productOnDemandHelper;
        $this->dataHelper = $dataHelper;
    }

   /**
    * Load the shopping cart from the latest authorized, but not completed order
    */
    public function execute(Observer $observer)
    {
        if ($this->checkoutSession->getauthenticatedOrderId() && $this->dataHelper->shouldRestoreCart()) {
            $order = $this->orderService->getAuthorisedOrder();
            $this->checkoutService->reactivateQuoteForOrder($order);
            $this->orderService->removeAuthorisedOrder();
        }

        if ($this->productOnDemandHelper->isProductOnDemandGeneralConfigActive()) {
            $quote = $this->checkoutSession->getQuote();
            $items = $quote->getAllItems();
            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($items as $item) {
                if ($item) {
                    $product = $item->getProduct();
                    $product->load($product->getId());
                    if ($product->getProductOnDemand() || $product->getData('product_on_demand')) {
                        $item->addMessage($this->productOnDemandHelper->getCartLabel());
                    }
                }
            }
        }
    }
}
