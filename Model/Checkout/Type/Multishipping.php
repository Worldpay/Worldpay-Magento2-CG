<?php

/**
 * Sapient 2022
 */

namespace Sapient\Worldpay\Model\Checkout\Type;

use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Multishipping extends \Magento\Multishipping\Model\Checkout\Type\Multishipping
{
    /**
     * @var \Magento\Multishipping\Model\Checkout\Type\Multishipping\PlaceOrderFactory
     */
    protected $placeOrderFactory = null;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger = null;
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager = null;
    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper = null;
    /**
     * @var array
     */
    protected $paymentdetailsdata;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;
    /**
     * @var \Magento\Quote\Model\Quote\Address\ToOrder
     */
    protected $quoteAddressToOrder;
    /**
     * @var \Magento\Quote\Model\Quote\Address\ToOrderAddress
     */
    protected $quoteAddressToOrderAddress;
    /**
     * @var \Magento\Quote\Model\Quote\Payment\ToOrderPayment
     */
    protected $quotePaymentToOrderPayment;
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;
    /**
     * @var \Magento\Quote\Model\Quote\Item\ToOrderItem
     */
    protected $quoteItemToOrderItem;
    /**
     * Constructor
     */
    protected function _construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $placOrder = \Magento\Multishipping\Model\Checkout\Type\Multishipping\PlaceOrderFactory::class;
        $this->placeOrderFactory = $objectManager->get($placOrder);
        $this->logger = $objectManager->get(\Psr\Log\LoggerInterface::class);
        $this->session = $objectManager->get(\Magento\Framework\Session\Generic::class);
        $this->checkoutSession = $objectManager->get(\Magento\Checkout\Model\Session::class);
        $this->eventManager = $objectManager->get(\Magento\Framework\Event\ManagerInterface::class);
        $this->_orderFactory = $objectManager->get(\Magento\Sales\Model\OrderFactory::class);
        $this->dataObjectHelper = $objectManager->get(\Magento\Framework\Api\DataObjectHelper::class);
        $this->quoteAddressToOrder = $objectManager->get(\Magento\Quote\Model\Quote\Address\ToOrder::class);
        $toOrderAddress = \Magento\Quote\Model\Quote\Address\ToOrderAddress::class;
        $this->quoteAddressToOrderAddress = $objectManager->get($toOrderAddress);
        $toOrderPayment = \Magento\Quote\Model\Quote\Payment\ToOrderPayment::class;
        $this->quotePaymentToOrderPayment = $objectManager->get($toOrderPayment);
        $this->priceCurrency = $objectManager->get(PriceCurrencyInterface::class);
        $this->quoteItemToOrderItem = $objectManager->get(\Magento\Quote\Model\Quote\Item\ToOrderItem::class);
        $this->quoteRepository = $objectManager->get(\Magento\Quote\Api\CartRepositoryInterface::class);
    }

    /**
     * Create multishipping orders
     */
    public function createOrders()
    {
        $this->_construct();

        $quote = $this->getQuote();
        $orders = [];

        $this->_validate();

        $shippingAddresses = $quote->getAllShippingAddresses();
        if ($quote->hasVirtualItems()) {
            $shippingAddresses[] = $quote->getBillingAddress();
        }

        foreach ($shippingAddresses as $key => $address) {
            $first = false;
            if ($key === array_key_first($shippingAddresses)) {
                $first = true;
            }

            $order = $this->_prepareOrder($address);

            $orders[] = $order;
            $this->eventManager->dispatch(
                'checkout_type_multishipping_create_orders_single',
                ['order' => $order, 'address' => $address, 'quote' => $quote]
            );
        }

        $paymentProviderCode = $quote->getPayment()->getMethod();
        $placeOrderService = $this->placeOrderFactory->create($paymentProviderCode);
        $exceptionList = $placeOrderService->place($orders);
        foreach ($exceptionList as $exception) {
            $this->logger->critical($exception);
        }

        /********* Missing Multishipping code */
         /** @var OrderInterface[] $failedOrders */
         $failedOrders = [];
         /** @var OrderInterface[] $successfulOrders */
         $successfulOrders = [];
         foreach ($orders as $order) {
             if (isset($exceptionList[$order->getIncrementId()])) {
                 $failedOrders[] = $order;
             } else {
                 $successfulOrders[] = $order;
             }
         }

         $placedAddressItems = [];
         foreach ($successfulOrders as $order) {
             $orderIds[$order->getId()] = $order->getIncrementId();
             if ($order->getCanSendNewEmailFlag()) {
                 //$this->orderSender->send($order);
             }
             $placedAddressItems = $this->getPlacedAddressItems($order);
         }

         $addressErrors = [];
         if (!empty($failedOrders)) {
             $this->removePlacedItemsFromQuote($shippingAddresses, $placedAddressItems);
             $addressErrors = $this->getQuoteAddressErrors(
                 $failedOrders,
                 $shippingAddresses,
                 $exceptionList
             );
         } else {
             $this->_checkoutSession->setLastQuoteId($this->getQuote()->getId());
             $this->getQuote()->setIsActive(false);
             $this->quoteRepository->save($this->getQuote());
         }

         $this->_session->setOrderIds($orderIds);
         $this->_session->setAddressErrors($addressErrors);
         $this->_eventManager->dispatch(
             'checkout_submit_all_after',
             ['orders' => $orders, 'quote' => $this->getQuote()]
         );
        /********* End Missing Multishipping code */

        return [
            "orders" => $orders,
            "exceptionList" => $exceptionList
        ];
    }
    /**
     * Prepare order based on quote address
     *
     * @param   \Magento\Quote\Model\Quote\Address $address
     * @return  \Magento\Sales\Model\Order
     * @throws  \Magento\Checkout\Exception
     */
    protected function _prepareOrder(\Magento\Quote\Model\Quote\Address $address)
    {
        $quote = $this->getQuote();
        $quote->unsReservedOrderId();
        if (!empty($this->checkoutSession->getHppReservedOrderId())) {
            $quote->setReservedOrderId($this->checkoutSession->getHppReservedOrderId());
            $this->checkoutSession->unsHppReservedOrderId();
        } else {
            $quote->reserveOrderId();
        }
        $quote->collectTotals();

        $order = $this->_orderFactory->create();

        $this->dataObjectHelper->mergeDataObjects(
            \Magento\Sales\Api\Data\OrderInterface::class,
            $order,
            $this->quoteAddressToOrder->convert($address)
        );

        $shippingMethodCode = $address->getShippingMethod();
        if ($shippingMethodCode) {
            $rate = $address->getShippingRateByCode($shippingMethodCode);
            $shippingPrice = $rate->getPrice();
        } else {
            $shippingPrice = $order->getShippingAmount();
        }
        $store = $order->getStore();
        $amountPrice = $store->getBaseCurrency()
            ->convert($shippingPrice, $store->getCurrentCurrencyCode());
        $order->setBaseShippingAmount($shippingPrice);
        $order->setShippingAmount($amountPrice);

        $order->setQuote($quote);
        $order->setBillingAddress($this->quoteAddressToOrderAddress->convert($quote->getBillingAddress()));

        if ($address->getAddressType() == 'billing') {
            $order->setIsVirtual(1);
        } else {
            $order->setShippingAddress($this->quoteAddressToOrderAddress->convert($address));
            $order->setShippingMethod($address->getShippingMethod());
        }

        $order->setPayment($this->quotePaymentToOrderPayment->convert($quote->getPayment()));
        if ($this->priceCurrency->round($address->getGrandTotal()) == 0) {
            $order->getPayment()->setMethod('free');
        }

        foreach ($address->getAllItems() as $item) {
            $_quoteItem = $item->getQuoteItem();
            if (!$_quoteItem) {
                throw new \Magento\Checkout\Exception(
                    __("The item isn't found, or it's already ordered.")
                );
            }
            $item->setProductType(
                $_quoteItem->getProductType()
            )->setProductOptions(
                $_quoteItem->getProduct()->getTypeInstance()->getOrderOptions($_quoteItem->getProduct())
            );
            $orderItem = $this->quoteItemToOrderItem->convert($item);
            if ($item->getParentItem()) {
                $orderItem->setParentItem($order->getItemByQuoteItemId($_quoteItem->getParentItem()->getId()));
            }
            $order->addItem($orderItem);
        }

        return $order;
    }
    /**
     * Set result page data's
     *
     * @param Quote $quote
     * @param array $successfulOrders
     * @param array $failedOrders
     * @param array $exceptionList
     */
    public function setResultsPageData($quote, $successfulOrders, $failedOrders, $exceptionList)
    {
        $shippingAddresses = $quote->getAllShippingAddresses();
        if ($quote->hasVirtualItems()) {
            $shippingAddresses[] = $quote->getBillingAddress();
        }

        $successfulOrderIds = [];
        foreach ($successfulOrders as $order) {
            $successfulOrderIds[$order->getId()] = $order->getIncrementId();
        }

        $this->session->setOrderIds($successfulOrderIds);

        $addressErrors = [];
        if (!empty($failedOrders)) {
            $addressErrors = $this->getQuoteAddressErrors($failedOrders, $shippingAddresses, $exceptionList);
            $this->session->setAddressErrors($addressErrors);
        }
    }
    /**
     * Get orders error from session
     *
     * @param Quote $quote
     * @param array $successfulOrders
     * @param array $failedOrders
     * @param array $exceptionList
     */
    public function getAddressErrors($quote, $successfulOrders, $failedOrders, $exceptionList)
    {
        $shippingAddresses = $quote->getAllShippingAddresses();
        if ($quote->hasVirtualItems()) {
            $shippingAddresses[] = $quote->getBillingAddress();
        }

        $addressErrors = [];
        if (!empty($failedOrders)) {
            $addressErrors = $this->getQuoteAddressErrors(
                $failedOrders,
                $shippingAddresses,
                $exceptionList
            );
        }

        return $addressErrors;
    }
    /**
     * Deactivate the quote
     *
     * @param Quote $quote
     */
    public function deactivateQuote($quote)
    {
        $this->_construct();

        $this->checkoutSession->setLastQuoteId($quote->getId());
        $quote->setIsActive(false);
        $this->quoteRepository->save($quote);
    }
    /**
     * Get quote address errors.
     *
     * @param OrderInterface[] $orders
     * @param \Magento\Quote\Model\Quote\Address[] $addresses
     * @param \Exception[] $exceptionList
     * @return string[]
     * @throws NotFoundException
     */
    private function getQuoteAddressErrors(array $orders, array $addresses, array $exceptionList): array
    {
        $addressErrors = [];
        foreach ($orders as $failedOrder) {
            if (!isset($exceptionList[$failedOrder->getIncrementId()])) {
                throw new NotFoundException(__('Exception for failed order not found.'));
            }
            $addressId = $this->searchQuoteAddressId($failedOrder, $addresses);
            $addressErrors[$addressId] = $exceptionList[$failedOrder->getIncrementId()]->getMessage();
        }

        return $addressErrors;
    }
    /**
     * Returns quote address id that was assigned to order.
     *
     * @param OrderInterface $order
     * @param \Magento\Quote\Model\Quote\Address[] $addresses
     *
     * @return int
     * @throws NotFoundException
     */
    private function searchQuoteAddressId(OrderInterface $order, array $addresses): int
    {
        $items = $order->getItems();
        $item = array_pop($items);
        foreach ($addresses as $address) {
            foreach ($address->getAllItems() as $addressItem) {
                if ($addressItem->getQuoteItemId() == $item->getQuoteItemId()) {
                    return (int)$address->getId();
                }
            }
        }

        throw new NotFoundException(__('Quote address for failed order ID "%1" not found.', $order->getEntityId()));
    }

    /**
     * Returns placed address items
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getPlacedAddressItems(OrderInterface $order): array
    {
        $placedAddressItems = [];
        foreach ($this->getQuoteAddressItems($order) as $key => $quoteAddressItem) {
            $placedAddressItems[$key] = $quoteAddressItem;
        }

        return $placedAddressItems;
    }
    /**
     * Returns quote address item id.
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getQuoteAddressItems(OrderInterface $order): array
    {
        $placedAddressItems = [];
        foreach ($order->getItems() as $orderItem) {
            $placedAddressItems[] = $orderItem->getQuoteItemId();
        }

        return $placedAddressItems;
    }
    /**
     * Remove successfully placed items from quote.
     *
     * @param \Magento\Quote\Model\Quote\Address[] $shippingAddresses
     * @param int[] $placedAddressItems
     * @return void
     */
    private function removePlacedItemsFromQuote(array $shippingAddresses, array $placedAddressItems)
    {
        foreach ($shippingAddresses as $address) {
            foreach ($address->getAllItems() as $addressItem) {
                if (in_array($addressItem->getQuoteItemId(), $placedAddressItems)) {
                    if ($addressItem->getProduct()->getIsVirtual()) {
                        $addressItem->isDeleted(true);
                    } else {
                        $address->isDeleted(true);
                    }

                    $this->decreaseQuoteItemQty($addressItem->getQuoteItemId(), $addressItem->getQty());
                }
            }
        }
        $this->save();
    }
    /**
     * Decrease quote item quantity.
     *
     * @param int $quoteItemId
     * @param int $qty
     * @return void
     */
    private function decreaseQuoteItemQty(int $quoteItemId, int $qty)
    {
        $quoteItem = $this->getQuote()->getItemById($quoteItemId);
        if ($quoteItem) {
            $newItemQty = $quoteItem->getQty() - $qty;
            if ($newItemQty > 0) {
                $quoteItem->setQty($newItemQty);
            } else {
                $this->getQuote()->removeItem($quoteItem->getId());
                $this->getQuote()->setIsMultiShipping(1);
            }
        }
    }
}
