<?php

namespace Sapient\Worldpay\Api;

/**
 * Interface EditSubscriptionHistoryRepositoryInterface
 */
interface EditSubscriptionHistoryRepositoryInterface
{
    /**
     * Get getById
     *
     * @param int $id
     * @return \Sapient\Worldpay\Api\Data\EditSubscriptionHistoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * Save Subscription History
     *
     * @param \Sapient\Worldpay\Api\Data\EditSubscriptionHistoryInterface $editSubscriptionHistory
     * @return mixed
     */
    public function save(\Sapient\Worldpay\Api\Data\EditSubscriptionHistoryInterface $editSubscriptionHistory): mixed;
}
