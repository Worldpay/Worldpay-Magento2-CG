<?php
/**
 * @copyright 2023 Sapient
 */
namespace Sapient\Worldpay\Model;

use Magento\Framework\Model\AbstractModel;
use Sapient\Worldpay\Api\Data\EditSubscriptionHistoryInterface;

/**
 * Class Model EditSubscriptionHistory
 */
class EditSubscriptionHistory extends AbstractModel implements EditSubscriptionHistoryInterface
{
    /**
     * Initialize EditSubscriptionHistory Model
     *
     * @return void
     */
    protected function _construct()
    {
    /**
     * Initialize resource model
     *
     * @return void
     */
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\EditSubscriptionHistory::class);
    }

    /**
     * Get Id
     */
    public function getId()
    {
        return parent::getData(self::ENTITY_ID);
    }

    /**
     * Set Id
     *
     * @param int $entityId
     */
    public function setId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get entityId
     */
    public function getSubscriptionId()
    {
        return parent::getData(self::SUBSCRIPTION_ID);
    }

    /**
     * Set Subscription Id
     *
     * @param int $subscriptionId
     */
    public function setSubscriptionId($subscriptionId)
    {
        return $this->setData(self::SUBSCRIPTION_ID, $subscriptionId);
    }

    /**
     * Get Customer Id
     */
    public function getCustomerId()
    {
        return parent::getData(self::CUSTOMER_ID);
    }

    /**
     * Set Customer Id
     *
     * @param int $customerId
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get Old Data
     */
    public function getOldData()
    {
        return parent::getData(self::OLD_DATA);
    }

    /**
     * Set shipping and payment method Data
     *
     * @param mixed $oldData
     */
    public function setOldData($oldData)
    {
        return $this->setData(self::OLD_DATA, $oldData);
    }

    /**
     * Get Created At
     */
    public function getCreatedAt()
    {
        return $this->setData(self::CREATED_AT);
    }

    /**
     * Set Created At
     *
     * @param string $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get ModifiedAt
     */
    public function getModifiedAt()
    {
        return $this->setData(self::MODIFIED_DATE);
    }

    /**
     * Set Modified At
     *
     * @param string $modifiedAt
     */
    public function setModifiedAt($modifiedAt)
    {
        return $this->setData(self::MODIFIED_DATE, $modifiedAt);
    }
}
