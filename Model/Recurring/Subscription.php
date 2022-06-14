<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\Recurring;

use Sapient\Worldpay\Model\Config\Source\SubscriptionStatus;

/**
 * Recurring Plan
 *
 * @method int getStoreId()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setStoreId(int $value)
 * @method string getCreatedAt()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setCreatedAt(string $value)
 * @method string getUpdatedAt()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setUpdatedAt(string $value)
 * @method int getPlanId()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setPlanId(int $value)
 * @method float getIntervalAmount()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setIntervalAmount(float $value)
 * @method string getStartDate()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setStartDate(string $value)
 * @method string getEndDate()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setEndDate(string $value)
 * @method int getWorldpaySubscriptionId()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setWorldpaySubscriptionId(int $value)
 * @method int getCustomerId()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setCustomerId(int $value)
 * @method int getOriginalOrderId()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setOriginalOrderId(int $value)
 * @method string getOriginalOrderIncrementId()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setOriginalOrderIncrementId(string $value)
 * @method string getWorldpayOrderId()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setWorldpayOrderId(string $value)
 * @method string getWorldpayTokenId()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setWorldpayTokenId(string $value)
 * @method int getProductId()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setProductId(int $value)
 * @method string getProductName()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setProductName(string $value)
 * @method string getBillingName()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setBillingName(string $value)
 * @method string getShippingName()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setShippingName(string $value)
 * @method string getStatus()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setStatus(string $value)
 * @method float getDiscountAmount()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setDiscountAmount(float $value)
 * @method string getDiscountDescription()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setDiscountDescription(string $value)
 * @method float getShippingAmount()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setShippingAmount(float $value)
 * @method float getShippingTaxAmount()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setShippingTaxAmount(float $value)
 * @method float getSubtotal()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setSubtotal(float $value)
 * @method float getTaxAmount()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setTaxAmount(float $value)
 * @method float getSubtotalInclTax()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setSubtotalInclTax(float $value)
 * @method float getItemPrice()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setItemPrice(float $value)
 * @method float getItemOriginalPrice()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setItemOriginalPrice(float $value)
 * @method float getItemTaxPercent()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setItemTaxPercent(float $value)
 * @method float getItemTaxAmount()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setItemTaxAmount(float $value)
 * @method float getItemDiscountPercent()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setItemDiscountPercent(float $value)
 * @method float getItemDiscountAmount()
 * @method \Sapient\Worldpay\Model\Recurring\Subscription setItemDiscountAmount(float $value)
 */
class Subscription extends \Magento\Framework\Model\AbstractModel
{
    public const REGISTRY_NAME = 'current_worldpay_subscription';

    /**
     * Store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Sapient\Worldpay\Model\Recurring\Source\SubscriptionStatus
     */
    private $statusSource;

    /**
     * @var array
     */
    private $statuses;

    /**
     * @var \Sapient\Worldpay\Model\Recurring\PlanFactory
     */
    private $planFactory;

    /**
     * @var \Sapient\Worldpay\Model\Recurring\Plan
     */
    private $plan;

    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Address\CollectionFactory
     */
    private $addressCollectionFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var \Magento\Sales\Model\Order|bool
     */
    private $lastOrder;
    
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * Addresses array
     *
     * @var array
     */
    private $addresses;

    /**
     * Discounts array
     *
     * @var array
     */
    private $discounts;

    /**
     * Local cache for total amount to date
     *
     * @var array
     */
    private $totalAmountToDate = [];
    
    /**
     * @var \Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory
     */
    private $transactionsFactory;

    /**
     * Subscription model constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param SubscriptionStatus $statusSource
     * @param \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
     * @param CollectionFactory $addressCollectionFactory
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory $transactionsFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        SubscriptionStatus $statusSource,
        \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory,
        \Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Address\CollectionFactory
        $addressCollectionFactory,
        \Sapient\Worldpay\Helper\Recurring $recurringHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory $transactionsFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->storeManager = $storeManager;
        $this->statusSource = $statusSource;
        $this->planFactory = $planFactory;
        $this->recurringHelper = $recurringHelper;
        $this->addressCollectionFactory = $addressCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->priceCurrency = $priceCurrency;
        $this->transactionsFactory = $transactionsFactory;
    }

    /**
     * Subscription constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription::class);
    }
    /**
     * @inheritdoc
     */
    public function afterSave()
    {
        if ($this->getAddresses() !== null && is_array($this->getAddresses())) {
            foreach ($this->getAddresses() as $address) {
                $address->setSubscriptionId($this->getId())
                    ->save();
            }
        }
        return parent::afterSave();
    }

