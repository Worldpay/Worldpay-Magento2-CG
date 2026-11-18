<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\Recurring\Subscription;

use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Sapient\Worldpay\Model\Recurring\Subscription;

/**
 * Subscription Address
 */
class Transactions extends AbstractModel
{
    /**
     * Constructor
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Transactions $entityResource,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->entityResource = $entityResource;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /** @var \Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Transactions */
    private $entityResource;

    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Transactions::class);
    }

    /**
     * Set subscription info
     *
     * @param Subscription $subscription
     * @return $this
     */
    public function setSubscription(Subscription $subscription)
    {
        $this->subscription = $subscription;
        return $this;
    }

    /**
     * Get subscription info
     *
     * @return Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * Combine values of street lines into a single string
     *
     * @param string[]|string $value
     * @return string
     */
    protected function implodeStreetValue($value)
    {
        if (is_array($value)) {
            $value = trim(implode(PHP_EOL, $value));
        }
        return $value;
    }

    /**
     * Load order Details
     *
     * @param int $order_increment_id
     * @return $this|null
     */
    public function loadByOrderIncrementId($order_increment_id)
    {
        if (!$order_increment_id) {
            return;
        }
        $id = $this->entityResource->loadByOriginalOrderIncrementId($order_increment_id);
        $this->entityResource->load($this, $id);
        return $this;
    }
    /**
     * Load order Details
     *
     * @param int $order_id
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
     * Load order Details
     *
     * @param int $subscriptionId
     * @return $this|null
     */
    public function loadBySubscriptionId($subscriptionId)
    {
        if (!$subscriptionId) {
            return;
        }
        $id = $this->entityResource->loadBySubscriptionId($subscriptionId);
        $this->entityResource->load($this, $id);
        return $this;
    }
    
    /**
     * Load order Details
     *
     * @param int $entityId
     * @return $this|null
     */
    public function loadById($entityId)
    {
        if (!$entityId) {
            return;
        }
        $id = $this->entityResource->loadById($entityId);
        $this->entityResource->load($this, $id);
        return $this;
    }

    /**
     * Load Subscription Active order Details
     *
     * @param int $subscriptionId
     * @return $this|null
     */
    public function loadBySubscriptionIdActive($subscriptionId)
    {
        if (!$subscriptionId) {
            return;
        }
        $id = $this->entityResource->loadBySubscriptionIdActive($subscriptionId);
        $this->entityResource->load($this, $id);
        return $this;
    }
}
