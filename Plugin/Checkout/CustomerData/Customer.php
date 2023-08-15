<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sapient\Worldpay\Plugin\Checkout\CustomerData;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Address\CustomerAddressDataProvider;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;

/**
 * Process quote items price, considering tax configuration.
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Customer
{
    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $wpHelper;

     /**
      * @var CurrentCustomer
      */
    protected $currentCustomer;

     /**
      * @var CustomerAddressDataProvider
      */
    protected $customerAddressData;
    
     /**
      * @var CustomerRepository
      */
    protected $customerRepository;

    /**
     * Constructor Function
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $wpHelper
     * @param CurrentCustomer $currentCustomer
     * @param CustomerAddressDataProvider $customerAddressData
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $wpHelper,
        CurrentCustomer $currentCustomer,
        CustomerAddressDataProvider $customerAddressData,
        CustomerRepository $customerRepository
    ) {
        $this->wplogger = $wplogger;
        $this->wpHelper = $wpHelper;
        $this->currentCustomer = $currentCustomer;
        $this->customerAddressData = $customerAddressData;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Add Customer data
     *
     * @param \Magento\Customer\CustomerData\Customer $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(\Magento\Customer\CustomerData\Customer $subject, $result)
    {
        if ($this->wpHelper->isGooglePayEnableonPdp() && $this->wpHelper->isGooglePayEnable()) {
            $currentCustomer = $this->currentCustomer->getCustomer();
            if ($currentCustomer) {
                if ($currentCustomer->getId()) {
                    $customerObj = $this->customerRepository->getById($currentCustomer->getId());
                    $result['customer_address'] = $this->customerAddressData->getAddressDataByCustomer($customerObj);
                }
            }
        }
        return $result;
    }
}
