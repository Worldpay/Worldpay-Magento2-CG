<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Helper;

use Sapient\Worldpay\Model\Config\Source\Interval;
use Sapient\Worldpay\Model\Config\Source\TrialInterval;
use Sapient\Worldpay\Model\Recurring\Subscription\Transactions;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Recurring extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const PENDING_RECURRING_PAYMENT_ORDER_STATUS = 'pending_recurring_payment';
    public const RECURRING_ORDER_SKIP_DAYS_UPTO = 9;

    /**
     * Product type ids supported to act as subscriptions
     *
     * @var array
     */
    private $allowedProductTypeIds = [
        \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
        \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL
    ];

    /**
     * @var \Sapient\Worldpay\Model\Recurring\PlanFactory
     */
    private $planFactory;

    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\Recurring\Plan\CollectionFactory
     */
    private $plansCollectionFactory;

    /**
     * @var Interval
     */
    private $intervalSource;

    /**
     * @var TrialInterval
     */
    private $trialIntervalSource;

    /**
     * @var array
     */
    private $intervals;

    /**
     * @var array
     */
    private $trialIntervals;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * Scope configuration instance.
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig = null;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var curlHelper
     */
    protected $curlHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Sales\Model\Service\OrderService
     */
    protected $orderService;

    /**
     * @var \Magento\Quote\Model\Quote\Payment
     */
    protected $payment;

     /**
      * @var \Magento\Checkout\Model\Cart
      */
    protected $cart;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Integration\Model\Oauth\TokenFactory
     */
    protected $tokenModelFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quote;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Integration\Model\Oauth\TokenFactory
     */
    protected $_tokenModelFactory;

    /**
     * @var $orderRepository
     */
    protected $orderRepository;
    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $_addressConfig;

    /**
     * @var CollectionFactory
     */
    private $transactionCollectionFactory;

    /**
     * Recurring constructor
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
     * @param \Sapient\Worldpay\Model\ResourceModel\Recurring\Plan\CollectionFactory $plansCollectionFactory
     * @param Interval $intervalSource
     * @param TrialInterval $trialIntervalSource
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\Data\Form\FormKey $formkey
     * @param \Magento\Quote\Model\QuoteFactory $quote
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Model\Service\OrderService $orderService
     * @param \Magento\Quote\Model\Quote\Payment $payment
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Integration\Model\Oauth\TokenFactory $tokenModelFactory
     * @param SerializerInterface $serializer
     * @param \Sapient\Worldpay\Helper\CurlHelper $curlHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param Transactions $recurringTransactions
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory,
        \Sapient\Worldpay\Model\ResourceModel\Recurring\Plan\CollectionFactory $plansCollectionFactory,
        Interval $intervalSource,
        TrialInterval $trialIntervalSource,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\Data\Form\FormKey $formkey,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Quote\Model\Quote\Payment $payment,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Integration\Model\Oauth\TokenFactory $tokenModelFactory,
        SerializerInterface $serializer,
        \Sapient\Worldpay\Helper\CurlHelper $curlHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Customer\Model\Address\Config $addressConfig,
        Transactions $recurringTransactions
    ) {
        parent::__construct($context);
        $this->plansCollectionFactory = $plansCollectionFactory;
        $this->planFactory = $planFactory;
        $this->intervalSource = $intervalSource;
        $this->trialIntervalSource = $trialIntervalSource;
        $this->escaper = $escaper;
        $this->localeDate = $localeDate;
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->payment = $payment;
        $this->_customerSession = $customerSession;
        $this->_tokenModelFactory = $tokenModelFactory;
        $this->serializer = $serializer;
        $this->curlHelper = $curlHelper;
        $this->orderRepository = $orderRepository;
        $this->_addressConfig = $addressConfig;
        $this->transactionCollectionFactory = $recurringTransactions;
    }

    /**
     * Get supported product type ids
     *
     * @return array
     */
    public function getAllowedProductTypeIds()
    {
        return $this->allowedProductTypeIds;
    }

    /**
     * Plan intervals getter
     *
     * @return array
     */
    private function getIntervals()
    {
        if ($this->intervals === null) {
            $this->intervals = $this->intervalSource->toOptionHash();
        }

        return $this->intervals;
    }

    /**
     * Plan trial intervals getter
     *
     * @return array
     */
    private function getTrialIntervals()
    {
        if ($this->trialIntervals === null) {
            $this->trialIntervals = $this->trialIntervalSource->toOptionHash();
        }

        return $this->trialIntervals;
    }

    /**
     * Get plan interval label
     *
     * @param string $intervalCode
     * @return string
     */
    public function getPlanIntervalLabel($intervalCode)
    {
        $intervals = $this->getIntervals();
        return isset($intervals[$intervalCode]) ? $intervals[$intervalCode] : '';
    }

    /**
     * Get trial interval label
     *
     * @param string $trialIntervalCode
     * @return string
     */
    public function getPlanTrialIntervalLabel($trialIntervalCode)
    {
        $trialIntervals = $this->getTrialIntervals();
        return isset($trialIntervals[$trialIntervalCode]) ? $trialIntervals[$trialIntervalCode] : '';
    }

    /**
     * Build plan option title
     *
     * @param \Sapient\Worldpay\Model\Recurring\Plan $plan
     * @param string $renderedPrice
     * @return string
     */
    public function buildPlanOptionTitle(\Sapient\Worldpay\Model\Recurring\Plan $plan, $renderedPrice = '')
    {
        $planInterval = strtolower($this->getPlanIntervalLabel($plan->getInterval()));
        $trialInfo = '';
        if ($plan->getNumberOfTrialIntervals() && $plan->getTrialInterval()) {
            $trialInfo = ', ' . $plan->getNumberOfTrialIntervals()
                . ' ' . strtolower($this->getPlanTrialIntervalLabel($plan->getTrialInterval())) . __('(s)')
                . ' ' . __('trial');
        }
        $maxPaymentsInfo = '';
        if ($plan->getNumberOfPayments()) {
            $maxPaymentsInfo = ', ' . $plan->getNumberOfPayments() . ' ' . __(' payments max');
        }

        return ($renderedPrice ? $renderedPrice . ' ' : '')
        . $this->escaper->escapeHtml(__('paid') . ' ' . $planInterval . $trialInfo . $maxPaymentsInfo);
    }

    /**
     * Build plan option id
     *
     * @param \Sapient\Worldpay\Model\Recurring\Plan $plan
     * @return string
     */
    public function buildPlanOptionId(\Sapient\Worldpay\Model\Recurring\Plan $plan)
    {
        $planInterval = strtolower($this->getPlanIntervalLabel($plan->getInterval()));
        return $this->escaper->escapeHtml($planInterval);
    }

    /**
     * Retrieve selected plan for product
     *
     * @param Product $product
     * @return \Sapient\Worldpay\Model\Recurring\Plan|false
     */
    public function getSelectedPlan(Product $product)
    {
        $planOption = $product->getCustomOption('worldpay_subscription_plan_id');
        if (!($planOption && $planOption->getValue())) {
            return false;
        }

        $planId = $planOption->getValue();
        if ($product->getWorldpaySubscriptionPlansCache($planId) === null) {
            $plan = $this->planFactory->create()->load($planId);
            $plansCache = $product->getWorldpaySubscriptionPlansCache() ?: [];
            $plansCache[$planId] = $plan->getId() ? $plan : false;
            $product->setWorldpaySubscriptionPlansCache($plansCache);
        }

        return $product->getWorldpaySubscriptionPlansCache($planId);
    }

    /**
     * Get array with selected plan information
     *
     * @param Product $product
     * @return array
     */
    public function getSelectedPlanOptionInfo(Product $product)
    {
        $planInfo = [];
        $plan = $this->getSelectedPlan($product);
        if ($plan) {
            $planInfo = [
                [
                    'label'   => __('Subscription Details'),
                    'value'   => $this->buildPlanOptionTitle($plan),
                    'plan_id' => $plan->getId(),
                ]
            ];
        }
        return $planInfo;
    }

    /**
     * Get trial info in one label
     *
     * @param \Sapient\Worldpay\Model\Recurring\Subscription $subscription
     * @return string
     */
    public function getSubscriptionTrialLabel(\Sapient\Worldpay\Model\Recurring\Subscription $subscription)
    {
        return $this->getTrialLabel($subscription->getNumberOfTrialIntervals(), $subscription->getTrialInterval());
    }

    /**
     * Get trial info in one label
     *
     * @param \Sapient\Worldpay\Model\Recurring\Plan $plan
     * @return string
     */
    public function getPlanTrialLabel(\Sapient\Worldpay\Model\Recurring\Plan $plan)
    {
        return $this->getTrialLabel($plan->getNumberOfTrialIntervals(), $plan->getTrialInterval());
    }

    /**
     * Build trial label (number of trial intervals and interval label combined)
     *
     * @param string $numberOfTrialIntervals
     * @param mixed $trialInterval
     * @return string
     */
    private function getTrialLabel($numberOfTrialIntervals, $trialInterval)
    {
        $label = '';
        if ($numberOfTrialIntervals && $trialInterval) {
            $label = $numberOfTrialIntervals . ' ' . $this->getPlanTrialIntervalLabel($trialInterval) . __('(s)');
        }
        return $label;
    }

    /**
     * Check is quote contains subscription item
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    public function quoteContainsSubscription(\Magento\Quote\Model\Quote $quote)
    {
        foreach ($quote->getAllItems() as $item) {
            if (($product = $item->getProduct())
                && $this->getSelectedPlan($product)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve selected subscription plan id from order item
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return int|false
     */
    public function getOrderItemPlanId(\Magento\Sales\Model\Order\Item $item)
    {
        $planId = false;
        $productOption = $item->getProductOptionByCode('worldpay_subscription_options');
        if (is_array($productOption) && isset($productOption['plan_id'])) {
            $planId = $productOption['plan_id'];
        }
        return $planId;
    }

    /**
     * Retrieve selected subscription plan from order item
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return \Sapient\Worldpay\Model\Recurring\Plan
     */
    public function getOrderItemPlan(\Magento\Sales\Model\Order\Item $item)
    {
        if ($item->getWorldpaySubscriptionPlan() === null) {
            $item->setWorldpaySubscriptionPlan(false);
            $planId = $this->getOrderItemPlanId($item);
            if ($planId) {
                $plan = $this->planFactory->create()->load($planId);
                if ($plan->getId()) {
                    $item->setWorldpaySubscriptionPlan($plan);
                }
            }
        }

        return $item->getWorldpaySubscriptionPlan();
    }

    /**
     * Get the first payment date
     *
     * @param int|string|\DateTimeInterface $createdAt
     * @param int|string|\DateTimeInterface $startDate
     * @param string $numTrialIntervals
     * @param string $trialInterval
     * @return \DateTime
     */
    public function getFirstPaymentDate($createdAt, $startDate, $numTrialIntervals, $trialInterval)
    {
        $date = $startDate ?: $createdAt;

        $firstPaymentDate = new \DateTime($date, new \DateTimeZone('UTC'));

        if ($numTrialIntervals && $trialInterval) {
            $interval = null;
            switch ($trialInterval) {
                case TrialInterval::DAY:
                    $interval = 'D';
                    break;
                case TrialInterval::MONTH:
                    $interval = 'M';
                    break;
            }

            if ($interval) {
                $firstPaymentDate->add(new \DateInterval('P' . $numTrialIntervals . $interval));
            }
        }

        return $firstPaymentDate;
    }

    /**
     * Retrieve product subscription plans
     *
     * @param Product $product
     * @return array|\Sapient\Worldpay\Model\ResourceModel\Recurring\Plan\CollectionFactory
     */
    public function getProductSubscriptionPlans(Product $product)
    {
        if ($product->getWorldpaySubscriptionPlans() === null) {
            $collection = $this->plansCollectionFactory->create()->addProductFilter($product)->addActiveFilter();
            foreach ($collection as $plan) {
                $plan->setProduct($product);
            }
            $product->setWorldpaySubscriptionPlans($collection);
        }

        return $product->getWorldpaySubscriptionPlans();
    }

    /**
     * Retrieve information from worldpay configuration.
     *
     * @param string $field
     * @param int|null $storeId
     * @param string $scopeType
     * @return bool
     */
    public function getSubscriptionValue($field, $storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue($field, $scopeType, $storeId);
    }

    /**
     * Get array with selected plan start date information
     *
     * @param Product $product
     * @return array|bool
     */
    public function getSelectedPlanStartDateOptionInfo(Product $product)
    {
        $startDateInfo = [];

        $startDateOption = $product->getCustomOption('subscription_date');
        if ($startDateOption && $startDateOption->getValue() && $product->getWorldpayRecurringAllowStart()) {

                $startDate = $startDateOption->getValue() ? $startDateOption->getValue() : date('d-m-yy');

            if (isset($startDateOption)) {
                $startDateInfo = [
                    [
                        'label' => __('Subscription Start Date'),
                        'value' => $startDate
                    ]
                ];
            }
        }

        return $startDateInfo;
    }

     /**
      * Get array with selected plan start date information
      *
      * @param Product $product
      * @return array|bool
      */
    public function getSelectedPlanEndDateOptionInfo(Product $product)
    {
        $endDateInfo = [];

        $endDateOption = $product->getCustomOption('subscription_end_date');
        if ($endDateOption && $endDateOption->getValue()) {

                $endDate = $endDateOption->getValue() ? $endDateOption->getValue() : date('d-m-yy');

            if (isset($endDateOption)) {
                $endDateInfo = [
                    [
                        'label' => __('Subscription End Date'),
                        'value' => $endDate
                    ]
                ];
            }
        }

        return $endDateInfo;
    }

    /**
     * Retrieve subscription options for order item
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return array
     */
    public function prepareOrderItemOptions(\Magento\Sales\Model\Order\Item $item)
    {
        $result = [];
        if (($options = $item->getProductOptions())
            && isset($options['worldpay_subscription_options']['options_to_display'])
        ) {
            $optionsToDisplay = $options['worldpay_subscription_options']['options_to_display'];
            if (isset($optionsToDisplay['subscription_details'])) {
                $result = array_merge($result, $optionsToDisplay['subscription_details']);
            }
            if (isset($optionsToDisplay['subscription_date'])) {
                $result = array_merge($result, $optionsToDisplay['subscription_date']);
            }
            if (isset($optionsToDisplay['subscription_end_date'])) {
                $result = array_merge($result, $optionsToDisplay['subscription_end_date']);
            }
        }

        return $result;
    }

    /**
     * Retrieve selected subscription start date from order item
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return string
     */
    public function getOrderItemSubscriptionStartDate(\Magento\Sales\Model\Order\Item $item)
    {
        $startDate = '';
        $productOption = $item->getProductOptionByCode('worldpay_subscription_options');
        $productBuyOptions = $item->getProductOptionByCode('info_buyRequest');
        if (is_array($productOption) && is_array($productBuyOptions) &&
                isset($productBuyOptions['subscription_date']) ||
                isset($productOption['subscription_date'])) {
            $startDate = isset($productOption['subscription_date'])?$productOption['subscription_date']
                    :$productBuyOptions['subscription_date'];
        }

        return $startDate;
    }

     /**
      * Retrieve selected subscription start date from order item
      *
      * @param \Magento\Sales\Model\Order\Item $item
      * @return string
      */
    public function getOrderItemSubscriptionEndDate(\Magento\Sales\Model\Order\Item $item)
    {
        $endDate = '';
        $productOption = $item->getProductOptionByCode('worldpay_subscription_options');
        $productBuyOptions = $item->getProductOptionByCode('info_buyRequest');
        if (is_array($productOption) && is_array($productBuyOptions) &&
                (isset($productBuyOptions['subscription_end_date']) ||
                isset($productOption['subscription_end_date']))) {
            $endDate = isset($productOption['subscription_end_date'])?$productOption['subscription_end_date']
                    :$productBuyOptions['subscription_end_date'];
        }

        return $endDate;
    }

     /**
      * Create order in magento
      *
      * @param array $orderData
      * @param array $paymentData
      * @return string
      */
    public function createMageOrder($orderData, $paymentData)
    {
        $this->_storeManager->setCurrentStore($orderData['store_id']);
        $store = $this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($orderData['email']);// load customet by email address
        $customerToken = $this->_tokenModelFactory->create();
        $tokenKey = $customerToken->createCustomerToken($customer->getId())->getToken();
        $quoteId = $this->createEmptyQuote($tokenKey);
        $itemData = [];
        $itemData['cartItem'] = ['sku' => $orderData['product_sku'],
            'qty' => $orderData['qty'], 'quote_id' => $quoteId];
        $itemResponse = $this->addItemsToQuote($tokenKey, json_encode($itemData), $quoteId);

        $cartId = $quoteId;
        $itemId = $itemResponse['item_id'];
        $price = $orderData['item_price'];
        $this->updateCartItem($cartId, $itemId, $price, $orderData['qty']);
        $addressData = [];
        $addressData['address'] = $orderData['shipping_address'];
        if (!empty($orderData['shipping_method'])) {
            $savedShippingMethod = explode('_', $orderData['shipping_method']);
            $shippingMethodResponse = $this->getShippingMethods($tokenKey, json_encode($addressData));
            foreach ($shippingMethodResponse as $shippingMethod) {
                if ($shippingMethod['carrier_code'] == $savedShippingMethod[0]) {
                    $shippingCarrierCode = $shippingMethod['carrier_code'];
                    $shippingMethodCode = $shippingMethod['method_code'];
                }
            }
            $shippingInformation = [];
            $shippingInformation['addressInformation']['shipping_address'] = $orderData['shipping_address'];
            $shippingInformation['addressInformation']['billing_address'] = $orderData['billing_address'];
            $shippingInformation['addressInformation']['shipping_carrier_code'] = $shippingCarrierCode;
            $shippingInformation['addressInformation']['shipping_method_code'] = $shippingMethodCode;
            $shippingResponse = $this->setShippingInformation($tokenKey, json_encode($shippingInformation));
        }
        $paymentData = json_encode($paymentData);
        $orderId = $this->orderPayment($tokenKey, $paymentData);
        return $orderId;
    }

    /**
     * Create empty quote
     *
     * @param string $tokenKey
     * @return array
     */
    public function createEmptyQuote($tokenKey)
    {
        $token = 'Bearer '.$tokenKey;
        $apiUrl = $this->_storeManager->getStore()->getUrl('rest/default/V1/carts/mine');
        return $this->curlHelper->sendCurlRequest(
            $apiUrl,
            [
                    CURLOPT_URL => $apiUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS =>'',
                    CURLOPT_HTTPHEADER => [
                        "Authorization: $token",
                        "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, "
                    . "like Gecko) Chrome/81.0.4044.138 Safari/537.36",
                        "Content-Type: application/json"
                    ],
            ]
        );
    }

    /**
     * Add items to quote
     *
     * @param string $tokenKey
     * @param array $itemData
     * @param string $quoteId
     * @return mixed Returns the JSON decoded data. Note that JSON objects are
     *     decoded as associative arrays.
     */
    public function addItemsToQuote($tokenKey, $itemData, $quoteId)
    {
        $token = 'Bearer '.$tokenKey;
        $apiUrl = '';
        $apiUrl = $this->_storeManager->getStore()->getUrl('rest/default/V1/carts/mine/');
        $response = $this->curlHelper->sendCurlRequest(
            $apiUrl,
            [
                CURLOPT_URL => $apiUrl.'items?cart_id='.$quoteId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $itemData,
                CURLOPT_HTTPHEADER => [
                  "Authorization: $token",
                  "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 "
                    . "(KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36",
                  "Content-Type: application/json"
                ],
            ]
        );
        return json_decode($response ?? '', true);
    }

    /**
     * Update cart items
     *
     * @param string $cartId
     * @param int $itemId
     * @param float $price
     * @param int $qty
     * @return bool
     */
    public function updateCartItem($cartId, $itemId, $price, $qty)
    {
        if ($price) {
            $quote = $this->quote->create()->load($cartId);
            $quoteItem = $quote->getItemById($itemId);
            $quoteItem->setQty($qty);
            $quoteItem->setCustomPrice($price);
            $quoteItem->setName($price);
            $quoteItem->setOriginalCustomPrice($price);
            $quoteItem->getProduct()->setIsSuperMode(true);
            $quoteItem->save();
            $quote->save();
        }
        return true;
    }

    /**
     * Get list of available shipping methods
     *
     * @param string $tokenKey
     * @param array $addressData
     * @return mixed Returns the JSON decoded data. Note that JSON objects are
     *     decoded as associative arrays.
     */
    public function getShippingMethods($tokenKey, $addressData)
    {
        $token = 'Bearer '.$tokenKey;
        $apiUrl = $this->_storeManager->getStore()->getUrl('rest/default/V1/carts/mine/');
        $response = $this->curlHelper->sendCurlRequest(
            $apiUrl,
            [
                CURLOPT_URL => $apiUrl.'estimate-shipping-methods',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $addressData,
                CURLOPT_HTTPHEADER => [
                  "Authorization: $token",
                  "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 "
                    . "(KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36",
                  "Content-Type: application/json"
                ],
            ]
        );
        return json_decode($response ?? '', true);
    }

    /**
     * Set shipping information
     *
     * @param string $tokenKey
     * @param string $shippingInformation
     * @return mixed Returns the JSON decoded data. Note that JSON objects are
     *     decoded as associative arrays.
     */
    public function setShippingInformation($tokenKey, $shippingInformation)
    {
        $token = 'Bearer '.$tokenKey;
        $apiUrl = $this->_storeManager->getStore()->getUrl('rest/default/V1/carts/mine/');
        $response = $this->curlHelper->sendCurlRequest(
            $apiUrl,
            [
                CURLOPT_URL => $apiUrl.'shipping-information',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $shippingInformation,
                CURLOPT_HTTPHEADER => [
                  "Authorization: $token",
                  "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 "
                    . "(KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36",
                  "Content-Type: application/json"
                ],
              ]
        );
        return json_decode($response ?? '', true);
    }

    /**
     * Order payment
     *
     * @param string $tokenKey
     * @param string $paymentData
     * @return mixed Returns the JSON decoded data. Note that JSON objects are
     *     decoded as associative arrays.
     */
    public function orderPayment($tokenKey, $paymentData)
    {
        $token = 'Bearer '.$tokenKey;
        $apiUrl = $this->_storeManager->getStore()->getUrl('rest/default/V1/carts/mine/');
        $response = $this->curlHelper->sendCurlRequest(
            $apiUrl,
            [
                CURLOPT_URL => $apiUrl.'payment-information',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS =>$paymentData,
                CURLOPT_HTTPHEADER => [
                  "Authorization: $token",
                  "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 "
                    . "(KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36",
                  "Content-Type: application/json"
                ],
            ]
        );
        return json_decode($response ?? '', true);
    }

    /**
     * Get Buy one time label text
     *
     * @return string
     */
    public function getBuyOneTimelabel()
    {
        $label = $this->scopeConfig->getValue(
            'worldpay/subscriptions/buy_onetime_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$label) {
            $label = __('Buy one time or select a Payment Plan');
        }

        return $label;
    }

    /**
     * Get subscribe checkbox label text
     *
     * @return string
     */
    public function getSubscribeCheckboxLabel()
    {
        $label = $this->scopeConfig->getValue(
            'worldpay/subscriptions/subscribe_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$label) {
            $label = __('Subscribe this product and save');
        }

        return $label;
    }

    /**
     * Get start date label text
     *
     * @return string
     */
    public function getStartDateLabel()
    {
        $label = $this->scopeConfig->getValue(
            'worldpay/subscriptions/start_date_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$label) {
            $label = __('Subscription Start Date');
        }

        return $label;
    }

    /**
     * Get end date label text
     *
     * @return string
     */
    public function getEndDateLabel()
    {
        $label = $this->scopeConfig->getValue(
            'worldpay/subscriptions/end_date_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$label) {
            $label = __('Subscription End Date');
        }

        return $label;
    }
    /**
     * Get the list of admin labels
     *
     * @param string $labelCode
     * @param Store $store
     * @param string|null $scope
     * @return string
     */
    public function getAdminLabels($labelCode, $store = null, $scope = null)
    {
        $customAdminLabel = $this->scopeConfig->getValue(
            'worldpay_custom_labels/admin_labels/admin_label',
            $scope === 'website'
                ? ScopeInterface::SCOPE_WEBSITE
                : ScopeInterface::SCOPE_STORE,
            $store
        );
        $adminLabels = '';
        if (!empty($customAdminLabel)) {
            $adminLabels = $this->serializer->unserialize($customAdminLabel);
        }

        if (is_array($adminLabels) || is_object($adminLabels)) {
            foreach ($adminLabels as $key => $valuepair) {
                if ($key == $labelCode) {
                    return $valuepair['wpay_custom_label'] ?: $valuepair['wpay_label_desc'];
                }
            }
        }

        return '';
    }

    /**
     * Get account label by code
     *
     * @param string $labelCode
     * @return string
     */
    public function getAccountLabelbyCode($labelCode)
    {
        $aLabels = $this->serializer->unserialize(
            $this->scopeConfig->getValue(
                'worldpay_custom_labels/my_account_labels/my_account_label',
                ScopeInterface::SCOPE_STORE
            )
        );
        if (is_array($aLabels) || is_object($aLabels)) {
            foreach ($aLabels as $key => $valuepair) {
                if ($key == $labelCode) {
                    return $valuepair['wpay_custom_label'] ?: __($valuepair['wpay_label_desc']);
                }
            }
        }

        return '';
    }

    /**
     * Get checkout label by code
     *
     * @param string $labelCode
     * @return string
     */
    public function getCheckoutLabelbyCode($labelCode)
    {
        $aLabels = $this->serializer->unserialize(
            $this->scopeConfig->getValue(
                'worldpay_custom_labels/checkout_labels/checkout_label',
                ScopeInterface::SCOPE_STORE
            )
        );
        if (is_array($aLabels) || is_object($aLabels)) {
            foreach ($aLabels as $key => $valuepair) {
                if ($key == $labelCode) {
                    return $valuepair['wpay_custom_label'] ?: __($valuepair['wpay_label_desc']);
                }
            }
        }

        return '';
    }

    /**
     * Get the list of my account exceptions
     *
     * @param string $exceptioncode
     * @return array
     */
    public function getMyAccountExceptions($exceptioncode)
    {
        $accdata = $this->serializer->unserialize(
            $this->scopeConfig->getValue(
                'worldpay_exceptions/my_account_alert_codes/response_codes',
                ScopeInterface::SCOPE_STORE
            )
        );
        if (is_array($accdata) || is_object($accdata)) {
            foreach ($accdata as $key => $valuepair) {
                if ($key == $exceptioncode) {
                    return $valuepair['exception_module_messages'] ?: __($valuepair['exception_messages']);
                }
            }
        }
    }

    /**
     * Get the list of checkout exceptions
     *
     * @param string $exceptioncode
     * @return array
     */
    public function getCheckoutExceptions($exceptioncode)
    {
        $ccdata = $this->serializer->unserialize(
            $this->scopeConfig->getValue(
                'worldpay_exceptions/ccexceptions/cc_exception',
                ScopeInterface::SCOPE_STORE
            )
        );
        if (is_array($ccdata) || is_object($ccdata)) {
            foreach ($ccdata as $key => $valuepair) {
                if ($key == $exceptioncode) {
                    return $valuepair['exception_module_messages'] ?: $valuepair['exception_messages'];
                }
            }
        }
    }

    /**
     * Is worldpay enable?
     *
     * @return bool
     */
    public function isWorldpayEnable()
    {
        return (bool) $this->scopeConfig->getValue(
            'worldpay/general_config/enable_worldpay',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Recurring Order Buffer Time
     *
     * @return string
     */
    public function getRecurringOrderBufferTime()
    {
        return $this->scopeConfig->getValue(
            'worldpay/subscriptions/recurring_order_buffer_time',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Recurring Order Reminder Email Template
     *
     * @return string
     */
    public function getRecurringOrderReminderEmail()
    {
        return $this->scopeConfig->getValue(
            'worldpay/subscriptions/recurring_order_reminder_email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Order Details
     *
     * @param int $orderId
     */
    public function getOrderDetails($orderId)
    {
        $order = $this->orderRepository->get($orderId);
        $billingAddress = $order->getBillingAddress()->getData();
        $formattedShippingAddress = '';
        if ($order->getIsNotVirtual()) {
            $shippingAddress = $order->getShippingAddress()->getData();
            $formattedShippingAddress = $this->getFormatAddressByCode($shippingAddress);
        }

        $paymentMethod = $order->getPayment()->getAdditionalInformation('method_title');
        $orderDetails = [
            'order' => $order,
            'order_id' => $order->getId(),
            'payment_html' => $paymentMethod,
            'formattedBillingAddress' => $this->getFormatAddressByCode($billingAddress),
            'formattedShippingAddress' => $formattedShippingAddress,
            'is_not_virtual' => $order->getIsNotVirtual()
        ];
        return $orderDetails;
    }

    /**
     * Format Shipping Address
     *
     * @param array $address
     * @return array
     */
    public function getFormatAddressByCode($address)
    {
        $renderer = $this->_addressConfig->getFormatByCode('html')->getRenderer();
        return $renderer->renderArray($address);
    }

    /**
     * Get next recurring order
     *
     * @param int $subscriptionId
     * @param int $customerId
     * @return array
     */
    public function getNextRecurringOrder($subscriptionId, $customerId)
    {
        $nextOrder = $this->getNextRecurringOrderCollection($subscriptionId, $customerId);
        if (!empty($nextOrder)) {
            $curdate = date("Y-m-d");
            $skipDays = self::RECURRING_ORDER_SKIP_DAYS_UPTO;
            $days = $this->getRecurringOrderBufferTime() + $skipDays;
            $endDate = strtotime(date("Y-m-d", strtotime($curdate)) . " +".$days." day");
            $nextOrderDate = strtotime($nextOrder->getRecurringDate());
            $recurringEndDate = strtotime($nextOrder->getRecurringEndDate());
            if (($nextOrderDate <= $endDate) && ($nextOrderDate <= $recurringEndDate)) {
                return $nextOrder;
            }
        }
        return false;
    }

    /**
     * Get next recurring order collection
     *
     * @param int $subscriptionId
     * @param int $customerId
     * @return array
     */
    public function getNextRecurringOrderCollection($subscriptionId, $customerId)
    {
        $curdate = date("Y-m-d");
        $collection = $this->transactionCollectionFactory->getCollection()
                ->addFieldToFilter('status', ['eq' => 'active'])
                ->addFieldToFilter('subscription_id', ['eq' => $subscriptionId])
                ->addFieldToFilter('customer_id', ['eq' => $customerId])
                ->addFieldToFilter('recurring_date', ['gteq' => $curdate]);
        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }
        return false;
    }
}
