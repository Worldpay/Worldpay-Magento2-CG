<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Notification;

use Sapient\Worldpay\Api\HistoryInterface;

class History implements HistoryInterface
{
    /** @var \Sapient\Worldpay\Model\ResourceModel\HistoryNotification\CollectionFactory */
    private $historyCollectionFactory;

    /**
     * @var \Sapient\Worldpay\Model\HistoryNotification
     */
    protected $historyNotification;
    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Model\HistoryNotification $historyNotification
     */
    public function __construct(
        \Sapient\Worldpay\Model\HistoryNotification $historyNotification,
        \Sapient\Worldpay\Model\ResourceModel\HistoryNotification\CollectionFactory $historyCollectionFactory
    ) {
        $this->historyCollectionFactory = $historyCollectionFactory;
        $this->historyNotification = $historyNotification;
    }
    /**
     * Returns Order Notification
     *
     * @api
     * @param Integer $order
     * @return json $result
     */
    public function getHistory($order)
    {
        $result="";
        if (isset($order)) {
                $result = $this->historyCollectionFactory->create()
                        ->addFieldToFilter('order_id', ['eq' => trim($order)])->getData();
        } else {
                $result = 'Order Id is null';
        }
        return $result;
    }
}
