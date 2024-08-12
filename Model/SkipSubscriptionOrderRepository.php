<?php
/**
 * @copyright 2023 Sapient
 */
namespace Sapient\Worldpay\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Sapient\Worldpay\Api\Data\SkipSubscriptionOrderInterface;
use Sapient\Worldpay\Api\SkipSubscriptionOrderRepositoryInterface;
use Sapient\Worldpay\Model\ResourceModel\SkipSubscriptionOrder;
use Sapient\Worldpay\Model\ResourceModel\SkipSubscriptionOrder\CollectionFactory;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;

/**
 * Class EditSubscriptionHistoryRepository
 *
 */
class SkipSubscriptionOrderRepository implements SkipSubscriptionOrderRepositoryInterface
{
    /**
     * @var  SkipSubscriptionOrderFactory
     */
    private $skipOrderFactory;

    /**
     * @var SkipSubscriptionOrder
     */
    private $skipSubscriptionOrder;

    /**
     * @var SkipSubscriptionOrderResource
     */
    private $skipOrderResource;

    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\SkipSubscriptionOrder\CollectionFactory
     */
    private $skipOrderCollectionFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\SubscriptionOrder\CollectionFactory
     */
    protected $subscriptionCollectionFactory;

    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\SubscriptionOrder
     */
    private $subscriptionOrderCollection;
  
    /**
     * @var \Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory
     */
    protected $transactionFactory;

    /**
     * @var \Sapient\Worldpay\Model\Recurring\PlanFactory
     */
    protected $planFactory;

    /**
     * @var \Sapient\Worldpay\Helper\SkipOrderEmail
     */
    protected $skipOrderEmail;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    protected $recurringhelper;

    /**
     * Constructor
     *
     * @param SkipSubscriptionOrderFactory $skipOrderFactory
     * @param SkipSubscriptionOrder $skipOrderResource
     * @param CollectionFactory $skipOrderCollectionFactory
     * @param \Sapient\Worldpay\Model\ResourceModel\SubscriptionOrder\CollectionFactory $subscriptionCollectionFactory
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory $transactionsFactory
     * @param \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
     * @param \Sapient\Worldpay\Helper\SkipOrderEmail $skipOrderEmail
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Recurring $recurringhelper
     */
    public function __construct(
        SkipSubscriptionOrderFactory $skipOrderFactory,
        SkipSubscriptionOrder $skipOrderResource,
        CollectionFactory $skipOrderCollectionFactory,
        \Sapient\Worldpay\Model\ResourceModel\SubscriptionOrder\CollectionFactory $subscriptionCollectionFactory,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Customer\Model\Session $customerSession,
        \Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory $transactionsFactory,
        \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory,
        \Sapient\Worldpay\Helper\SkipOrderEmail $skipOrderEmail,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Recurring $recurringhelper
    ) {
        $this->skipOrderFactory = $skipOrderFactory;
        $this->skipOrderResource = $skipOrderResource;
        $this->skipOrderCollectionFactory = $skipOrderCollectionFactory;
        $this->subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->customerSession = $customerSession;
        $this->transactionFactory = $transactionsFactory;
        $this->planFactory = $planFactory;
        $this->skipOrderEmail = $skipOrderEmail;
        $this->wplogger = $wplogger;
        $this->recurringhelper = $recurringhelper;
    }

    /**
     * Get Skipked subscription Order By Id
     *
     * @param int $id
     * @return \Sapient\Worldpay\Api\Data\StudentInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id)
    {
        $skipOrderHistory = $this->skipOrderFactory->create();
        $this->skipOrderResource->load($skipOrderHistory, $id);
        if (!$skipOrderHistory->getId()) {
            throw new NoSuchEntityException(__('Unable to find History with ID "%1"', $id));
        }
        return $skipOrderHistory;
    }

    /**
     * Save Skipked subscription Order
     *
     * @param SkipSubscriptionOrderInterface $skipSubscriptionOrder
     * @return mixed
     */
    public function save(SkipSubscriptionOrderInterface $skipSubscriptionOrder): mixed
    {
        $this->skipOrderResource->save($skipSubscriptionOrder);
        return $skipSubscriptionOrder;
    }

