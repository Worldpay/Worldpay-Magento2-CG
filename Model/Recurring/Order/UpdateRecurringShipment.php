<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Recurring\Order;

use Sapient\Worldpay\Api\UpdateRecurringShipmentInterface;

use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;

class UpdateRecurringShipment implements UpdateRecurringShipmentInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Sales\Model\OrderFactory $orderFactory
     */
    protected $orderFactory;
    
    /**
     * @var RateRequestFactory $rateRequestFactory
     */
    protected $rateRequestFactory;

    /**
     * @var RateCollectorInterfaceFactory $rateCollector
     */
    protected $rateCollector;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var AddressRepositoryInterface $addressRepository
     */
    protected $addressRepository;

    /**
     * @var CartRepositoryInterface $cartRepository
     */
    protected $cartRepository;

    /**
     * @var SubscriptionFactory $subscriptionFactory
     */
    protected $subscriptionFactory;
   
    /**
     * @var RegionCollectionFactory $regionCollectionFactory
     */
    protected $regionCollectionFactory;

    /**
     * @var EditSubscriptionHistoryRepository $editSubscriptionRepository
     */
    protected $editSubscriptionRepository;

    /**
     * Recurring UpdateRecurringShipment constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param RateRequestFactory $rateRequestFactory
     * @param RateCollectorInterfaceFactory $rateCollector
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param AddressRepositoryInterface $addressRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param RegionCollectionFactory $regionCollectionFactory
     * @param SubscriptionFactory $subscriptionFactory
     * @param EditSubscriptionHistoryRepository $editSubscriptionRepository
     */

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        RateRequestFactory $rateRequestFactory,
        RateCollectorInterfaceFactory $rateCollector,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        AddressRepositoryInterface $addressRepository,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        RegionCollectionFactory $regionCollectionFactory,
        SubscriptionFactory $subscriptionFactory,
        \Sapient\Worldpay\Model\EditSubscriptionHistoryRepository $editSubscriptionRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->orderFactory = $orderFactory;
        $this->rateRequestFactory = $rateRequestFactory;
        $this->rateCollector = $rateCollector;
        $this->storeManager = $storeManager;
        $this->addressRepository = $addressRepository;
        $this->cartRepository = $cartRepository;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->editSubscriptionRepository = $editSubscriptionRepository;
    }

    /**
     * Retrieve Original Order data
     *
     * @param int $orderIncrementId
     * @return mixed
     */
    public function getOriginalOrder($orderIncrementId)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
        return $order;
    }

    /**
     * Retrieve Address data
     *
     * @param int $addressId
     *
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    public function getAddressData($addressId)
    {
        try {
            $addressData = $this->addressRepository->getById($addressId);
        } catch (\Exception $e) {
            throw $e->getMessage();
        }
        return $addressData;
    }

    /**
     * Retrieve text of street lines, concatenated using LF symbol
     *
     * @param string $streetData
     * @return string
     */
    public function getStreetFull($streetData)
    {
        $street = $streetData;
        return is_array($street) ? implode("\n", $street) : ($street ?? '');
    }

    /**
     * Update Shipment Data
     *
     * @param int $subscriptionId
     * @return mixed
     */
    public function getSubscription($subscriptionId)
    {
        return $this->subscriptionFactory
            ->create()
            ->load($subscriptionId);
    }

    /**
     * Update Shipment Data
     *
     * @param mixed $shipmentData
     * @return string
     */

    public function updateRecurringShipment($shipmentData) : string
    {
        $shipmentUpdateMsg = [
            'success'=> false,
            'msg'=> ''
        ];
        try {
            if (!empty($shipmentData)) {

                $orderIncrementId = $shipmentData['orderIncrementId'];
                $addressId = $shipmentData['addressId'];
                $shipmentMethodData = $shipmentData['shipping_method'];
                $subscriptionId = $shipmentData['subscription_id'];
                $currentSubscription = $this->getSubscription($subscriptionId);

                $addressData = $this->getAddressData($addressId);

                $subscriptionOldInfo = $this->editSubscriptionRepository->getSubscriptionData($subscriptionId);
                $shippingOldAddress = $subscriptionOldInfo->getShippingAddress();
                $transactionData = $this->getTransactionData($currentSubscription);
                $paymentMethod = $transactionData->getMethod();
                
                $this->updateAddress($currentSubscription, $addressData);
                $this->updateShipmentAmount($currentSubscription, $shipmentMethodData);
                $this->editSubscriptionRepository
                    ->updateEditHistory(
                        $subscriptionOldInfo,
                        $shippingOldAddress,
                        $paymentMethod
                    );
                $shipmentUpdateMsg = [
                    'success'=> true,
                    'msg'=> 'Shipping data updated'
                ];
            }
        } catch (\Exception $e) {
            $shipmentUpdateMsg = [
                'success'=> false,
                'msg'=> $e->getMessage()
            ];
        }
        return json_encode($shipmentUpdateMsg);
    }

    /**
     * Update Address
     *
     * @param object $subscriptionObj
     * @param mixed $addressData
     *
     * @return mixed
     */
    public function updateAddress($subscriptionObj, $addressData)
    {
        $subscription = $subscriptionObj->getShippingAddress();
        try {

            if ($addressData->getFirstname() != $subscription->getFirstname() ||
                $addressData->getLastname() != $subscription->getLastname()
            ) {
                $subscription->setFirstname($addressData->getFirstname());
                $subscription->setLastname($addressData->getLastname());
                $subscriptionObj->setBillingName($addressData->getFirstname() . ' ' . $addressData->getLastname());
            }

            if ($addressData->getStreet() != $subscription->getStreet()) {
                $subscription->setStreet($addressData->getStreet());
            }

            if ($addressData->getCity() != $subscription->getCity()) {
                $subscription->setCity($addressData->getCity());
            }

            /* 'region_id' may not be set for some countries */
            $regionId = $addressData->getRegionId();
            if (!empty($regionId) && $regionId != $subscription->getRegionId()) {
                $region = $this->regionCollectionFactory
                    ->create()
                    ->getItemById((int) $regionId);

                $subscription->setRegionId($region->getRegionId());
                $subscription->setRegion($region->getName());
            } else {
                $subscription->setRegionId($regionId);
                $subscription->setRegion('');
            }

            if ($addressData->getPostcode() != $subscription->getPostcode()) {
                $subscription->setPostcode($addressData->getPostcode());
            }

            if ($addressData->getCountryId() != $subscription->getCountryId()) {
                $subscription->setCountryId($addressData->getCountryId());
            }
          
            if ($subscriptionObj->getShippingAddress()->hasDataChanges()) {
                $subscriptionObj->setHasDataChanges(true);
            }
            if ($subscriptionObj->hasDataChanges()) {
                $subscriptionObj->save();
            }
        } catch (\Exception $e) {
            throw $e->getMessage();
        }
    }

    /**
     * Transaction Data
     *
     * @param Subscription $subscriptionOrder
     *
     * @return mixed
     */
    public function getTransactionData(\Sapient\Worldpay\Model\Recurring\Subscription $subscriptionOrder)
    {
        return $subscriptionOrder->getTransactionData();
    }
    /**
     * Update Shipping amount
     *
     * @param object $subscriptionObj
     * @param object $shipmentMethodData
     */
    public function updateShipmentAmount($subscriptionObj, $shipmentMethodData)
    {
        $shipmentPrice = $shipmentMethodData['price'];
        $shippingMethod = $shipmentMethodData['carrier'].'_'.$shipmentMethodData['method'];
        $shippingMethodTitle = $shipmentMethodData['carrier_title'].'-'.$shipmentMethodData['method_title'];
        $subscriptionObj->setData('shipping_method', $shippingMethod);
        $subscriptionObj->setData('shipping_description', $shippingMethodTitle);
        $subscriptionObj->setData('shipping_amount', $shipmentPrice);
        $subscriptionObj->save();
    }
}
