<?php
/**
 * @copyright 2023 Sapient
 */
declare(strict_types=1);

namespace Sapient\Worldpay\Api\Data;

/**
 * Api Data Interface EditSubscriptionHistoryInterface
 */
interface EditSubscriptionHistoryInterface
{
    public const ENTITY_ID = 'entity_id';
    public const SUBSCRIPTION_ID = 'subscription_id';
    public const CUSTOMER_ID = 'customer_id';
    public const OLD_DATA = 'old_data';
    public const CREATED_AT = 'created_at';
    public const MODIFIED_DATE = 'modified_date';

    /**
     * Get Model entity_id property value
     *
     * @return string
     */
    public function getId();

    /**
     * Set Model id property value
     *
     * @param int $id
     * @return int
     */
    public function setId(int $id);

    /**
     * Get SubscriptionId property value
     *
     * @return int|null
     */
    public function getSubscriptionId();

    /**
     * Set subscriptionId property value
     *
     * @param string $subscriptionId
     * @return mixed
     */
    public function setSubscriptionId(int $subscriptionId);

    /**
     * Get customerId property value
     *
     * @return string
     */
    public function getCustomerId();

    /**
     * Set Model customerId property value
     *
     * @param int $customerId
     * @return int
     */
    public function setCustomerId(int $customerId);

    /**
     * Get oldData property value
     *
     * @return mixed
     */
    public function getOldData();

    /**
     * Set oldData property value
     *
     * @param mixed $oldData
     * @return mixed
     */
    public function setOldData(array $oldData);

    /**
     * Get CreatedAt property value
     *
     * @return bool|null
     */
    public function getCreatedAt();

    /**
     * Set CreatedAt property value
     *
     * @param string $createdAt
     * @return mixed
     */
    public function setCreatedAt(string $createdAt);

    /**
     * Get Modified At property value
     *
     * @return string|null
     */
    public function getModifiedAt();

    /**
     * Set Model modifiedAt property value
     *
     * @param string $modifiedAt
     * @return mixed
     */
    public function setModifiedAt(string $modifiedAt);
}