    /**
     * Retrieve associated array of all subscription statuses and their labels
     *
     * @return array
     */
    private function getStatuses()
    {
        if ($this->statuses === null) {
            $this->statuses = $this->statusSource->toOptionHash();
        }

        return $this->statuses;
    }

    /**
     * Format amount
     *
     * @param float $amount
     * @param int|null $storeId
     * @return string
     */
    private function formatAmount($amount, $storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getStoreId();
        }
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        return $this->storeManager->getWebsite($websiteId)->getBaseCurrency()->formatPrecision(
            $amount,
            2
        );
    }

    /**
     * Get formatted interval amount
     *
     * @return string
     */
    public function getFormattedIntervalAmount()
    {
        return $this->formatAmount($this->getIntervalAmount());
    }

    /**
     * Get formatted interval amount
     *
     * @return string
     */
    public function getFormattedPlanIntervalAmount()
    {
        $amount = $this->getPlan() ? $this->getPlan()->getIntervalAmount() : 0;
        return $this->formatAmount($amount);
    }

    /**
     * Get status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $statuses = $this->getStatuses();
        return isset($statuses[$this->getStatus()]) ? $statuses[$this->getStatus()] : '';
    }

    /**
     * Retrieve store model instance
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        $storeId = $this->getStoreId();
        if ($storeId) {
            return $this->storeManager->getStore($storeId);
        }
        return $this->storeManager->getStore();
    }

    /**
     * Get the plans data
     *
     * @return Plan
     */
    public function getPlan()
    {
        if ($this->plan === null) {
            $plan = false;
            if ($this->getPlanId()) {
                $plan = $this->planFactory->create()->load($this->getPlanId());
                if (!$plan->getId()) {
                    $plan = false;
                }
            }
            $this->plan = $plan;
        }

        return $this->plan;
    }

    /**
     * Set the plan data
     *
     * @param Plan $plan
     * @return $this
     */
    public function setPlan(Plan $plan)
    {
        $this->plan = $plan;
        return $this;
    }

    /**
     * Update subscription id in original order
     *
     * @return $this
     */
    public function updateOriginalOrderRelation()
    {
        if (!($this->getOriginalOrderId() && $this->getId())) {
            return $this;
        }
        $this->updateTransactionsData();
        $this->getResource()->updateOrderRelation($this->getOriginalOrderId(), $this->getId());
        return $this;
    }

    /**
     * Retrieve quote address collection
     *
     * @return \Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Address\Collection
     */
    public function getAddressesCollection()
    {
        $collection = $this->addressCollectionFactory->create()->setSubscriptionFilter($this);
        if ($this->getId()) {
            foreach ($collection as $address) {
                $address->setSubscription($this);
            }
        }
        return $collection;
    }

    /**
     * Retrieve customer address array
     *
     * @return \Sapient\Worldpay\Model\Recurring\Subscription\Address[]
     */
    public function getAddresses()
    {
        if ($this->addresses === null) {
            $this->addresses = $this->getAddressesCollection()->getItems();
        }
        return $this->addresses;
    }

    /**
     * Set customer addresses.
     *
     * @param array $addresses
     * @return $this
     */
    public function setAddresses($addresses)
    {
        $this->addresses = $addresses;
        return $this;
    }

    /**
     * Retrieve order billing address
     *
     * @return \Sapient\Worldpay\Model\Recurring\Subscription\Address|null
     */
    public function getBillingAddress()
    {
        foreach ($this->getAddresses() as $address) {
            if ($address->getAddressType() == 'billing' && !$address->isDeleted()) {
                return $address;
            }
        }
        return null;
    }

    /**
     * Retrieve order shipping address
     *
     * @return \Magento\Sales\Model\Order\Address|null
     */
    public function getShippingAddress()
    {
        foreach ($this->getAddresses() as $address) {
            if ($address->getAddressType() == 'shipping' && !$address->isDeleted()) {
                return $address;
            }
        }
        return null;
    }

    /**
     * @return \Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Addon\Collection
     */
