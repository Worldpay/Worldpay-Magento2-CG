<?php

namespace Sapient\Worldpay\Api;

/**
 * Interface SkipSubscriptionOrderRepositoryInterface
 */
interface SkipSubscriptionOrderRepositoryInterface
{
    /**
     * Get getById
     *
     * @param int $id
     * @return \Sapient\Worldpay\Api\Data\SkipSubscriptionOrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * Save Skip Subscription Order data
     *
     * @param \Sapient\Worldpay\Api\Data\SkipSubscriptionOrderInterface $skipSubscriptionOrder
     * @return mixed
     */
    public function save(\Sapient\Worldpay\Api\Data\SkipSubscriptionOrderInterface $skipSubscriptionOrder): mixed;
}
