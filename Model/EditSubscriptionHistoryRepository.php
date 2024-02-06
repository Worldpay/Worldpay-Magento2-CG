<?php

namespace Sapient\Worldpay\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Sapient\Worldpay\Api\Data\EditSubscriptionHistoryInterface;
use Sapient\Worldpay\Api\EditSubscriptionHistoryRepositoryInterface;
use Sapient\Worldpay\Model\ResourceModel\EditSubscriptionHistory;
use Sapient\Worldpay\Model\ResourceModel\EditSubscriptionHistory\CollectionFactory;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Model EditSubscriptionHistoryRepository
 *
 */
class EditSubscriptionHistoryRepository implements EditSubscriptionHistoryRepositoryInterface
{
    /**
     * @var $editHistoryFactory
     */
    private $editHistoryFactory;

    /**
     * @var $editHistoryResource
     */
    private $editHistoryResource;

    /**
     * @var $editHistoryCollectionFactory
     */
    private $editHistoryCollectionFactory;

    /**
     * @var $subscriptionFactory
     */
    protected $subscriptionFactory;

    /**
     * @var $customerSession
     */
    protected $customerSession;

    /**
     * @var $serializer
     */
    private $serializer;
    
    /**
     * @var $orderRepository
     */
    protected $orderRepository;

    /**
     * @var $addressConfig
     */
    protected $addressConfig;
  
    /**
     * Constructor
     *
     * @param EditSubscriptionHistoryFactory $editHistoryFactory
     * @param EditSubscriptionHistory $editHistoryResource
     * @param CollectionFactory $editHistoryCollectionFactory
     * @param SubscriptionFactory $subscriptionFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param SerializerInterface $serializer
     *
     */
    public function __construct(
        EditSubscriptionHistoryFactory $editHistoryFactory,
        EditSubscriptionHistory $editHistoryResource,
        CollectionFactory $editHistoryCollectionFactory,
        SubscriptionFactory $subscriptionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Customer\Model\Session $customerSession,
        SerializerInterface $serializer,
    ) {
        $this->editHistoryFactory = $editHistoryFactory;
        $this->editHistoryResource = $editHistoryResource;
        $this->editHistoryCollectionFactory = $editHistoryCollectionFactory;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->customerSession = $customerSession;
        $this->serializer = $serializer;
        $this->orderRepository = $orderRepository;
        $this->addressConfig = $addressConfig;
    }

    /**
     * Get ById
     *
     * @param int $id
     * @return \Sapient\Worldpay\Api\Data\StudentInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id)
    {
        $editHistory = $this->editHistoryFactory->create();
        $this->editHistoryResource->load($editHistory, $id);
        if (!$editHistory->getId()) {
            throw new NoSuchEntityException(__('Unable to find History with ID "%1"', $id));
        }
        return $editHistory;
    }

    /**
     * Save SubscriptionHistory data
     *
     * @param EditSubscriptionHistoryInterface $subscriptionHistory
     * @return mixed
     */
    public function save(EditSubscriptionHistoryInterface $subscriptionHistory): mixed
    {
        $this->editHistoryResource->save($subscriptionHistory);
        return $subscriptionHistory;
    }

    /**
     * Update Edit History
     *
     * @param object $subscriptionOldInfo
     * @param string $shippingOldAddress
     * @param string $method
     */
    public function updateEditHistory($subscriptionOldInfo, $shippingOldAddress, $method)
    {
        $customer = $this->customerSession->getCustomer();
        $customerId = $customer->getId();
         
        $shippingAddress = $this->getShippingAddress($shippingOldAddress);

        $subscriptionHistory = $this->editHistoryFactory->create();
        $subscriptionOldData = [
            'payment_method'=>$method,
            'shipping_address'=>$shippingAddress,
            'shipping_method' =>$subscriptionOldInfo->getShippingMethod()
            ];
        $subscriptionOldData = $this->serializer->serialize($subscriptionOldData);

        $subscriptionHistory->setSubscriptionId($subscriptionOldInfo->getSubscriptionId());
        $subscriptionHistory->setCustomerId($customerId);
        $subscriptionHistory->setOldData($subscriptionOldData);
        $subscriptionHistory->setCreatedAt(date('Y-m-d'));
        $subscriptionHistory->setModifiedAt(date('Y-m-d'));
        $subscriptionHistory->save();
    }

    /**
     * Get Subscription Data
     *
     * @param int $subscriptionId
     * @return mixed
     */
    public function getSubscriptionData($subscriptionId)
    {
        $subscription = $this->subscriptionFactory->create();
        $subscription->load($subscriptionId);
        return $subscription;
    }

    /**
     * Get Shipping address data of specific order
     *
     * @param string $address
     * @return string
     */
    
    public function getShippingAddress($address)
    {
        $renderer = $this->addressConfig->getFormatByCode('text')->getRenderer();
        return $renderer->renderArray($address);
    }
}
