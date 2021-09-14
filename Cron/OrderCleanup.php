<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Cron;

use \Magento\Framework\App\ObjectManager;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

/**
 * Model for cancel the order based on configuration set by admin
 */
class OrderCleanup
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
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Sapient\Worldpay\Helper\Data $worldpayhelper
    ) {
        $this->_logger = $wplogger;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->worldpayhelper = $worldpayhelper;
    }
    
    /**
     * Get the list of orders to be Cleanup and cancel the order
     */
    public function execute()
    {
        if (!$this->worldpayhelper->isOrderCleanUp()) {
            return;
        }
        $this->_logger->info('clean up executed on - '.date('Y-m-d H:i:s'));
        $cleanupids = $this->getCleanupOrderIds();
        if (!empty($cleanupids)) {
            $implodeorder = implode(",", $cleanupids);
            $orders = $this->getOrderCollectionFactory()->create();
            $orders->distinct(true);
            $orders->addFieldToFilter('main_table.entity_id', ['in' => $implodeorder]);
            foreach ($orders as $order) {
                if ($order->canCancel()) {
                    $order->cancel();
                } else {
                    $this->_logger->info($order->getIncrementId().' cannot be canncelled');
                }
            }
            $orders->save();
        }
        return $this;
    }

    /**
     * Get the list of orders to be Cleanup
     *
     * @return array List of order IDs
     */
    public function getCleanupOrderIds()
    {
        $orders = $this->getOrderCollectionFactory()->create();
        $orders->distinct(true);
        $orders->addFieldToSelect(['entity_id','increment_id','created_at']);
        $orders->addFieldToFilter('main_table.status', ['in' => $this->worldpayhelper->cleanOrderStatus()]);
        $orders->join(['wp' => 'worldpay_payment'], 'wp.order_id=main_table.increment_id', ['payment_type']);
        $orders->join(['og' => 'sales_order_grid'], 'og.entity_id=main_table.entity_id', '');

        $orderIds = array_reduce($orders->getItems(), [$this, '_filterOrder']);
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

        $paymentMethod = $order->getData('payment_type');
        if (!empty($paymentMethod) && $this->getCreationDate($order) < $this->getLimitDateForMethod($paymentMethod)) {
            $carry[] = $order->getEntityId();
        }

        return $carry;
    }

    /**
     * Computes the latest valid date for the given payment method
     *
     * @param string $paymentMethod
     *
     * @return DateTime
     */
    protected function getLimitDateForMethod($paymentMethod)
    {
        $timelimit = $this->worldpayhelper->getTimeLimitOfAbandonedOrders($paymentMethod);
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
     * @return DateTime
     */
    protected function getLimitDate()
    {
        $cleanUpInterval = $this->worldpayhelper->orderCleanUpInterval();
        $date = new \DateTime('now');
        $interval = new  \DateInterval(sprintf('PT%dH', $cleanUpInterval));
        $date->sub($interval);
        return $date;
    }
}
