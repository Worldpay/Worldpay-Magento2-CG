<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Block\Recurring\Customer\Subscriptions\OrderInfo;

use Magento\Customer\Model\Address\Config as AddressConfig;

class AddressInfo extends \Sapient\Worldpay\Block\Recurring\Customer\Subscriptions\OrderInfo
{
    /**
     * @var string
     */
    protected $_template = 'Sapient_Worldpay::recurring/edit/order_address_info.phtml';
    /**
     * Retrieve Address Options
     *
     * @return mixed
     */
    public function getAddressOptions()
    {
        $options = $this->getData('address_options');
        if ($options === null) {
            $options = [];
            $addresses = [];

            try {
                $currentCustomer = $this->getCurrentCustomer();
                $addresses = $this->customerRepository->getById($currentCustomer->getId())->getAddresses();
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                /** Customer does not exist */
                throw new NoSuchEntityException(__('Customer does not exist'));
            }
            /** @var \Magento\Customer\Api\Data\AddressInterface $address */
            foreach ($addresses as $address) {
                $label = $this->_addressConfig
                    ->getFormatByCode(AddressConfig::DEFAULT_ADDRESS_FORMAT)
                    ->getRenderer()
                    ->renderArray($this->addressMapper->toFlatArray($address));

                $options[] = [
                    'value' => $address->getId(),
                    'label' => $label,
                ];
            }
            $this->setData('address_options', $options);
        }

        return $options;
    }
}
