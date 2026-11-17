<?php
/**
 * @copyright 2022 Sapient
 */
namespace Sapient\Worldpay\Model\Multishipping;

class Order extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Constructor
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order $entityResource,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->entityResource = $entityResource;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /** @var \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order */
    private $entityResource;

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\Multishipping\Order::class);
    }
    /**
     * Retrieve multishipping details
     *
     * @param string $orderId
     * @return $this|null
     */
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
