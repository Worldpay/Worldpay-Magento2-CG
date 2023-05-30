<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Controller\Paybylink\Multishipping;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Multishipping\Model\Checkout\Type\Multishipping;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\State;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

/**
 * Multishipping checkout success controller.
 */
class Success extends Action implements HttpGetActionInterface
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var Multishipping
     */
    private $multishipping;
    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection
     */
    private $wpMultishippingCollection;
    /**
     * @var \Magento\Sales\Model\Order
     */
    private $order;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @param Context $context
     * @param State $state
     * @param Multishipping $multishipping
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection $wpMultishippingCollection
     * @param \Magento\Sales\Model\Order $order
     */
    public function __construct(
        Context $context,
        State $state,
        Multishipping $multishipping,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection $wpMultishippingCollection,
        \Magento\Sales\Model\Order $order
    ) {
        $this->state = $state;
        $this->multishipping = $multishipping;
        $this->order = $order;
        $this->wpMultishippingCollection = $wpMultishippingCollection;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context);
    }
    /**
     * Multishipping checkout success page
     *
     * @return void
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (empty($params['orderKey'])) {
            $this->messageManager->addNotice(__("Order key not found."));
            return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
        }
        preg_match('/\^(\d+)-/', $params['orderKey'], $matches);
        $order = $this->order->loadByIncrementId($matches[1]); // load order by common multishipping order code
        if ($order->getId()) {
            $quoteId = $order->getQuoteId();
            $quoteObj = $this->quoteRepository->get($quoteId);
            $multiShippingOrders =  $this->wpMultishippingCollection->getMultishippingOrderIds($quoteId);
            if (count($multiShippingOrders) == 0) {
                $this->messageManager->addNotice("Multishipping orders not found");
                return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
            }
            $this->_view->loadLayout();
            $ids = $multiShippingOrders;
            $this->_eventManager->dispatch('multishipping_checkout_controller_success_action', ['order_ids' => $ids]);
            $this->_view->renderLayout();
            return;
        }
        $this->messageManager->addNotice("Order not found");
        return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
    }
}