    /**
     * Update Skipked subscription Order
     *
     * @param mixed $params
     * @return mixed
     */
    public function updateSkipHistory($params)
    {
        
        $subscriptionId = $params['subscriptionId'];
        $recurringDate  = date('Y-m-d', strtotime($params['nextOrder']));
        $customer = $this->customerSession->getCustomer();
        $customerId = $customer->getId();
        $customerName = $customer->getName();
        $subscriptionOrderData = $this->getsubscriptionOrderCollection(
            $subscriptionId,
            $customerId,
            $recurringDate
        );
        $recurringId = $subscriptionOrderData->getId();
        $newrecucceringDate = $this->updateSkipOrderDate($recurringId, $recurringDate);
        $skipOrderHistory = $this->skipOrderFactory->create();
        try {
            $orderId = $subscriptionOrderData->getOriginalOrderId();
            $skipOrderParams['orderId'] = $subscriptionOrderData->getOriginalOrderIncrementId();
            $skipOrderParams['customerName'] = $customerName;
            $skipOrderParams['recurring_date'] = $recurringDate;
            $skipOrderParams['order_data']   = $this->recurringhelper->getOrderDetails($orderId);
            $skipOrderHistory->setSubscriptionId($subscriptionId);
            $skipOrderHistory->setCustomerId($customerId);
            $skipOrderHistory->setNewRecurringDate($newrecucceringDate);
            $skipOrderHistory->setOldRecurringDate($recurringDate);
            $skipOrderHistory->save();
            $this->skipOrderEmail->sendSkipOrderEmail($skipOrderParams);

            $this->wplogger->info(__('Skipped and next order date is: '). $newrecucceringDate);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Load Subscription order collection
     *
     * @param string $subscriptionId
     * @param string $customerId
     * @param string $recurringDate
     *
     * @return \Sapient\Worldpay\Model\subscriptionOrde
     */
    public function getsubscriptionOrderCollection($subscriptionId, $customerId, $recurringDate)
    {
        if ($this->subscriptionOrderCollection === null) {
            $this->subscriptionOrderCollection = $this->subscriptionCollectionFactory->create();
            $this->subscriptionOrderCollection
                    ->addFieldToFilter("subscription_id", ['eq' => $subscriptionId])
                    ->addFieldToFilter("customer_id", ['eq' => $customerId])
                    ->addFieldToFilter('status', ['eq' => 'active']);
        }
        return $this->subscriptionOrderCollection->getFirstItem();
    }

    /**
     * Update Subscription order date in database
     *
     * @param int $id
     * @param string $oldRecurringDate
     * @return string
     */
    public function updateSkipOrderDate($id, $oldRecurringDate)
    {
        $transactionDetails = $this->transactionFactory->create()->loadById($id);

        if ($transactionDetails) {
            $date = $transactionDetails->getRecurringDate();
            $recurringEndDate = $transactionDetails->getRecurringEndDate();
            $week = strtotime(date("Y-m-d", strtotime($date)) . " +1 week");
            $monthdate = strtotime(date("Y-m-d", strtotime($date)) . " +1 month");
            $tmonthsdate = strtotime(date("Y-m-d", strtotime($date)) . " +3 month");
            $sixmonthsdate = strtotime(date("Y-m-d", strtotime($date)) . " +6 month");
            $yeardate = strtotime(date("Y-m-d", strtotime($date)) . " +12 month");
            
            $plan = $this->planFactory->create()->loadById($transactionDetails->getPlanId());
            $planInterval = $plan->getInterval();

            if ($planInterval == 'WEEKLY') {
                $recurringDate = date('Y-m-d', $week);
            } elseif ($planInterval == 'MONTHLY') {
                $recurringDate = date('Y-m-d', $monthdate);
            } elseif ($planInterval == 'QUARTERLY') {
                $recurringDate = date('Y-m-d', $tmonthsdate);
            } elseif ($planInterval == 'SEMIANNUAL') {
                $recurringDate = date('Y-m-d', $sixmonthsdate);
            } elseif ($planInterval == 'ANNUAL') {
                $recurringDate = date('Y-m-d', $yeardate);
            }
            try {
                if (strtotime($recurringDate) > strtotime($recurringEndDate)) {
                    $this->wplogger->info(__('Next order date is greater then End date: '). $recurringDate);
                    $transactionDetails->setRecurringDate($recurringEndDate);
                    $transactionDetails->setStatus('completed');
                    $transactionDetails->save();
                    return $oldRecurringDate;
                }
                $transactionDetails->setRecurringDate($recurringDate);
                $transactionDetails->save();
                return $recurringDate;

            } catch (Exception $e) {
                $this->wplogger
                    ->info(__('Skipped order date not updated: '). $e->getMessage());
            }
        }
    }
}
