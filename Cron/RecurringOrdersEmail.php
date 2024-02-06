<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Cron;

use \Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;

/**
 * Model for order sync status based on configuration set by admin
 */
class RecurringOrdersEmail
{
    public const EMAIL_NOTIFICATION_DAYS = 9;
    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $_logger;
   
    /**
     * @var CollectionFactory
     */
    private $subscriptionCollectionFactory;
    
    /**
     * @var CollectionFactory
     */
    private $transactionCollectionFactory;

    /**
     * @var \Sapient\Worldpay\Helper\SendRecurringOrdersEmail
     */
    protected $recurringOrdersEmail;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Sapient\Worldpay\Model\SavedToken
     */
    protected $worldpaytoken;
    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    protected $recurringhelper;
    /**
     * @var \Sapient\Worldpay\Model\Recurring\PlanFactory
     */
    protected $planFactory;
    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Recurring\Subscription $subscriptions
     * @param \Sapient\Worldpay\Model\Recurring\Subscription\Transactions $recurringTransactions
     * @param \Sapient\Worldpay\Helper\SendRecurringOrdersEmail $recurringOrdersEmail
     * @param \Magento\Store\Model\StoreManagerInterface $_storeManager
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken
     * @param \Sapient\Worldpay\Helper\Recurring $recurringhelper
     * @param \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Recurring\Subscription $subscriptions,
        \Sapient\Worldpay\Model\Recurring\Subscription\Transactions $recurringTransactions,
        \Sapient\Worldpay\Helper\SendRecurringOrdersEmail $recurringOrdersEmail,
        \Magento\Store\Model\StoreManagerInterface $_storeManager,
        \Sapient\Worldpay\Model\SavedToken $worldpaytoken,
        \Sapient\Worldpay\Helper\Recurring $recurringhelper,
        \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
    ) {
        $this->_logger = $wplogger;
        $this->subscriptionCollectionFactory = $subscriptions;
        $this->transactionCollectionFactory = $recurringTransactions;
        $this->recurringOrdersEmail = $recurringOrdersEmail;
        $this->_storeManager = $_storeManager;
        $this->worldpaytoken = $worldpaytoken;
        $this->recurringhelper = $recurringhelper;
        $this->planFactory = $planFactory;
    }

    /**
     * Get the list of orders to be sync the status
     */
    public function execute()
    {
        $this->_logger->info('Recurring Orders Email Notificaion executed on - '.date('Y-m-d H:i:s'));
        $recurringOrderIds = $this->getRecurringOrders();
        if (!empty($recurringOrderIds)) {
            foreach ($recurringOrderIds as $recurringOrder) {
                $orderData = $paymentData = [];
                $recurringOrderData = $recurringOrder;
                $totalInfo = $this->getTotalDetails($recurringOrderData);
                if ($totalInfo) {
                    try {
                        $subscriptionDetails = $totalInfo['subscriptionData'];
                        $orderId = $subscriptionDetails->getOriginalOrderId();
                        $interval = $this->getPlanInterval($subscriptionDetails->getPlanId());
                        if (($interval) && ($interval != 'WEEKLY')) {
                            $viewOrderUrl = $this->_storeManager->getStore()
                                        ->getBaseUrl().'worldpay/recurring/edit/subscription_id/'.$subscriptionDetails
                                        ->getSubscriptionId();
                            $orderData = [
                            'email'          => [$subscriptionDetails->getCustomerEmail()],
                            'order_id'       => $subscriptionDetails->getOriginalOrderIncrementId(),
                            'billing_name'   => $subscriptionDetails->getBillingName(),
                            'recurring_date' => $recurringOrderData['recurring_date'],
                            'product_name'   => $subscriptionDetails->getProductName(),
                            'view_order_url' => $viewOrderUrl,
                            'mail_template'  => $this->recurringhelper->getRecurringOrderReminderEmail(),
                            'expired_msg'    => $this->getCartExpiryMessage($totalInfo),
                            'order_data'     => $this->recurringhelper->getOrderDetails($orderId)
                            ];
                            $this->recurringOrdersEmail->sendRecurringOrdersEmail($orderData);
                        }
                    } catch (Exception $e) {
                        $this->_logger->error($e->getMessage());
                    }
                }
            }
        }
        return $this;
    }
    
    /**
     * Get the list of orders for send Email Notificaion
     *
     * @return array List of orders
     */
    public function getRecurringOrders()
    {
        $curdate = date("Y-m-d");
        $days = $this->recurringhelper->getRecurringOrderBufferTime() + self::EMAIL_NOTIFICATION_DAYS;
        $orderDate = strtotime(date("Y-m-d", strtotime($curdate)) . " +".$days." day");
        $recurringDate = date('Y-m-d', $orderDate);
        $result = $this->transactionCollectionFactory->getCollection()
                ->addFieldToFilter('status', ['eq' => 'active'])
                ->addFieldToFilter('recurring_date', ['eq' => $recurringDate])->getData();
        return $result;
    }

    /**
     * Get Total Details
     *
     * @param array $recurringOrderData
     */
    public function getTotalDetails($recurringOrderData)
    {
        $data = [];
        if ($recurringOrderData) {
            $tokenId = $recurringOrderData['worldpay_token_id'];
            $data['tokenData'] = $this->getTokenInfo($tokenId, $recurringOrderData['customer_id']);
            $data['subscriptionData'] = $this->getSubscriptionsInfo($recurringOrderData['subscription_id']);
        }
        return $data;
    }
    
    /**
     * Get Total Details
     *
     * @param Int $tokenId
     * @param Int $customerId
     */

     public function getTokenInfo($tokenId, $customerId)
     {
         $curdate = date("Y-m-d");
         if ($tokenId) {
             $result = $this->worldpaytoken->getCollection()
                 ->addFieldToFilter('id', ['eq' => trim($tokenId)])
                 ->addFieldToFilter('customer_id', ['eq' => trim($customerId)])
                 ->addFieldToFilter('token_expiry_date', ['gteq' => $curdate])->getData();
             return $result;
         }
     }
    /**
     * Get SubscriptionsInfo
     *
     * @param Int $subscriptionId
     */
    public function getSubscriptionsInfo($subscriptionId)
    {
        if ($subscriptionId) {
            $collection = $this->subscriptionCollectionFactory->getCollection()
                ->addFieldToFilter('subscription_id', ['eq' => trim($subscriptionId)]);
            if ($collection->getSize()) {
                return $collection->getFirstItem();
            }
            return false;
        }
    }

    /**
     * Get Plan Interval
     *
     * @param Int $planId
     */
    public function getPlanInterval($planId)
    {
        if ($planId) {
            $plan = $this->planFactory->create()->loadById($planId);
            return $plan->getInterval();
        }
        return false;
    }

    /**
     * Get Cart Expiry Message Interval
     *
     * @param array $totalInfo
     */
    public function getCartExpiryMessage($totalInfo)
    {
        $expiredMsg = '';
        if (isset($totalInfo['tokenData'][0])) {
            $currentDate = date("Y-m-d");
            $currentMonth = date("m") + 1;
            $currentYear = date("Y");

            $expiryMonth = sprintf("%02d",$totalInfo['tokenData'][0]['card_expiry_month']);
            $expiryYear = $totalInfo['tokenData'][0]['card_expiry_year'];
            $expiry = date($expiryYear.'-'.$expiryMonth.'-01');

            if ($expiry < $currentDate) {
                $expiredMsg = 'Your card is expired. Plese add/update card details.';
            } else if ($expiryYear === $currentYear && $expiryMonth == $currentMonth) {
                $expiredMsg = 'Your card is going to expire, please check the card or update the card details';
            }
        }
        return $expiredMsg;
    }
}
