<?php
/**
 * @copyright 2023 Sapient
 */
namespace Sapient\Worldpay\Model\PayByLink;

class Order extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

     /**
      * @var \Sapient\Worldpay\Helper\Data
      */
    private $worldpayhelper;

     /**
      * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
      */
    private $_orderCollectionFactory;
    /**
     * Pay By Link Order Constructor
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Sapient\Worldpay\Helper\Data $worldpayhelper
    ) {
        $this->worldpayhelper = $worldpayhelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
    }
    /**
     * Get the list of orders to be expired or resend
     *
     * @param string $curDate
     * @param int $expiryTime
     * @param bool|null $resendExpiryTime
     * @return array List of order IDs
     */
    public function getPayByLinkOrderIds($curDate, $expiryTime, $resendExpiryTime = null)
    {
        $dateFieldValues = [];
        $dateFields = [];
        /* Expiry Time date finding */
        $minDate = $this->worldpayhelper->findPblOrderExpiryTime($curDate, $expiryTime);
        $cronDates = $this->worldpayhelper->findFromToPblDateAndTime($minDate);
        $dateFieldValues[] = ['from' => $cronDates['from'], 'to' => $cronDates['to']];
        $dateFields[] = 'main_table.created_at';
        /* Resend Expiry Time */
        if (!empty($resendExpiryTime)) {
            $dateFields[] = 'main_table.created_at';
            $minResendDate = $this->worldpayhelper->findPblOrderExpiryTime($curDate, $resendExpiryTime);
            $cronResendDates = $this->worldpayhelper->findFromToPblDateAndTime($minResendDate);
            $dateFieldValues[] = ['from' => $cronResendDates['from'], 'to' => $cronResendDates['to']];
        }
        $orderStatus = 'pending';
        $orders = $this->getOrderCollectionFactory()->create();
        $orders->distinct(true);
        $orders->addFieldToSelect(['increment_id','created_at']);
        $orders->addFieldToFilter('main_table.status', ['in' => $orderStatus]);
        $orders->addFieldToFilter($dateFields, $dateFieldValues);
        $orders->addFieldToFilter('og.payment_method', ['eq' => 'worldpay_paybylink']);
        $orders->join(['wp' => 'worldpay_payment'], 'wp.order_id=main_table.increment_id', '');
        $orders->join(['og' => 'sales_order_grid'], 'og.entity_id=main_table.entity_id', '');
        $orders->getSelect()->group('wp.worldpay_order_id');
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
}
