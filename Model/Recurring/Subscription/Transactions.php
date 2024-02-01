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
     * @return array
     */
    public function loadByOrderIncrementId($order_increment_id)
    {
        if (!$order_increment_id) {
            return;
        }
        $id = $this->getResource()->loadByOriginalOrderIncrementId($order_increment_id);
        return $this->load($id);
    }
    /**
     * Load order Details
     *
     * @param int $order_id
     * @return array
     */
    public function loadByWorldpayOrderId($order_id)
    {
        if (!$order_id) {
            return;
        }
        $id = $this->getResource()->loadByWorldpayOrderId($order_id);
            return $this->load($id);
    }
    
    /**
     * Load order Details
     *
     * @param int $subscriptionId
     * @return array
     */
    public function loadBySubscriptionId($subscriptionId)
    {
        if (!$subscriptionId) {
            return;
        }
        $id = $this->getResource()->loadBySubscriptionId($subscriptionId);
            return $this->load($id);
    }
    
    /**
     * Load order Details
     *
     * @param int $entityId
     * @return array
     */
    public function loadById($entityId)
    {
        if (!$entityId) {
            return;
        }
        $id = $this->getResource()->loadById($entityId);
            return $this->load($id);
    }

    /**
     * Load Subscription Active order Details
     *
     * @param int $subscriptionId
     * @return array
     */
    public function loadBySubscriptionIdActive($subscriptionId)
    {
        if (!$subscriptionId) {
            return;
        }
        $id = $this->getResource()->loadBySubscriptionIdActive($subscriptionId);
           return $this->load($id);
    }
}
