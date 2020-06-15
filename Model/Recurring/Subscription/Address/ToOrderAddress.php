<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\Recurring\Subscription\Address;

class ToOrderAddress
{
    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    private $objectCopyService;

    /**
     * @var \Magento\Sales\Model\Order\AddressRepository
     */
    private $addressRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\Sales\Model\Order\AddressRepository $addressRepository
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Sales\Model\Order\AddressRepository $addressRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->objectCopyService = $objectCopyService;
        $this->addressRepository = $addressRepository;
        $this->eventManager = $eventManager;
    }

    /**
     * Convert subscription address to order address
     *
     * @param \Sapient\Worldpay\Model\Recurring\Subscription\Address $subscriptionAddress
     * @param array $data
     * @return \Magento\Sales\Api\Data\OrderAddressInterface
     */
    public function convert(\Sapient\Worldpay\Model\Recurring\Subscription\Address $subscriptionAddress, $data = [])
    {
        $address = $this->addressRepository->create();

        $addressData = $this->objectCopyService->getDataFromFieldset(
            'worldpay_subscription_address_convert',
            'to_order_address',
            $subscriptionAddress
        );

        $address->addData(array_merge($addressData, $data));

        $this->eventManager->dispatch(
            'sales_convert_subscription_address_to_order_address',
            ['subscription_address' => $subscriptionAddress, 'order_address' => $address]
        );

        return $address;
    }
}
