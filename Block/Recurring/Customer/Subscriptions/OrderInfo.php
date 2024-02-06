<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Block\Recurring\Customer\Subscriptions;

use Sapient\Worldpay\Model\Recurring\Subscription\Address;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Sapient\Worldpay\Model\Recurring\Order\Address\Renderer as AddressRenderer;
use Magento\Customer\Helper\Address as CustomerAddressHelper;
use Magento\Customer\Model\Address\Config as AddressConfig;

class OrderInfo extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'Sapient_Worldpay::recurring/edit/order_info.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;

      /**
       * @var CustomerAddressHelper
       */
    protected $_customerAddressHelper;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

     /**
      * @var \Magento\Customer\Api\CustomerRepositoryInterface
      */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $_addressConfig;
    /**
     * @param TemplateContext $context
     * @param Registry $registry
     * @param PaymentHelper $paymentHelper
     * @param AddressRenderer $addressRenderer
     * @param CustomerAddressHelper $customerAddressHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressConfig $addressConfig
     * @param Mapper $addressMapper
     * @param Session $customerSession
     *
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        PaymentHelper $paymentHelper,
        AddressRenderer $addressRenderer,
        CustomerAddressHelper $customerAddressHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        AddressConfig $addressConfig,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->addressRenderer = $addressRenderer;
        $this->paymentHelper = $paymentHelper;
        $this->coreRegistry = $registry;
        $this->_isScopePrivate = true;
        $this->_customerAddressHelper = $customerAddressHelper;
        $this->addressMapper = $addressMapper;
        $this->customerSession  = $customerSession;
        $this->_addressConfig  = $addressConfig;
        $this->customerRepository  = $customerRepository;
       
        parent::__construct($context, $data);
    }

    /**
     * Retrieve Current Customer data
     *
     * @return mixed
     */
    public function getCurrentCustomer()
    {
        return $this->customerSession->getCustomer();
    }

    /**
     * Retrieve Current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getCurrentSubscriptionOrder()
    {
        return $this->coreRegistry->registry('current_subscription_order');
    }

    /**
     * Returns string with formatted address
     *
     * @param Address $address
     * @return null|string
     */
    public function getFormattedAddress(Address $address)
    {
        return $this->addressRenderer->format($address, 'html');
    }

    /**
     * Retrieve Transaction Data
     *
     * @param Subscription $subscriptionOrder
     * @return mixed
     */
    public function getTransactionData(\Sapient\Worldpay\Model\Recurring\Subscription $subscriptionOrder)
    {
        return $subscriptionOrder->getTransactionData();
    }
}
