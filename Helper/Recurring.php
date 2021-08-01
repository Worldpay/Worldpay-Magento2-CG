<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Helper;

use Sapient\Worldpay\Model\Config\Source\Interval;
use Sapient\Worldpay\Model\Config\Source\TrialInterval;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Recurring extends \Magento\Framework\App\Helper\AbstractHelper
{
    const PENDING_RECURRING_PAYMENT_ORDER_STATUS = 'pending_recurring_payment';

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
    
    protected $_customerSession;
    
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Recurring constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
     * @param \Sapient\Worldpay\Model\ResourceModel\Recurring\Plan\CollectionFactory $plansCollectionFactory
     * @param Interval $intervalSource
     * @param TrialInterval $trialIntervalSource
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param ScopeConfigInterface $scopeConfig
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
        SerializerInterface $serializer
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
        $this->_product = $product;
        $this->_formkey = $formkey;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->payment = $payment;
        $this->_cart = $cart;
        $this->_customerSession = $customerSession;
        $this->_tokenModelFactory = $tokenModelFactory;
        $this->serializer = $serializer;
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
     * @param $intervalCode
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
     * @param $trialIntervalCode
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
     * @param string $renderedPrice
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
                    'label'   => 'Subscription Details',
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
     * @param \Sapient\Worldpay\Model\Recurring\Plan
     * @return string
     */
    public function getPlanTrialLabel(\Sapient\Worldpay\Model\Recurring\Plan $plan)
    {
        return $this->getTrialLabel($plan->getNumberOfTrialIntervals(), $plan->getTrialInterval());
    }

    /**
     * Build trial label (number of trial intervals and interval label combined)
     *
     * @param $numberOfTrialIntervals
     * @param $trialInterval
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
     * @param $createdAt
     * @param $startDate
     * @param $numTrialIntervals
     * @param $trialInterval
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
                        'label' => 'Subscription Start Date',
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
                        'label' => 'Subscription End Date',
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
        $savedShippingMethod = explode('_', $orderData['shipping_method']);
        
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
        $paymentData = json_encode($paymentData);
        $orderId = $this->orderPayment($tokenKey, $paymentData);
        return $orderId;
    }
    
    public function createEmptyQuote($tokenKey)
    {
        $token = 'Bearer '.$tokenKey;
        // @codingStandardsIgnoreStart
        $curl = curl_init();
        $apiUrl = $this->_storeManager->getStore()->getUrl('rest/default/V1/carts/mine');
        curl_setopt_array($curl, [
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
            //"Cookie: private_content_version=6803ffbab48db2029bb648e4a02b9692; PHPSESSID=d4nbqs1pbd0uc2dn04061mvjp4"
          ],

        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        // @codingStandardsIgnoreEnd
        return $response;
    }
    
    public function addItemsToQuote($tokenKey, $itemData, $quoteId)
    {
        $token = 'Bearer '.$tokenKey;
        // @codingStandardsIgnoreStart
        $curl = curl_init();
        $apiUrl = '';
        $apiUrl = $this->_storeManager->getStore()->getUrl('rest/default/V1/carts/mine/');
        curl_setopt_array($curl, [
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
            //"Cookie: private_content_version=6803ffbab48db2029bb648e4a02b9692; PHPSESSID=d4nbqs1pbd0uc2dn04061mvjp4"
          ],

        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        // @codingStandardsIgnoreEnd
        return json_decode($response, true);
    }
    
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
    
    public function getShippingMethods($tokenKey, $addressData)
    {
        $token = 'Bearer '.$tokenKey;
        // @codingStandardsIgnoreStart
        $curl = curl_init();
        $apiUrl = $this->_storeManager->getStore()->getUrl('rest/default/V1/carts/mine/');
        curl_setopt_array($curl, [
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
            //"Cookie: private_content_version=6803ffbab48db2029bb648e4a02b9692; PHPSESSID=d4nbqs1pbd0uc2dn04061mvjp4"
          ],

        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        // @codingStandardsIgnoreEnd
        return json_decode($response, true);
    }
    
    public function setShippingInformation($tokenKey, $shippingInformation)
    {
        $token = 'Bearer '.$tokenKey;
        // @codingStandardsIgnoreStart
        $curl = curl_init();
        $apiUrl = $this->_storeManager->getStore()->getUrl('rest/default/V1/carts/mine/');
        curl_setopt_array($curl, [
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
            //"Cookie: private_content_version=6803ffbab48db2029bb648e4a02b9692; PHPSESSID=d4nbqs1pbd0uc2dn04061mvjp4"
          ],

        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        // @codingStandardsIgnoreEnd
        return json_decode($response, true);
    }
    
    public function orderPayment($tokenKey, $paymentData)
    {
        $token = 'Bearer '.$tokenKey;
        // @codingStandardsIgnoreStart
        $curl = curl_init();
        $apiUrl = $this->_storeManager->getStore()->getUrl('rest/default/V1/carts/mine/');
        curl_setopt_array($curl, [
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
            //"Cookie: private_content_version=6803ffbab48db2029bb648e4a02b9692; PHPSESSID=d4nbqs1pbd0uc2dn04061mvjp4"
          ],

        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        // @codingStandardsIgnoreEnd
        return json_decode($response, true);
    }
    
    public function getBuyOneTimelabel()
    {
        $label = $this->scopeConfig->getValue(
            'worldpay/subscriptions/buy_onetime_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$label) {
            $label = 'Buy one time or select a Payment Plan';
        }
        
        return $label;
    }
    
    public function getSubscribeCheckboxLabel()
    {
        $label = $this->scopeConfig->getValue(
            'worldpay/subscriptions/subscribe_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$label) {
            $label = 'Subscribe this product and save';
        }
        
        return $label;
    }
    
    public function getStartDateLabel()
    {
        $label = $this->scopeConfig->getValue(
            'worldpay/subscriptions/start_date_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$label) {
            $label = 'Subscription Start Date';
        }
        
        return $label;
    }
    
    public function getEndDateLabel()
    {
        $label = $this->scopeConfig->getValue(
            'worldpay/subscriptions/end_date_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$label) {
            $label = 'Subscription End Date';
        }
        
        return $label;
    }
    public function getAdminLabels($labelCode, $store = null, $scope = null)
    {
        if ($scope==='website') {
            $adminLabels = $this->serializer->unserialize($this->scopeConfig->getValue(
                'worldpay_custom_labels/admin_labels/admin_label',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $store
            ));
        } else {
            $adminLabels = $this->serializer->unserialize($this->scopeConfig->getValue(
                'worldpay_custom_labels/admin_labels/admin_label',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            ));
        }
        if (is_array($adminLabels) || is_object($adminLabels)) {
            foreach ($adminLabels as $key => $valuepair) {
                if ($key == $labelCode) {
                    return $valuepair['wpay_custom_label']?$valuepair['wpay_custom_label']:
                        $valuepair['wpay_label_desc'];
                }
            }
        }
    }

    public function getAccountLabelbyCode($labelCode)
    {
        $aLabels = $this->serializer->unserialize($this->scopeConfig->getValue(
            'worldpay_custom_labels/my_account_labels/my_account_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
        if (is_array($aLabels) || is_object($aLabels)) {
            foreach ($aLabels as $key => $valuepair) {
                if ($key == $labelCode) {
                    return $valuepair['wpay_custom_label']?$valuepair['wpay_custom_label']:
                    $valuepair['wpay_label_desc'];
                }
            }
        }
    }
    
    public function getCheckoutLabelbyCode($labelCode)
    {
        $aLabels = $this->serializer->unserialize($this->scopeConfig->getValue(
            'worldpay_custom_labels/checkout_labels/checkout_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
        if (is_array($aLabels) || is_object($aLabels)) {
            foreach ($aLabels as $key => $valuepair) {
                if ($key == $labelCode) {
                    return $valuepair['wpay_custom_label']?$valuepair['wpay_custom_label']:
                    $valuepair['wpay_label_desc'];
                }
            }
        }
    }
    
    public function getMyAccountExceptions($exceptioncode)
    {
        $accdata = $this->serializer->unserialize($this->scopeConfig->getValue('worldpay_exceptions/'
                . 'my_account_alert_codes/response_codes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        if (is_array($accdata) || is_object($accdata)) {
            foreach ($accdata as $key => $valuepair) {
                if ($key == $exceptioncode) {
                    return $valuepair['exception_module_messages']?$valuepair['exception_module_messages']:
                        $valuepair['exception_messages'];
                }
            }
        }
    }
    public function getCheckoutExceptions($exceptioncode)
    {
        $ccdata = $this->serializer->unserialize($this->scopeConfig->getValue('worldpay_exceptions/'
                . 'ccexceptions/cc_exception', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        if (is_array($ccdata) || is_object(accdata)) {
            foreach ($ccdata as $key => $valuepair) {
                if ($key == $exceptioncode) {
                    return $valuepair['exception_module_messages']?$valuepair['exception_module_messages']:
                        $valuepair['exception_messages'];
                }
            }
        }
    }
}
