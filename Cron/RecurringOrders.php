<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Cron;
use \Magento\Framework\App\ObjectManager;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;

/**
 * Model for order sync status based on configuration set by admin
 */
class RecurringOrders {

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $_logger;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;
    private $_orderId;
    private $_order;
    private $_paymentUpdate;
    private $_tokenState;
    
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
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice,
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken,
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice,
     * @param \Sapient\Worldpay\Model\Recurring\Subscription $subscriptions,
     * @param \Sapient\Worldpay\Model\Recurring\Subscription\Transactions $recurringTransactions,
     * @param \Sapient\Worldpay\Model\Recurring\Subscription\Address $subscriptionAddress
     */
    public function __construct(JsonFactory $resultJsonFactory,
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
        \Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory $transactionsFactory,
        \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
    ) {
        $this->_logger = $wplogger;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->worldpayhelper = $worldpayhelper;
        $this->paymentservice = $paymentservice;
        $this->orderservice = $orderservice;
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
                $orderData = $paymentData = array();
                $recurringOrderData = $recurringOrder;
                $totalInfo = $this->getTotalDetails($recurringOrderData);
                if($totalInfo){
                    $orderDetails = $totalInfo['orderDetails'][0];
                    $addressDetails['shipping'] = $totalInfo['addressData'][1];
                    $addressDetails['billing'] = $totalInfo['addressData'][0];
                    $subscriptionDetails = $totalInfo['subscriptionData'][0];
                    if(isset($totalInfo['tokenData'][0])){
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
                        $curdate = date("Y-m-d");
                        $recurringDate = $recurringOrderData['recurring_date'];
                        $fiveDays = strtotime(date("Y-m-d", strtotime($curdate)) . " +5 day");
                        $cronDate = date('Y-m-d', $fiveDays);
                        $date1 = $curdate;
                        $date2 = $cronDate;

                        if (($recurringDate >= $date1) && ($recurringDate <= $date2)){
                        //$subscriptionDetails['created_at'];
                        //if($subscriptionDetails['created_at'] >= $curdate){
                            $orderData['shipping_address'] = $this->getShippingAddress($addressDetails['shipping'],$subscriptionDetails['customer_id']);
                            $orderData['billing_address'] = $this->getBillingAddress($addressDetails['billing']);
                            $paymentType = "worldpay_cc";

                            $paymentData['paymentMethod']['method'] = $paymentType;
                            $paymentData['paymentMethod']['additional_data'] = $this->getAdditionalData($tokenDetails);
                            $paymentData['billing_address'] = $this->getBillingAddress($addressDetails['billing']);
                            //$paymentData['shipping_address'] = $this->getBillingAddress($addressDetails['shipping']);
                            try {
                                $result = $this->recurringhelper->createMageOrder($orderData,$paymentData);
                                $this->_logger->info(print_r($result,true));
                                $this->updateRecurringTransactions($result, $recurringOrderData['entity_id']);
                            } catch (Exception $e) {
                                $this->_logger->error($e->getMessage());
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }
    
    /**
     * Get the list of orders to be Sync
     *
     * @return array List of order IDs
     */
    public function getRecurringOrderIds()
    {
        $result = $this->transactionCollectionFactory->getCollection()
                ->addFieldToFilter('status', array('eq' => 'active'))->getData();
        return $result;
    }
    
    public function getTotalDetails($recurringOrderData){
        $data = array();
        if($recurringOrderData){
            $data['tokenData'] = $this->getTokenInfo($recurringOrderData['worldpay_token_id'], $recurringOrderData['customer_id']);
            $data['subscriptionData'] = $this->getSubscriptionsInfo($recurringOrderData['subscription_id']);
            $data['addressData'] = $this->getAddressInfo($recurringOrderData['subscription_id']);
            $data['orderDetails'] = $this->getOrderInfo($recurringOrderData['recurring_order_id']);
        }
        return $data;
    }
    
    public function getTokenInfo($tokenId, $customerId){
        if($tokenId){
            $result = $this->worldpaytoken->getCollection()
                ->addFieldToFilter('id', array('eq' => trim($tokenId)))
                ->addFieldToFilter('customer_id', array('eq' => trim($customerId)))
                ->getData();
            return $result;
        }
    }
    
    public function getSubscriptionsInfo($subscriptionId){
        if($subscriptionId){
            $result = $this->subscriptionCollectionFactory->getCollection()
                ->addFieldToFilter('subscription_id', array('eq' => trim($subscriptionId)))->getData();
            return $result;
        }
    }
    
    public function getAddressInfo($subscriptionId){
        if($subscriptionId){
            $result = $this->addressCollectionFactory->getCollection()
                ->addFieldToFilter('subscription_id', array('eq' => trim($subscriptionId)))->getData();
            return $result;
        }
    }
    
    /**
     * Get the list of orders to be Sync
     *
     * @return array List of order IDs
     */
    public function getOrderInfo($orderId)
    {
        $orders = $this->getOrderCollectionFactory()->create();
        $orders->distinct(true);
        $orders->addFieldToFilter('main_table.entity_id', array('eq' => trim($orderId)));
        $orderIds = $orders->getData();
        return $orderIds;
    }

    /**
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
     * @return array
     */
    private function getShippingAddress($addressDetails, $customerId)
    {
        $shippingAddress = array(
                            'region'        => $addressDetails['region'],
                            'region_id'     => $addressDetails['region_id'],
                            'country_id'    => $addressDetails['country_id'],
                            'street'        => array($addressDetails['street']),
                            'postcode'      => $addressDetails['postcode'],
                            'city'          => $addressDetails['city'],
                            'firstname'     => $addressDetails['firstname'],
                            'lastname'      => $addressDetails['lastname'],
                            'customer_id'   => $customerId,
                            'email'         => $addressDetails['email'],
                            'telephone'     => $addressDetails['telephone'],
                            'fax'           => $addressDetails['fax']
                        );
        return $shippingAddress;
    }
    
    /**
     * Frame Billing Address
     * @return array
     */
    private function getBillingAddress($addressDetails)
    {
        $billingAddress = array(
                            'region'        => $addressDetails['region'],
                            'region_id'     => $addressDetails['region_id'],
                            'country_id'    => $addressDetails['country_id'],
                            'street'        => array($addressDetails['street']),
                            'postcode'      => $addressDetails['postcode'],
                            'city'          => $addressDetails['city'],
                            'firstname'     => $addressDetails['firstname'],
                            'lastname'      => $addressDetails['lastname'],
                            'email'         => $addressDetails['email'],
                            'telephone'     => $addressDetails['telephone'],
                            'fax'           => $addressDetails['fax']
                        );
        return $billingAddress;
    }
    
    /**
     * Frame Payment Additional data
     * @return array
     */
    private function getAdditionalData($tokenDetails)
    {
        $additionalData = Array(
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
                            'stored_credentials_enabled' => 1,
                            'subscriptionStatus' => ''
                        );
        return $additionalData;
    }

    /**
     * Update recurring order Transactionsfor next order
     * 
     * 
     */
    public function updateRecurringTransactions($orderId, $recurringId){
        $transactionDetails = $this->transactionFactory->create()->loadById($recurringId);
        $this->insertNewTransaction($transactionDetails, $orderId);
        $transactionDetails->setStatus('completed')->save();        
    }

    public function insertNewTransaction($transactionDetails, $orderId){
        if($transactionDetails){
            $date = $transactionDetails->getRecurringDate();
            $week = strtotime(date("Y-m-d", strtotime($date)) . " +1 week");
            $monthdate = strtotime(date("Y-m-d", strtotime($date)) . " +1 month");
            $tmonthsdate = strtotime(date("Y-m-d", strtotime($date)) . " +3 month");
            $sixmonthsdate = strtotime(date("Y-m-d", strtotime($date)) . " +6 month");
            $yeardate = strtotime(date("Y-m-d", strtotime($date)) . " +12 month");
            
            $plan = $this->planFactory->create()->loadById($transactionDetails->getPlanId());
            $planInterval = $plan->getInterval();
            
            if($planInterval == 'WEEKLY'){
                $recurringDate = date('Y-m-d', $week);
            } else if($planInterval == 'MONTHLY'){
                $recurringDate = date('Y-m-d', $monthdate);
            } else if($planInterval == 'QUARTERLY'){
                $recurringDate = date('Y-m-d', $tmonthsdate);
            } else if($planInterval == 'SEMIANNUAL'){
                $recurringDate = date('Y-m-d', $sixmonthsdate);
            } else if($planInterval == 'ANNUAL'){
                $recurringDate = date('Y-m-d', $yeardate);
            }
            $transactions = $this->transactionFactory->create();            
            $transactions->setCustomerId($transactionDetails->getCustomerId());
            $transactions->setPlanId($transactionDetails->getPlanId());
            $transactions->setSubscriptionId($transactionDetails->getSubscriptionId());
            $transactions->setRecurringDate($recurringDate);
            $transactions->setRecurringEndDate($recurringDate);
            $transactions->setStatus('active');
            $transactions->setRecurringOrderId($orderId);
            $transactions->setWorldpayTokenId($transactionDetails->getWorldpayTokenId());
            $transactions->setWorldpayOrderId($transactionDetails->getWorldpayOrderId());
            $transactions->save();
        }
    }
    /**
     * Returns orders have creation date exceeded the allowed limit
     *
     * @param array $carry Result of previous filter call
     * @param \Magento\Sales\Model\Order
     *
     * @return array List of order IDs
     */       
    protected function _filterOrder($carry, \Magento\Sales\Model\Order $order)
    {
        if ($this->getCreationDate($order) > $this->getLimitDateForMethod()) {
            $carry[] = $order->getEntityId();
        }
        return $carry;
    }

    /**
     * Computes the latest valid date
     *
     * @return DateTime
     */
    protected function getLimitDateForMethod()
    {
        $timelimit = 24;
        $date = new \DateTime('now');
        $interval = new  \DateInterval(sprintf('PT%dH', $timelimit));
        $date->sub($interval);
        return $date;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return float|mixed
     */
    protected function getCreationDate(\Magento\Sales\Model\Order $order)
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $order->getData('created_at'));
    }

    /**
     * Computes the latest valid date
     *@return DateTime
     */
    protected function getLimitDate()
    {
        $cleanUpInterval = $this->worldpayhelper->orderCleanUpInterval();
        $date = new \DateTime('now');
        $interval = new  \DateInterval(sprintf('PT%dH', $cleanUpInterval));
        $date->sub($interval);
        return $date;
    }
    
    private function _loadOrder($orderId)
    {
        $this->_orderId = $orderId;
        $this->_order = $this->orderservice->getById($this->_orderId);
    }
    
    /**
     * Computes the latest valid date
     *@return DateTime
     */
    
    public function createSyncRequest(){
        try {
            $this->_fetchPaymentUpdate();
            $this->_registerWorldPayModel();
            $this->_applyPaymentUpdate();
            $this->_applyTokenUpdate();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
             if ($e->getMessage() == 'same state') {
                $this->_logger->error('Payment synchronized successfully!!');
             } else {
                 $this->_logger->error('Synchronising Payment Status failed: ' . $e->getMessage());
             }
        }
        return true;
    }
    
    private function _fetchPaymentUpdate()
    {
        $xml = $this->paymentservice->getPaymentUpdateXmlForOrder($this->_order);
        $this->_paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml($xml);
        $this->_tokenState = new \Sapient\Worldpay\Model\Token\StateXml($xml);
    }

    private function _registerWorldPayModel()
    {
        $this->paymentservice->setGlobalPaymentByPaymentUpdate($this->_paymentUpdate);
    }

    private function _applyPaymentUpdate()
    {
        try {
            $this->_paymentUpdate->apply($this->_order->getPayment(),$this->_order);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function _applyTokenUpdate()
    {
        $this->worldpaytoken->updateOrInsertToken($this->_tokenState, $this->_order->getPayment());
    }


}