<?php

/**
 * @copyright 2022 Sapient
 */

namespace Sapient\Worldpay\Model\ResourceModel\Multishipping\Order;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init(
            \Sapient\Worldpay\Model\Multishipping\Order::class,
            \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order::class
        );
    }
    
    /**
     * Get records by quote id
     *
     * @param int $quote_id
     * @param int $order_id
     * @return int
     */
    public function getCollectionByQuoteId($quote_id, $order_id)
    {
        if (empty($quote_id) || !is_numeric($quote_id)) {
            return [];
        }
        $data = $this->getOriginalOrderId($order_id);
        $original_id = $data->getWorldpayOrderId();
        $this->clear()->getSelect()->reset(\Magento\Framework\DB\Select::WHERE);
        $collection = $this->addFieldToSelect('*')
            ->addFieldToFilter('worldpay_order_id', ['eq' => $original_id])
            ->addFieldToFilter('quote_id', ['eq' => $quote_id]);
        return $collection;
    }
    /**
     * Get quotes count by quote id
     *
     * @param int $orderCode
     * @return array
     */
    public function getOriginalWorldpayOrderId($orderCode)
    {
        $this->clear()->getSelect()->reset(\Magento\Framework\DB\Select::WHERE);
        $collection = $this->addFieldToSelect('order_id')
            ->addFieldToFilter('worldpay_order_id', ['eq' => $orderCode]);
        return $collection->getFirstItem()->toArray();
    }
    /**
     * Get records by quote_id and order_id
     *
     * @param int $order_id
     * @param int $quote_id
     * @return int
     */
    public function getCollectionByOrderAndQuoteId($order_id, $quote_id)
    {
        if (empty($quote_id) || !is_numeric($quote_id)) {
            return [];
        }
        $data = $this->getOriginalOrderId($order_id);
        $original_id = $data->getWorldpayOrderId();
        $this->clear()->getSelect()->reset(\Magento\Framework\DB\Select::WHERE);
        $collection = $this->addFieldToSelect('*')
            ->addFieldToFilter('quote_id', ['eq' => $quote_id])
            ->addFieldToFilter('worldpay_order_id', ['eq' => $original_id])
            ->addFieldToFilter('order_id', ['neq' => $order_id]);
        return $collection;
    }
    /**
     * Get original order id
     *
     * @param int $order_id
     * @return string
     */
    public function getOriginalOrderId($order_id)
    {
        $this->clear()->getSelect()->reset(\Magento\Framework\DB\Select::WHERE);
        $collection = $this->addFieldToSelect('*')
            ->addFieldToFilter('order_id', ['eq' => $order_id]);
        return $collection->getFirstItem();
    }
}
