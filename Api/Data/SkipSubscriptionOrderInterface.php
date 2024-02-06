<?php
/**
 * @copyright 2023 Sapient
 */
declare(strict_types=1);

namespace Sapient\Worldpay\Api\Data;

/**
 * Interface EditSubscriptionHistoryInterface
 *
 */

interface SkipSubscriptionOrderInterface
{
    public const ENTITY_ID = 'entity_id';
    public const SUBSCRIPTION_ID = 'subscription_id';
    public const CUSTOMER_ID = 'customer_id';
    public const IS_SKIPPED = 'is_skipped';
    public const OLD_RECURRING_DATE = 'old_recurring_date';
    public const NEW_RECURRING_DATE = 'new_recurring_date';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Get Model'entity_id' property value
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
     * Get CustomerId property value
     *
     * @return string
     */
    public function getCustomerId();

    /**
     * Set Model CustomerId property value
     *
     * @param int $customerId
     * @return mixed
     */
    public function setCustomerId(int $customerId);
 
    /**
     * Get IsSkipped property value
     *
     * @return bool|null
     */
    public function getIsSkipped();

    /**
     * Set IsSkipped property value
     *
     * @param bool $isSkipped
     * @return true|false
     */
    public function setIsSkipped($isSkipped);

    /**
     * Get NewRecurringDate property value
     *
     * @return string
     */
    public function getNewRecurringDate();

    /**
     * Set NewRecurringDate property value
     *
     * @param string $newRecurringDate
     * @return mixed|null
     */
    public function setNewRecurringDate($newRecurringDate);

    /**
     * Get OldRecurringDate property value
     *
     * @return string|null
     */
    public function getOldRecurringDate();

    /**
     * Set Model OldRecurringDate property value
     *
     * @param string $oldRecurringDate
     * @return mixed|null
     */
    public function setOldRecurringDate($oldRecurringDate);

    /**
     * Get OldRecurringDate property value
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set Model createdAt property value
     *
     * @param string $createdAt
     * @return mixed|null
     */
    public function setCreatedAt($createdAt);

    /**
     * Get OldRecurringDate property value
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set Model updatedAt property value
     *
     * @param string $updatedAt
     * @return string|null
     */
    public function setUpdatedAt($updatedAt);
}
