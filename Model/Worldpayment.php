<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;

use Sapient\Worldpay\Model\ResourceModel\Worldpayment\CollectionFactory;

/**
 * Resource Model
 */
class Worldpayment extends \Magento\Framework\Model\AbstractModel
{
    /** @var \Sapient\Worldpay\Model\ResourceModel\Worldpayment */
    private $entityResource;

    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\SubscriptionOrder
     */
    private $sentforAuthOrderCollection;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param CollectionFactory $sentforAuthOrderCollection
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        CollectionFactory $sentforAuthOrderCollection,
        \Sapient\Worldpay\Model\ResourceModel\Worldpayment $entityResource,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->entityResource = $entityResource;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->sentforAuthOrderCollection = $sentforAuthOrderCollection;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\Worldpayment::class);
    }

    /**
     * Retrieve worldpay payment Details
     *
     * @param string $orderId
     * @return $this|null
     */
    public function loadByPaymentId($orderId)
    {

        if (!$orderId) {
            return;
        }
        $id = $this->entityResource->loadByPaymentId($orderId);
        $this->entityResource->load($this, $id);
        return $this;
    }

    /**
     * Load worldpay payment Details
     *
     * @param string $order_id
     * @return $this|null
     */
    public function loadByWorldpayOrderId($order_id)
    {
        if (!$order_id) {
            return;
        }
        $id = $this->entityResource->loadByWorldpayOrderId($order_id);
        $this->entityResource->load($this, $id);
        return $this;
    }

    /**
     * Load pending order
     *
     * @param string $filterByCreatedAt
     *
     * @return \Sapient\Worldpay\Model\Worldpayment
     */
    public function getsentforAuthOrderCollection($filterByCreatedAt)
    {
        $thresholdOption = new \DateTime();
        $thresholdOption->modify('-'.$filterByCreatedAt.' hour');
        $createdAt = $thresholdOption->format('Y-m-d H:i:s');
        $this->sentforAuthOrderCollection = $this->sentforAuthOrderCollection->create();
        //Add join with the order table
        $this->sentforAuthOrderCollection
            ->getSelect()
            ->joinLeft(
                ['so' => $this->sentforAuthOrderCollection->getTable('sales_order')],
                'main_table.order_id = so.increment_id',
                ['created_at']
            )
            ->where('so.created_at < ?', $createdAt)
            ->where('payment_status = ?', 'SENT_FOR_AUTHORISATION')
            ->order('id DESC');

        return $this->sentforAuthOrderCollection;
    } 
}