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
class OrderSyncStatus
{

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
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $_orderCollectionFactory;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    private $worldpayhelper;
    /**
     * @var \Sapient\Worldpay\Model\Payment\Service
     */
    private $paymentservice;

    /**
     * @var \Sapient\Worldpay\Model\Order\Service
     */
    private $orderservice;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Sapient\Worldpay\Model\Token\WorldpayToken
     */
    private $worldpaytoken;
    
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
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
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
                $this->_loadOrder($order['entity_id']);
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
        $curdate = date("Y-m-d H:i:s");
        $timelimit = $this->worldpayhelper->getOrderSyncInterval();
        $maxDate = strtotime(date("Y-m-d H:i:s", strtotime($curdate)) . " -30 min");
        $cronMaxDate = date('Y-m-d H:i:s', $maxDate);
        
        $minDate = strtotime(date("Y-m-d H:i:s", strtotime($curdate)) . " -$timelimit hour");
        $cronMinDate = date('Y-m-d H:i:s', $minDate);
        
        $status = $this->worldpayhelper->getSyncOrderStatus();
        $orderStatus = explode(',', $status);
        $orders = $this->getOrderCollectionFactory()->create();
        $orders->distinct(true);
        $orders->addFieldToSelect(['entity_id','increment_id','created_at']);
        $orders->addFieldToFilter('main_table.status', ['in' => $orderStatus]);
        $orders->addFieldToFilter('main_table.created_at', ['lteq' => $cronMaxDate]);
        $orders->addFieldToFilter('main_table.created_at', ['gteq' => $cronMinDate]);
        $orders->join(['wp' => 'worldpay_payment'], 'wp.order_id=main_table.increment_id', '');
        $orders->join(['og' => 'sales_order_grid'], 'og.entity_id=main_table.entity_id', '');
        
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

            $this->orderCollectionFactory = $this->_orderCollectionFactory;
        }
        return $this->orderCollectionFactory;
    }
    /**
     * LoadOrder
     *
     * @param Int $orderId
     * @return CollectionFactoryInterface
     */

    private function _loadOrder($orderId)
    {
        $this->_orderId = $orderId;
        $this->_order = $this->orderservice->getById($this->_orderId);
    }
    /**
     * Create SyncRequest
     *
     * @return CollectionFactoryInterface
     */

    public function createSyncRequest()
    {
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
    /**
     * Fetch PaymentUpdate
     *
     * @return CollectionFactoryInterface
     */

    private function _fetchPaymentUpdate()
    {
        $xml = $this->paymentservice->getPaymentUpdateXmlForOrder($this->_order);
        $this->_paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml($xml);
        $this->_tokenState = new \Sapient\Worldpay\Model\Token\StateXml($xml);
    }
    /**
     * RegistercWorldPaycModel
     *
     * @return CollectionFactoryInterface
     */

    private function _registerWorldPayModel()
    {
        $this->paymentservice->setGlobalPaymentByPaymentUpdate($this->_paymentUpdate);
    }
    /**
     * Apply Payment Update
     *
     * @return CollectionFactoryInterface
     */

    private function _applyPaymentUpdate()
    {
        try {
            $this->_paymentUpdate->apply($this->_order->getPayment(), $this->_order);
        } catch (Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }
    /**
     * Apply Token Update
     *
     * @return CollectionFactoryInterface
     */

    private function _applyTokenUpdate()
    {
        $this->worldpaytoken->updateOrInsertToken($this->_tokenState, $this->_order->getPayment());
    }
}
