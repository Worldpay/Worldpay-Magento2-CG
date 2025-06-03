<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use Sapient\Worldpay\Helper\ProductOnDemand;

class Cart implements ObserverInterface
{
    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    private $wplogger;
    /**
     * @var \Sapient\Worldpay\Model\Order\Service
     */
    private $orderservice;

    /**
     * @var \Sapient\Worldpay\Model\Checkout\Service
     */
    private $checkoutservice;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutsession;

    private ProductOnDemand $productOnDemandHelper;

    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Model\Checkout\Service $checkoutservice
     * @param \Magento\Checkout\Model\Session $checkoutsession
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Sapient\Worldpay\Model\Checkout\Service $checkoutservice,
        \Magento\Checkout\Model\Session $checkoutsession,
        ProductOnDemand $productOnDemandHelper,
    ) {
        $this->orderservice = $orderservice;
        $this->wplogger = $wplogger;
        $this->checkoutservice = $checkoutservice;
        $this->checkoutsession = $checkoutsession;
        $this->productOnDemandHelper = $productOnDemandHelper;
    }

   /**
    * Load the shopping cart from the latest authorized, but not completed order
    *
    * @param \Magento\Framework\Event\Observer $observer
    */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->checkoutsession->getauthenticatedOrderId()) {
            $order = $this->orderservice->getAuthorisedOrder();
            $this->checkoutservice->reactivateQuoteForOrder($order);
            $this->orderservice->removeAuthorisedOrder();
        }

        if ($this->productOnDemandHelper->isProductOnDemandGeneralConfigActive()) {
            $quote = $this->checkoutsession->getQuote();
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
