<?php
namespace Sapient\Worldpay\Cron;
use \Magento\Framework\App\ObjectManager;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
class OrderCleanup {

    protected $_logger;
    private $orderCollectionFactory;

    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Sapient\Worldpay\Helper\Data $worldpayhelper
    ) {
        $this->_logger = $wplogger;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->worldpayhelper = $worldpayhelper;

    }


    public function execute()
    {
        if (!$this->worldpayhelper->isOrderCleanUp()) {
            return;
        }
        $this->_logger->info('clean up executed on - '.date('Y-m-d H:i:s'));
        $cleanupids = $this->getCleanupOrderIds();
        if(!empty($cleanupids)){
            $implodeorder = implode(",", $cleanupids);
            $orders = $this->getOrderCollectionFactory()->create();
            $orders->distinct(true);
            $orders->addFieldToFilter('main_table.entity_id', array('in' => $implodeorder));
            foreach($orders as $order){
                if($order->canCancel()){
                    $order->cancel();
                }else{
                    $this->_logger->info($order->getIncrementId().' cannot be canncelled');
                }
            }
            $orders->save();
        }
        return $this;
    }


    public function getCleanupOrderIds()
    {
        $orders = $this->getOrderCollectionFactory()->create();
        $orders->distinct(true);
        $orders->addFieldToSelect(array('entity_id','increment_id','created_at'));
        $orders->addFieldToFilter('main_table.status', array('in' => $this->worldpayhelper->cleanOrderStatus()));
        $orders->join(array('wp' => 'worldpay_payment'), 'wp.order_id=main_table.increment_id', array('payment_type'));
        $orders->join(array('og' => 'sales_order_grid'), 'og.entity_id=main_table.entity_id', '');

        $orderIds = array_reduce($orders->getItems(), array($this, '_filterOrder'));
        return $orderIds;
    }


    private function getOrderCollectionFactory()
    {
        if ($this->orderCollectionFactory === null) {

            $this->orderCollectionFactory = ObjectManager::getInstance()->get(CollectionFactoryInterface::class);
        }
        return $this->orderCollectionFactory;
    }



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



}