//    public function getAddonCollection()
//    {
//        $collection = $this->addonCollectionFactory->create()->setSubscriptionFilter($this);
//        if ($this->getId()) {
//            /** @var Subscription\Addon $addon */
//            foreach ($collection as $addon) {
//                $addon->setSubscription($this);
//            }
//        }
//
//        return $collection;
//    }
//
//    /**
//     * @return Subscription\Addon[]
//     */
//    public function getAddonList()
//    {
//        if ($this->addons === null) {
//            $this->addons = $this->getAddonCollection()->getItems();
//        }
//
//        return $this->addons;
//    }
//
//    /**
//     * @param array $addonList
//     * @return $this
//     */
//    public function setAddonList(array $addonList)
//    {
//        $this->addons = $addonList;
//        return $this;
//    }
//
//    /**
//     * @param Subscription\Addon $addon
//     */
//    public function addAddon(Subscription\Addon $addon)
//    {
//        $addon->setSubscription($this);
//        $addonList = $this->getAddonList();
//        $addonList[] = $addon;
//
//        $this->setAddonList($addonList);
//    }

    /**
     * @return \Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Discount\Collection
     */
//    public function getDiscountCollection()
//    {
//        $collection = $this->discountCollectionFactory->create()->setSubscriptionFilter($this);
//        if ($this->getId()) {
//            /** @var Subscription\Discount $discount */
//            foreach ($collection as $discount) {
//                $discount->setSubscription($this);
//            }
//        }
//
//        return $collection;
//    }

    /**
     * @return Subscription\Discount[]
     */
