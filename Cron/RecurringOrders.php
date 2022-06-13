<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Cron;

use \Magento\Framework\App\ObjectManager;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;
use Sapient\Worldpay\Model\Config\Source\SubscriptionStatus;
use Exception;

/**
 * Model for order sync status based on configuration set by admin
 */
class RecurringOrders
{

    /**
     * @var SubscriptionFactory
     */
    private $subscriptionFactory;
    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $_logger;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;
    /**
     * @var _orderId
     */
    private $_orderId;
    /**
     * @var _order
     */
    private $_order;
    /**
     * @var _paymentUpdate
     */
    private $_paymentUpdate;
    /**
     * @var _tokenState
     */
    private $_tokenState;
    /**
     * @var _newEntityId
     */
    private $_newEntityId;
    
    /**
     * @var CollectionFactory
     */
    private $subscriptionCollectionFactory;
    
    /**
     * @var CollectionFactory
     */
    private $transactionCollectionFactory;
    
    /**
     * @var CollectionFactory
     */
    private $addressCollectionFactory;
    
    /**
     * Constructor
     *
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Model\Recurring\Subscription $subscriptions
     * @param \Sapient\Worldpay\Model\Recurring\Subscription\Transactions $recurringTransactions
     * @param \Sapient\Worldpay\Model\Recurring\Subscription\Address $subscriptionAddress
     * @param Sapient\Worldpay\Helper\Recurring $recurringhelper
     * @param SubscriptionFactory $subscriptionFactory
     * @param \Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory $transactionsFactory
     * @param \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\SavedToken $worldpaytoken,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Sapient\Worldpay\Model\Recurring\Subscription $subscriptions,
        \Sapient\Worldpay\Model\Recurring\Subscription\Transactions $recurringTransactions,
        \Sapient\Worldpay\Model\Recurring\Subscription\Address $subscriptionAddress,
        \Sapient\Worldpay\Helper\Recurring $recurringhelper,
        SubscriptionFactory $subscriptionFactory,
        \Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory $transactionsFactory,
        \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
    ) {
        $this->_logger = $wplogger;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->worldpayhelper = $worldpayhelper;
        $this->paymentservice = $paymentservice;
        $this->orderservice = $orderservice;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->worldpaytoken = $worldpaytoken;
        $this->subscriptionCollectionFactory = $subscriptions;
        $this->transactionCollectionFactory = $recurringTransactions;
        $this->addressCollectionFactory = $subscriptionAddress;
        $this->recurringhelper = $recurringhelper;
        $this->transactionFactory = $transactionsFactory;
        $this->planFactory = $planFactory;
    }

    /**
     * Get the list of orders to be sync the status
     */
    public function execute()
    {
        $this->_logger->info('Recurring Orders transactions executed on - '.date('Y-m-d H:i:s'));
        $recurringOrderIds = $this->getRecurringOrderIds();
        if (!empty($recurringOrderIds)) {
            foreach ($recurringOrderIds as $recurringOrder) {
                $orderData = $paymentData = [];
                $recurringOrderData = $recurringOrder;
                $totalInfo = $this->getTotalDetails($recurringOrderData);
                if ($totalInfo && isset($totalInfo['tokenData'][0])) {
                    $orderDetails = $totalInfo['orderDetails'][0];
                    $addressDetails['shipping'] = $totalInfo['addressData'][1];
                    $addressDetails['billing'] = $totalInfo['addressData'][0];
                    $subscriptionDetails = $totalInfo['subscriptionData'][0];
                    $tokenDetails = $totalInfo['tokenData'][0];
                    $orderData = [
                    'currency_id'       => $orderDetails['order_currency_code'],
                    'item_price'        => $subscriptionDetails['item_price'],
                    'email'             => $subscriptionDetails['customer_email'],
                    'customer_id'       => $subscriptionDetails['customer_id'],
                    'shipping_method'   => $subscriptionDetails['shipping_method'],
                    'store_id'          => $subscriptionDetails['store_id'],
                    'store_name'        => $subscriptionDetails['store_name'],
                    'product_id'        => $subscriptionDetails['product_id'],
                    'product_sku'        => $subscriptionDetails['product_sku'],
                    'qty'               => 1
                    ];

                    $shipping = $addressDetails['shipping'];
                    $customerId = $subscriptionDetails['customer_id'];
                    $orderData['shipping_address'] = $this->getShippingAddress($shipping, $customerId);
                    $orderData['billing_address'] = $this->getBillingAddress($addressDetails['billing']);
                    $paymentType = "worldpay_cc";

                    $paymentData['paymentMethod']['method'] = $paymentType;
                    $paymentData['paymentMethod']['additional_data'] = $this->getAdditionalData($tokenDetails);
                    $paymentData['billing_address'] = $this->getBillingAddress($addressDetails['billing']);
                    try {
                        $result = $subscriptionDetails['original_order_id'];
                        $currentStatus = $this->updateRecurringTransactions($result, $recurringOrderData['entity_id']);
                        if ($currentStatus == 'completed') {
                            $result = $this->recurringhelper->createMageOrder($orderData, $paymentData);
                            $this->updateRecurringTransOrderId($result, $this->_newEntityId);
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
     * Update recurring order Transactionsfor next order
     *
     * @param Int $orderId
     * @param Int $recurringId
     */
    public function updateRecurringTransOrderId($orderId, $recurringId)
    {
        $transactionDetails = $this->transactionFactory->create()->loadById($recurringId);
        if ($transactionDetails) {
               $transactionDetails->setOriginalOrderId($orderId)->save();
        }
    }
    
    /**
     * Get the list of orders to be Sync
     *
     * @return array List of order IDs
     */
    public function getRecurringOrderIds()
    {
        $curdate = date("Y-m-d");
        $fiveDays = strtotime(date("Y-m-d", strtotime($curdate)) . " +5 day");
        $cronDate = date('Y-m-d', $fiveDays);
        $result = $this->transactionCollectionFactory->getCollection()
                ->addFieldToFilter('status', ['eq' => 'active'])
                ->addFieldToFilter('recurring_date', ['gteq' => $curdate])
                ->addFieldToFilter('recurring_date', ['lteq' => $cronDate])->getData();
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
            $data['addressData'] = $this->getAddressInfo($recurringOrderData['subscription_id']);
            $data['orderDetails'] = $this->getOrderInfo($recurringOrderData['recurring_order_id']);
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
            $result = $this->subscriptionCollectionFactory->getCollection()
                ->addFieldToFilter('subscription_id', ['eq' => trim($subscriptionId)])->getData();
            return $result;
        }
    }
    /**
     * Get AddressInfo
     *
     * @param Int $subscriptionId
     */

    public function getAddressInfo($subscriptionId)
    {
        if ($subscriptionId) {
            $result = $this->addressCollectionFactory->getCollection()
                ->addFieldToFilter('subscription_id', ['eq' => trim($subscriptionId)])->getData();
            return $result;
        }
    }
    
    /**
     * Get the list of orders to be Sync
     *
     * @param Int $orderId
     * @return array List of order IDs
     */
    public function getOrderInfo($orderId)
    {
        $orders = $this->getOrderCollectionFactory()->create();
        $orders->distinct(true);
        $orders->addFieldToFilter('main_table.entity_id', ['eq' => trim($orderId)]);
        $orderIds = $orders->getData();
        return $orderIds;
    }

    /**
     * Get Order Collection Factory
     *
     * @return CollectionFactoryInterface
     */
    private function getOrderCollectionFactory()
    {
        if ($this->orderCollectionFactory === null) {

            $this->orderCollectionFactory = ObjectManager::getInstance()->get(CollectionFactoryInterface::class);
        }
        return $this->orderCollectionFactory;
    }
    
    /**
     * Frame Shipping Address
     *
     * @param string $addressDetails
     * @param Int $customerId
     * @return array
     */
    private function getShippingAddress($addressDetails, $customerId)
    {
        $shippingAddress = [
                            'region'        => $addressDetails['region'],
                            'region_id'     => $addressDetails['region_id'],
                            'country_id'    => $addressDetails['country_id'],
                            'street'        => [$addressDetails['street']],
                            'postcode'      => $addressDetails['postcode'],
                            'city'          => $addressDetails['city'],
                            'firstname'     => $addressDetails['firstname'],
                            'lastname'      => $addressDetails['lastname'],
                            'customer_id'   => $customerId,
                            'email'         => $addressDetails['email'],
                            'telephone'     => $addressDetails['telephone'],
                            'fax'           => $addressDetails['fax']
                        ];
        return $shippingAddress;
    }
    
    /**
     * Frame Billing Address
     *
     * @param array $addressDetails
     * @return array
     */
    private function getBillingAddress($addressDetails)
    {
        $billingAddress = [
                            'region'        => $addressDetails['region'],
                            'region_id'     => $addressDetails['region_id'],
                            'country_id'    => $addressDetails['country_id'],
                            'street'        => [$addressDetails['street']],
                            'postcode'      => $addressDetails['postcode'],
                            'city'          => $addressDetails['city'],
                            'firstname'     => $addressDetails['firstname'],
                            'lastname'      => $addressDetails['lastname'],
                            'email'         => $addressDetails['email'],
                            'telephone'     => $addressDetails['telephone'],
                            'fax'           => $addressDetails['fax']
                        ];
        return $billingAddress;
    }
    
    /**
     * Frame Payment Additional data
     *
     * @param array $tokenDetails
     * @return array
     */
    private function getAdditionalData($tokenDetails)
    {
        $additionalData = [
                            'cc_cid' => '',
                            'cc_type' => 'savedcard',
                            'cc_number' => '',
                            'cc_name' => '',
                            'save_my_card' => '',
                            'cse_enabled' => '',
                            'encryptedData' => '',
                            'tokenCode' => $tokenDetails['token_code'],
                            'saved_cc_cid' => '',
                            'isSavedCardPayment' => 1,
                            'tokenization_enabled' => 1,
                            'isRecurringOrder' => 1,
                            'stored_credentials_enabled' => 1,
                            'subscriptionStatus' => ''
                        ];
        return $additionalData;
    }
    
    /**
     * Update recurring order Transactionsfor next order
     *
     * @param Int $orderId
     * @param Int $recurringId
     */
    public function updateRecurringTransactions($orderId, $recurringId)
    {
        $transactionDetails = $this->transactionFactory->create()->loadById($recurringId);
        return $this->insertNewTransaction($transactionDetails, $orderId);
    }
    /**
     * Update recurring order Transactionsfor next order
     *
     * @param array $transactionDetails
     * @param Int $orderId
     */

    public function insertNewTransaction($transactionDetails, $orderId)
    {
        if ($transactionDetails) {
            $date = $transactionDetails->getRecurringDate();
            $week = strtotime(date("Y-m-d", strtotime($date)) . " +1 week");
            $monthdate = strtotime(date("Y-m-d", strtotime($date)) . " +1 month");
            $tmonthsdate = strtotime(date("Y-m-d", strtotime($date)) . " +3 month");
            $sixmonthsdate = strtotime(date("Y-m-d", strtotime($date)) . " +6 month");
            $yeardate = strtotime(date("Y-m-d", strtotime($date)) . " +12 month");
            
            $plan = $this->planFactory->create()->loadById($transactionDetails->getPlanId());
            $planInterval = $plan->getInterval();
            
            $recurringOrderId = $transactionDetails->getRecurringOrderId();
            
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
            if (!$this->recurringhelper->getSubscriptionValue('worldpay/subscriptions/endDate')
                    || ($transactionDetails->getRecurringEndDate()
                    && $recurringDate <= $transactionDetails->getRecurringEndDate())) {
                $transactions = $this->transactionFactory->create();
                $transactions->setOriginalOrderId($orderId);
                $transactions->setCustomerId($transactionDetails->getCustomerId());
                $transactions->setPlanId($transactionDetails->getPlanId());
                $transactions->setSubscriptionId($transactionDetails->getSubscriptionId());
                $transactions->setRecurringDate($recurringDate);
                if (!$this->recurringhelper->getSubscriptionValue('worldpay/subscriptions/endDate')) {
                    $transactions->setRecurringEndDate($recurringDate);
                } else {
                    $transactions->setRecurringEndDate($transactionDetails->getRecurringEndDate());
                }
                $transactions->setStatus('active');
                $transactions->setRecurringOrderId($recurringOrderId);
                $transactions->setWorldpayTokenId($transactionDetails->getWorldpayTokenId());
                $transactions->setWorldpayOrderId($transactionDetails->getWorldpayOrderId());
                $transactions->save();
                $this->_newEntityId = $transactions->getEntityId();
                $transactionDetails->setStatus('completed')->save();
                return 'completed';
            } else {
                $subscription = $this->subscriptionFactory->create()->load($transactionDetails->getSubscriptionId());
                $subscription->setStatus(SubscriptionStatus::EXPIRED)->save();
                $transactionDetails->setStatus('expired')->save();
                return 'expired';
                
            }
        }
    }
}
