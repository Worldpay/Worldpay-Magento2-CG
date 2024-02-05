<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model;

use Magento\Sales\Api\OrderRepositoryInterface;

class SubscriptionOrder extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\SubscriptionOrder
     */
    private $subscriptionOrderCollection;

    /**
     * Initialize resource model
     *
     * @return void
     */
    /**
     * @var CollectionFactory
     */
    protected $subscriptionCollectionFactory;
   
     /**
      * @var OrderRepositoryInterface
      */
    private $orderRepository;

    /**
     * @var $customerSession
     */
    protected $customerSession;

   /**
    * @var \Sapient\Worldpay\Helper\Recurring
    */
    private $recurringHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Sapient\Worldpay\Helper\Recurring $recurringhelper
     * @param CollectionFactory $subscriptionCollectionFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Sapient\Worldpay\Helper\Recurring $recurringhelper,
        \Sapient\Worldpay\Model\ResourceModel\SubscriptionOrder\CollectionFactory $subscriptionCollectionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->recurringhelper = $recurringhelper;
        $this->subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\SubscriptionOrder::class);
    }

    /**
     * Load Subscription order
     *
     * @param string $subscriptionId
     * @param string $timeFilterby
     * @return \Sapient\Worldpay\Model\subscriptionOrde
     */
    public function getsubscriptionOrderCollection($subscriptionId, $timeFilterby)
    {
        if ($this->subscriptionOrderCollection === null) {
            $this->subscriptionOrderCollection = $this->subscriptionCollectionFactory->create();
            $this->subscriptionOrderCollection
                ->addFieldToFilter("main_table.subscription_id", ['eq' => $subscriptionId])
                ->joinPlans(['interval', 'interval_amount'])
                ->joinSubscriptions(['product_name'])
                ->addFieldToFilter("main_table.status", ['eq' => 'completed'])
                ->addFieldToFilter('main_table.created_at', ['gteq'=> $timeFilterby])
                ->addCustomerIdFilter($this->customerSession->getCustomerId())
                ->addOrder('created_at', \Magento\Framework\Data\Collection::SORT_ORDER_DESC);
        }
        return $this->subscriptionOrderCollection;
    }

    /**
     * Load Order by origin order id
     *
     * @param string $orderId
     * @return orderRepository
     */
    public function getOrderbyOriginalId($orderId)
    {
        $order = $this->orderRepository->get($orderId);
        return $order;
    }

    /**
     * Return date for filter
     *
     * @param string $timeinterval
     * @return string
     */
    public function getFilterDate($timeinterval)
    {
        $period = 'm';
        if (!empty($timeinterval)) {
            $timeInt = explode('-', $timeinterval);
            $period = $timeInt[0];
        }
        switch ($period) {
            case 'm':
                $time = strtotime(time());//$time = strtotime("-6 month", time());
                $date = date('Y-01-01');
                break;
            case 'y':
                $date = date('Y-01-01');//date("Y-m-d", $time);
                break;
            default:
                $time = strtotime("-6 month", time());
                $date = date("Y-m-d", $time);
        }
        return $date;
    }
}
