<?php

namespace Sapient\Worldpay\Model\ProductOnDemand;

class Order extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Constructor
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Sapient\Worldpay\Model\ResourceModel\ProductOnDemand\Order $entityResource,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->entityResource = $entityResource;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /** @var \Sapient\Worldpay\Model\ResourceModel\ProductOnDemand\Order */
    private $entityResource;

    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\ProductOnDemand\Order::class);
    }

    public function loadByOrderId($orderId)
    {
        if (!$orderId) {
            return;
        }
        $id = $this->entityResource->loadByOrderId($orderId);
        $this->entityResource->load($this, $id);
        return $this;
    }
}
