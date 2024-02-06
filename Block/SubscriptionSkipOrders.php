<?php
/**
 * Copyright © 2023 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Sapient\Worldpay\Block;

use Sapient\Worldpay\Model\SkipSubscriptionOrder;

class SubscriptionSkipOrders extends \Magento\Framework\View\Element\Template
{
    /**
     * @var skipSubscriptionOrder
     */
    protected $skipSubscriptionOrder;

    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\SubscriptionOrder\Collection
     */
    private $subscriptionOrderCollection;

    /**
     * @var $orderRepository
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\UrlInterface $urlInterface
     */
    protected $urlInterface;
    /**
     * Subscriptions block constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param SkipSubscriptionOrder $skipSubscriptionOrder
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Helper\Recurring $recurringhelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        SkipSubscriptionOrder $skipSubscriptionOrder,
        \Magento\Customer\Model\Session $customerSession,
        \Sapient\Worldpay\Helper\Recurring $recurringhelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\UrlInterface $urlInterface,
        array $data = []
    ) {
        $this->skipSubscriptionOrder = $skipSubscriptionOrder;
        $this->recurringhelper = $recurringhelper;
        $this->orderRepository = $orderRepository;
        $this->urlInterface = $urlInterface;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve Subscription Order Collection
     *
     * @return mixed
     */
    public function getskipSubscriptionOrderCollection()
    {
        $subscriptionId = $this->getRequest()->getParam('subscription_id');
        $timeFilter = $this->getRequest()->getParam('timeFilter');
        $timeFilterby = $this->skipSubscriptionOrder
            ->getFilterDate($timeFilter=null);
        $subscriptionOrderCollection = $this->skipSubscriptionOrder
            ->getskipSubscriptionOrderCollection($subscriptionId, $timeFilterby);
        
        return $subscriptionOrderCollection;
    }

    /**
     * My Account Labels
     *
     * @param string $labelCode
     * @return $this
     */

    public function getMyAccountLabels($labelCode)
    {
        return $this->recurringhelper->getAccountLabelbyCode($labelCode);
    }

    /**
     * Get subscription plan interval label
     *
     * @param string $interval
     * @return string
     */
    public function getSubscriptionIntervalLabel($interval)
    {
        return $this->recurringhelper->getPlanIntervalLabel($interval);
    }
    
    /**
     * Specific Exception
     *
     * @param string $exceptioncode
     * @return string
     */

    public function getMyAccountSpecificException($exceptioncode)
    {
        return $this->recurringhelper->getMyAccountExceptions($exceptioncode);
    }
    
    /**
     * Return Current Url
     *
     * @return string
     */
    public function getCurrentURL()
    {
        return $this->urlInterface->getCurrentUrl();
    }
  
    /**
     * Return  Order Filter URL
     *
     * @param string $timeVal
     * @return string
     */
    public function getOrderFilterURL($timeVal)
    {
        $link = $this->getHistoryPageURL();
        $paramsData = $this->getRequest()->getParams();
        if (count($paramsData)) {
            $link .= "?";
            /* Page Number */
            if (isset($paramsData['p'])) {
                $link .= "p=" . $paramsData['p'] . "&";
            }
            /* Page Limit Number */
            if (isset($paramsData['limit'])) {
                $link .= "limit=" . $paramsData['limit'] . "&";
            }
            /* Time period */
            $link .= "timeFilter=" . $timeVal . "&";
        }
        
        $link = rtrim(rtrim($link, "&"), "?");
        return $link;
    }

    /**
     * Return history URL for Filter
     *
     * @return string
     */
    public function getHistoryPageURL()
    {
        $subscriptionId = $this->getRequest()->getParam('subscription_id');
        return $this->_urlBuilder->getUrl(
            'worldpay/recurring_order/skiporders/',
            ['_secure' => true, 'subscription_id' => $subscriptionId]
        );
    }
    /**
     * Return Selected Option in Filter
     *
     * @param string $filter
     * @return bool
     */
    public function checkSelectedFilter($filter)
    {
        $selectedFilter = $this->getRequest()->getParam('timeFilter');
        if ($filter == $selectedFilter) {
            return true;
        }
        return false;
    }
    
    /**
     * Return Complete Orderl Url
     *
     * @return string
     */
    public function getCompleteOrderlUrl()
    {
        $subscriptionId = $this->getRequest()->getParam('subscription_id');
        return $this->_urlBuilder->getUrl(
            'worldpay/recurring_order/view/',
            ['_secure' => true, 'subscription_id' => $subscriptionId]
        );
    }

    /**
     * Check Order Shipment
     *
     * @param Subscription $subscription
     * @return bool
     */
    public function checkOrderShipment(SubscriptionOrder $subscription)
    {
        $orderId = $subscription->getOriginalOrderId();
        $order = $this->skipSubscriptionOrder->getOrderbyOriginalId($orderId);

        return $order->hasShipments();
    }

    /**
     * Check Order Buffer Time
     *
     * @param Subscription $subscription
     * @return bool
     */
    public function checkOrderBufferTime(SubscriptionOrder $subscription)
    {
        $createdAt = new \DateTime($subscription->getCreatedAt());
        $current = new \DateTime();
        $dateDiff = strtotime($current->format('Y-m-d')) - strtotime($createdAt->format('Y-m-d'));
        $remainigDays = floor($dateDiff/(60*60*24));
        $bufferTime = $this->recurringhelper->getRecurringOrderBufferTime();

        return ($bufferTime >= $remainigDays) ? true : false;
    }

    /**
     * Check Order already Canceled
     *
     * @param Subscription $subscription
     * @return bool
     */
    public function getNotAlreadyCanceled(SubscriptionOrder $subscription)
    {
        $orderId = $subscription->getOriginalOrderId();
        $order = $this->skipSubscriptionOrder->getOrderbyOriginalId($orderId);

        return ($order->getStatus() == 'canceled') ? true : false;
    }

    /**
     * Get order id column value
     *
     * @param Subscription $subscription
     * @return string
     */
    public function getOrderIdLabel($subscription)
    {
        $OrderIncrementId = '';
        if ($subscription->getOriginalOrderId()) {
            $OrderIncrementId = $this->getOrgIncrementOrderId($subscription->getOriginalOrderId());
            return sprintf(
                '<a href="%s" class="order-link"><span>%s</span></a>',
                $this->getUrl('sales/order/view', ['order_id' => $subscription->getOriginalOrderId()]),
                $this->escapeHtml($OrderIncrementId)
            );
        }
        return $this->escapeHtml($OrderIncrementId);
    }

    /**
     * Retrieve OrgIncrement Order Id
     *
     * @param string $orderId
     * @return string
     */
    public function getOrgIncrementOrderId($orderId)
    {
        $order = $this->orderRepository->get($orderId);
        return $order->getIncrementId();
    }
    /**
     * Prepare layout (initialise pagination block)
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->pageConfig->getTitle()->set('All Recurring Order');

        $pagination = $this->getLayout()
            ->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'subscriptions_order_pagination'
            )
            ->setCollection($this->getskipSubscriptionOrderCollection())
            ->setPath('worldpay/recurring_order/skiporders');
        $this->setChild('pagination', $pagination);
        return $this;
    }
    /**
     * Show Pagination Html
     *
     * @return string
     */
    public function getPaginationHtml()
    {
        return $this->getChildHtml('pagination');
    }
}
