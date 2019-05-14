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
class OrderSyncStatus {

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
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice,
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken,
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     */
    public function __construct(JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken,
        \Sapient\Worldpay\Model\Order\Service $orderservice
    ) {
        $this->_logger = $wplogger;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->worldpayhelper = $worldpayhelper;
        $this->paymentservice = $paymentservice;
        $this->orderservice = $orderservice;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->worldpaytoken = $worldpaytoken;

    }

    /**
     * Get the list of orders to be sync the status
     */
    public function execute()
    {
        $this->_logger->info('Orders sync status executed on - '.date('Y-m-d H:i:s'));
        $orderIds = $this->getOrderIds();
        
        if (!empty($orderIds)) {
            foreach ($orderIds as $order) {
                $this->_loadOrder($order);
                $this->createSyncRequest();
            }
        }
        return $this;
    }

    /**
     * Get the list of orders to be Sync
     *
     * @return array List of order IDs
     */
    public function getOrderIds()
    {
        // Complete order status for downloadable products
        $orderStatus = array('pending','processing','complete');
        $orders = $this->getOrderCollectionFactory()->create();
        $orders->distinct(true);
        $orders->addFieldToSelect(array('entity_id','increment_id','created_at'));
        $orders->addFieldToFilter('main_table.status', array('in' => $orderStatus));
        $orderIds = array_reduce($orders->getItems(), array($this, '_filterOrder'));
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