//    public function getDiscountList()
//    {
//        if ($this->discounts === null) {
//            $this->discounts = $this->getDiscountCollection()->getItems();
//        }
//
//        return $this->discounts;
//    }

    /**
     * Set discount list
     *
     * @param array $discountList
     * @return $this
     */
    public function setDiscountList(array $discountList)
    {
        $this->discounts = $discountList;
        return $this;
    }

    /**
     * Add amount to existing discount amount
     *
     * @param Subscription\Discount $discount
     */
    public function addDiscount(Subscription\Discount $discount)
    {
        $discount->setSubscription($this);
        $discountList = $this->getDiscountList();
        $discountList[] = $discount;

        $this->setDiscountList($discountList);
    }

    /**
     * Retrieve last order associated with the subscription
     *
     * @return bool|\Magento\Sales\Api\Data\OrderInterface
     */
    public function getLastOrder()
    {
        if ($this->lastOrder === null) {
            $this->lastOrder = false;
            if ($this->getId()) {
                $sortOrder = $this->sortOrderBuilder->setField('created_at')->setDescendingDirection()->create();
                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('worldpay_subscription_id', $this->getId())
                    ->addSortOrder($sortOrder)
                    ->setPageSize(1)
                    ->setCurrentPage(1)
                    ->create();
                $orders = $this->orderRepository->getList($searchCriteria)->getItems();
                if ($orders && ($order = array_pop($orders)) && $order->getId()) {
                    $this->lastOrder = $this->orderRepository->get($order->getId());
                }
            }
        }

        return $this->lastOrder;
    }

    /**
     * Get product options array
     *
     * @return array
     */
    public function getProductOptions()
    {
        $data = $this->_getData('product_options');
        return is_string($data) ?
        \Magento\Framework\Serialize\SerializerInterface::unserialize($data) : $data;
    }

    /**
     * Change subscription plan, update all related fields
     *
     * @param int $newPlanId
     * @param bool $allowInactive
     * @return $this
     */
    public function changePlan($newPlanId, $allowInactive = false)
    {
        if ($newPlanId == $this->getPlanId()) {
            return $this;
        }

        $newPlan = $this->planFactory->create()->load($newPlanId);
        if (!$newPlan->getId()) {
            throw new \InvalidArgumentException('Plan doesn\'t exist.');
        }

        if ($newPlan->getProductId() != $this->getProductId()) {
            throw new \InvalidArgumentException('Plan doesn\'t belong to subscription product.');
        }

        if (!$allowInactive && !$newPlan->getActive()) {
            throw new \InvalidArgumentException('Plan is inactive.');
        }

        $this->setPlanId($newPlan->getId());
        $this->setPlanCode($newPlan->getCode());

        // calculate subscription amounts based on new plan
        if ($this->getIntervalAmount() !== null) {
            $originalPriceIncrease = $this->getItemOriginalPrice()
            && (($this->getItemPrice() - $this->getItemOriginalPrice()) > 0.0001)
                ? ($this->getItemPrice() - $this->getItemOriginalPrice()) : 0;
            $this->setItemPrice($newPlan->getIntervalAmount() + $originalPriceIncrease)
                ->setItemOriginalPrice($newPlan->getIntervalAmount());
            if ($this->getItemTaxPercent()) {
                $taxExtra = $this->getTaxAmount() - $this->getItemTaxAmount();
                $this->setItemTaxAmount(
                    $this->priceCurrency->round($this->getItemPrice() * $this->getItemTaxPercent() / 100)
                );
                $this->setTaxAmount($this->getItemTaxAmount() + $taxExtra);
            }
            $this->setSubtotal($this->getItemPrice())
                ->setSubtotalInclTax($this->getItemPrice() + $this->getItemTaxAmount());

//            $addonsAmount = 0;
//            foreach ($this->getAddonList() as $addon) {
//                switch ($addon->getCode()) {
//                    case Subscription\Addon::TAX_CODE:
//                        $addon->setAmount($this->getItemTaxAmount());
//                        break;
//                }
//                if ($addon->getIsSystem()) {
//                    $addonsAmount += $addon->getAmount();
//                }
//            }

            $this->setIntervalAmount($newPlan->getIntervalAmount() + $addonsAmount);
        } else {
            // interval_amount === null means that recurring order total should be equal to plan amount,
            // i.e. there is nothing extra (tax, shipping, etc.) and thus easier calculation
            $this->setItemPrice($newPlan->getIntervalAmount())
                ->setItemOriginalPrice($this->getItemPrice())
                ->setSubtotal($this->getItemPrice())
                ->setSubtotalInclTax($this->getItemPrice());
        }

        // delete all discounts
        $this->setItemDiscountAmount(0)
            ->setItemDiscountPercent(0)
            ->setDiscountAmount(0)
            ->setDiscountDescription('');
        foreach ($this->getDiscountList() as $discount) {
            $discount->isDeleted(true);
        }

        return $this;
    }

    /**
     * Create amount changelog record
     *
     * @param array $changeLogData
     * @return $this
     */
    public function addAmountChangelog($changeLogData)
    {
        if (!isset($changeLogData['subscription_id']) && $this->getId()) {
            $changeLogData['subscription_id'] = $this->getId();
        }
        $this->getResource()->addAmountChangelog($changeLogData);

        return $this;
    }

    /**
     * Retrieve subscription amount at given time
     *
     * @param \DateTime $dateTime
     * @return float|null
     */
    public function getTotalAmountToDate(\DateTime $dateTime)
    {
        $cacheKey = $dateTime->getTimestamp();
        if (!isset($this->totalAmountToDate[$cacheKey])) {
            $this->totalAmountToDate[$cacheKey] = $this->getResource()->getTotalAmountToDate($this, $dateTime);
        }
        return $this->totalAmountToDate[$cacheKey];
    }

    /**
     * Recalculate subscription amounts for date
     *
     * @param \DateTime $dateTime
     * @return $this
     */
    public function recalculateAmountsToDate(\DateTime $dateTime)
    {
        $startDate = $this->getStartDate() ? $this->getStartDate() : $this->getCreatedAt();
        $startDate = new \DateTime($startDate);
        if ($startDate->getTimestamp() > $dateTime->getTimestamp()) {
            return $this;
        }

        $currentTotal = $this->getTotalAmountToDate($dateTime);
        if ($currentTotal === null) {
            return $this;
        }

        if ($currentTotal < 0) {
            $currentTotal = 0;
        }

        $originalTotal = $this->getIntervalAmount() !== null
            ? $this->getIntervalAmount() : $this->getPlan()->getIntervalAmount();
        $totalDiff = $currentTotal - $originalTotal;
        if (abs($totalDiff) < 0.0001) {
            return $this;
        }

        if ($totalDiff < 0) {
            // increase discount
            $this->setDiscountAmount($this->getDiscountAmount() + $totalDiff);
            $this->setItemDiscountAmount($this->getItemDiscountAmount() - $totalDiff);
            $discountDescription = (string)__('Subscription Discounts');
            $this->setDiscountDescription(
                $this->getDiscountDescription()
                    ? $this->getDiscountDescription() . ', ' . $discountDescription : $discountDescription
            );
        } else {
            // increase item price and tax
            $itemTaxDiff = 0;
            if ($this->getItemTaxPercent()) {
                $itemTaxDiff = $this->priceCurrency->round(
                    $totalDiff * (1 - 1 / (1 + $this->getItemTaxPercent() / 100))
                );
                $this->setItemTaxAmount($this->getItemTaxAmount() + $itemTaxDiff);
                $this->setTaxAmount($this->getTaxAmount() + $itemTaxDiff);
                $this->setItemPrice($this->getItemPrice() + $totalDiff - $itemTaxDiff);
            } else {
                $this->setItemPrice($this->getItemPrice() + $totalDiff);
            }
            $this->setSubtotal($this->getSubtotal() + $totalDiff - $itemTaxDiff);
            $this->setSubtotalInclTax($this->getSubtotalInclTax() + $totalDiff);
        }
        $this->setIntervalAmount($currentTotal);

        return $this;
    }
    
     /**
      * Check is subscriptions functionality enabled globally and product is of supported type
      *
      * @return bool
      */
    public function isEndDateEnabled()
    {
        return $this->recurringHelper->getSubscriptionValue('worldpay/subscriptions/endDate');
    }
    
    /**
     * Update recurring transactions data
     *
     * @return $this
     */
    public function updateTransactionsData()
    {
        $startDate = '';
        if ($this->getStartDate()) {
            $startDate = $this->getStartDate();
        }
        $endDate = '';
        if ($this->getEndDate()) {
            $endDate = $this->getEndDate();
        } else {
             $endDate = $this->getStartDate();
        }
        $transactionData = ['subscription_id'=>$this->getId(),
                'customer_id'   =>  $this->getCustomerId(),
                'original_order_id' =>  $this->getOriginalOrderId(),
                'original_order_increment_id'   =>  $this->getOriginalOrderIncrementId(),
                'plan_id'   =>  $this->getPlanId(),
                'recurring_date'   =>  $startDate,
                'recurring_end_date'   =>  $endDate,
                'email'=>$this->getCustomerEmail(),
                'status'=>'active',
                'recurring_order_id' => $this->getOriginalOrderId()];
        $transactions = $this->transactionsFactory->create()
                ->loadByOrderIncrementId($this->getOriginalOrderIncrementId());
        $transactions->setData('subscription_id', $this->getId());
        $transactions->setData('customer_id', $this->getCustomerId());
        $transactions->setData('original_order_id', $this->getOriginalOrderId());
        //$transactions->setData('worldpay_token_id', $this->getWorldpayTokenId());
        //$transactions->setData('worldpay_order_id', $this->getWorldpayOrderId());
        $transactions->setData('original_order_increment_id', $this->getOriginalOrderIncrementId());
        $transactions->setData('plan_id', $this->getPlanId());
        $transactions->setData('recurring_date', $startDate);
        $transactions->setData('recurring_end_date', $endDate);
        $transactions->setData('email', $this->getCustomerEmail());
        $transactions->setData('status', 'active');
        $transactions->setData('recurring_order_id', $this->getOriginalOrderId());
        $transactions->save();
    }
    
    /**
     * Getter for order state
     *
     * @param string $orderId
     * @return string
     */
    public function getOrderStatus($orderId)
    {
        $order = $this->orderRepository->get($orderId);
        $state = $order->getState(); //Get Order State(Complete, Processing, ....)
        return $state;
    }
    
    /**
     * Load subscription Details
     *
     * @param string $order_id
     */
    public function loadByOrderId($order_id)
    {
        if (!$order_id) {
            return;
        }
        $id = $this->getResource()->loadByOriginalOrderIncrementId($order_id);
            return $this->load($id);
    }
}
