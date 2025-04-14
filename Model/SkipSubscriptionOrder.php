<?php
/**
 * @copyright 2023 Sapient
 */
namespace Sapient\Worldpay\Model;

use Magento\Framework\Model\AbstractModel;
use Sapient\Worldpay\Api\Data\SkipSubscriptionOrderInterface;

/**
 * Class EditSubscriptionHistory
 */
class SkipSubscriptionOrder extends AbstractModel implements SkipSubscriptionOrderInterface
{
    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\SubscriptionOrder
     */
    private $skipSubscriptionOrderCollection;

    /**
     * Initialize resource model
     *
     * @return void
     */
    /**
     * @var CollectionFactory
     */
    protected $skipSubcriptionCollectionFactory;
   
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
     * @param CollectionFactory $skipSubcriptionCollectionFactory
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
        \Sapient\Worldpay\Model\ResourceModel\SkipSubscriptionOrder\CollectionFactory $skipSubcriptionCollectionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Customer\Model\Session $customerSession,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->recurringhelper = $recurringhelper;
        $this->skipSubcriptionCollectionFactory = $skipSubcriptionCollectionFactory;
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
        /**
         * Initialize resource model
         *
         * @return void
         */
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\SkipSubscriptionOrder::class);
    }
    /**
     * Load Subscription order
     *
     * @param string $subscriptionId
     * @param string $timeFilterby
     *
     * @return \Sapient\Worldpay\Model\skipSubscriptionOrderCollection
     */
    public function getskipSubscriptionOrderCollection($subscriptionId, $timeFilterby)
    {
        if ($this->skipSubscriptionOrderCollection === null) {
            $this->skipSubscriptionOrderCollection = $this->skipSubcriptionCollectionFactory->create();
            $this->skipSubscriptionOrderCollection
                ->addFieldToFilter("main_table.subscription_id", ['eq' => $subscriptionId])
                ->joinSubscriptions(['product_name'])
                ->joinPlans(['interval', 'interval_amount'])
                ->addFieldToFilter('main_table.created_at', ['gteq'=> $timeFilterby])
                ->addCustomerIdFilter($this->customerSession->getCustomerId())
                ->addOrder('created_at', \Magento\Framework\Data\Collection::SORT_ORDER_DESC);
        }
        return $this->skipSubscriptionOrderCollection;
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

    /**
     * Get entityId
     */
    public function getId()
    {
        return parent::getData(self::ENTITY_ID);
    }

    /**
     * Set entityId
     *
     * @param int $entityId
     */
    public function setId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get SubscriptionId
     */
    public function getSubscriptionId()
    {
        return parent::getData(self::SUBSCRIPTION_ID);
    }

    /**
     * Set SubscriptionId
     *
     * @param int $subscriptionId
     */
    public function setSubscriptionId($subscriptionId)
    {
        return $this->setData(self::SUBSCRIPTION_ID, $subscriptionId);
    }

    /**
     * Get CustomerId
     */
    public function getCustomerId()
    {
        return parent::getData(self::CUSTOMER_ID);
    }

    /**
     * Set CustomerId
     *
     * @param int $customerId
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get IsSkipped
     */
    public function getIsSkipped()
    {
        return parent::setIsSkipped(self::IS_SKIPPED);
    }

    /**
     * Set IsSkipped
     *
     * @param bool $isSkipped
     */
    public function setIsSkipped($isSkipped)
    {
        return $this->setData(self::IS_SKIPPED, $isSkipped);
    }

    /**
     * Get NewRecurringDate
     */
    public function getNewRecurringDate()
    {
        return $this->setData(self::NEW_RECURRING_DATE);
    }

    /**
     * Set NewRecurringDate
     *
     * @param string $newRecurringDate
     * return mixed
     */
    public function setNewRecurringDate($newRecurringDate)
    {
        return $this->setData(self::NEW_RECURRING_DATE, $newRecurringDate);
    }

    /**
     * Get OldRecurringDate
     */
    public function getOldRecurringDate()
    {
        return $this->setData(self::OLD_RECURRING_DATE);
    }

    /**
     * Set OldRecurringDate
     *
     * @param string $oldRecurringDate
     */
    public function setOldRecurringDate($oldRecurringDate)
    {
        return $this->setData(self::OLD_RECURRING_DATE, $oldRecurringDate);
    }

    /**
     * Get Created Date
     */
    public function getCreatedAt()
    {
        return $this->setData(self::CREATED_AT);
    }

    /**
     * Set Created Date
     *
     * @param string $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get updated date
     *
     * @param string $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->setData(self::UPDATED_AT);
    }

    /**
     * Set updatedAt
     *
     * @param string $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
