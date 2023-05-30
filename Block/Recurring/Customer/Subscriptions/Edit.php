<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Block\Recurring\Customer\Subscriptions;

use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Model\CcConfigProvider as IconsProvider;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\PaymentToken;
use Sapient\Worldpay\Helper\Recurring;
use Sapient\Worldpay\Model\Recurring\Plan;
use Sapient\Worldpay\Model\Recurring\Subscription;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;
use Magento\Directory\Helper\Data;

//use Sapient\Worldpay\Model\Ui\CcConfigProvider;

class Edit extends Template
{
    /**
     * @var \Sapient\Worldpay\Model\Recurring\Subscription|null
     */
    private $subscription = null;
    /**
     * @var \Magento\Catalog\Model\Product|null
     */
    private $product = null;
    /**
     * @var SubscriptionFactory
     */
    private $subscriptionFactory;
    /**
     * @var Recurring
     */
    private $recurringHelper;
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var PaymentTokenManagementInterface
     */
    private $tokenManager;
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var IconsProvider
     */
    private $iconsProvider;
    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Template\Context $context
     * @param SubscriptionFactory $subscriptionFactory
     * @param Recurring $recurringHelper
     * @param ProductFactory $productFactory
     * @param IconsProvider $iconsProvider
     * @param PaymentTokenManagementInterface $tokenManager
     * @param Session $customerSession
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SubscriptionFactory $subscriptionFactory,
        Recurring $recurringHelper,
        ProductFactory $productFactory,
        IconsProvider $iconsProvider,
        PaymentTokenManagementInterface $tokenManager,
        Session $customerSession,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->subscriptionFactory = $subscriptionFactory;
        $this->recurringHelper = $recurringHelper;
        $this->productFactory = $productFactory;
        $this->tokenManager = $tokenManager;
        $this->customerSession = $customerSession;
        //$this->ccConfigProvider = $ccConfigProvider;
        $this->helper = $helper;
        $this->iconsProvider = $iconsProvider;
    }
    /**
     * Prepare layout
     *
     * @return $layout
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->pageConfig->getTitle()->set($this->recurringHelper->getAccountLabelbyCode('AC24'));

        return $this;
    }
    /**
     * Account Labels
     *
     * @param string $labelCode
     * @return string
     */

    public function getMyAccountLabels($labelCode)
    {
        return $this->recurringHelper->getAccountLabelbyCode($labelCode);
    }
    /**
     * Subscription
     *
     * @return Subscription|null
     */

    public function getSubscription()
    {
        $subscriptionId = $this->getRequest()->getParam('subscription_id');
        if (!$this->subscription && $subscriptionId) {
            $subscription = $this->subscriptionFactory->create();
            $subscription->load($subscriptionId);

            if ($subscription->getId() && $subscription->getId() == $subscriptionId) {
                $this->subscription = $subscription;
            }
        }

        return $this->subscription;
    }

    /**
     * Save Url
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->_urlBuilder->getUrl(
            'worldpay/recurring/formPost',
            ['_secure' => true, 'subscription_id' => $this->getSubscription()->getId()]
        );
    }

    /**
     * Retrieve product subscription plans
     *
     * @return array|\Sapient\Worldpay\Model\ResourceModel\Recurring\Plan\CollectionFactory
     */
    public function getPlans()
    {
        if ($this->getProduct() && $this->getProduct()->isSalable()) {
            return $this->recurringHelper->getProductSubscriptionPlans($this->getProduct());
        } else {
            return [];
        }
    }

    /**
     * Plan Selected
     *
     * @param Int $plan
     * @return bool
     */
    public function isPlanSelected(Plan $plan)
    {
        return $plan->getId() == $this->getSubscription()->getPlanId();
    }

    /**
     * Generate payment plan option title
     *
     * @param Plan $plan
     * @return string
     */
    public function getPlanTitle(Plan $plan)
    {
        return $this->recurringHelper->buildPlanOptionTitle($plan, $this->getPlanPrice($plan));
    }

    /**
     * Plan amount
     *
     * @param Plan $plan
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    private function getPlanAmount(Plan $plan)
    {
        /** @var \Sapient\Worldpay\Model\Pricing\Price\PlanPrice $planPrice */
        $planPrice = $this->getPriceType();
        return $planPrice->getPlanAmount($plan);
    }

    /**
     * Plan Price
     *
     * @param Plan $plan
     * @return string
     */
    public function getPlanPrice(Plan $plan)
    {
        /** @var \Magento\Framework\Pricing\Render $block */
        $block = $this->getLayout()->getBlock('product.price.render.default');
        return $block->renderAmount(
            $this->getPlanAmount($plan),
            $this->getPriceType(),
            $this->getProduct()
        );
    }

    /**
     * Get LinkPrice Type
     *
     * @return \Magento\Framework\Pricing\Price\PriceInterface
     */
    private function getPriceType()
    {
        return $this->getProduct()->getPriceInfo()->getPrice('worldpay_subscription_price');
    }

    /**
     * Product
     *
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProduct()
    {
        if (!$this->product && $this->getSubscription()->getProductId()) {
            $product = $this->productFactory
                ->create()
                ->load($this->getSubscription()->getProductId());

            if ($product->getId() == $this->getSubscription()->getProductId()) {
                $this->product = $product;
            }
        }

        return $this->product;
    }

    /**
     * CC Form Mage
     *
     * @return string
     */
    public function getCcFormMageInitJson()
    {
        //$config = $this->ccConfigProvider->getEprotectConfig();
        //$config['scriptUrl'] = $this->ccConfigProvider->getScriptUrl();

        $data = [
            'Sapient_Worldpay/js/view/recurring/payment' => [
                'config' => $config,
            ],
        ];

        $json = json_encode($data);

        return $json;
    }

    /**
     * Return Saved Payments
     *
     * @return array
     */
    public function getSavedPayments()
    {
        $savedPayments = [];
        $validPaymentMethods = [];
        $allPaymentMethods = $this->_scopeConfig->getValue('payment');

        foreach ($allPaymentMethods as $code => $method) {
            if (isset($method['can_use_for_worldpay_subscription']) && $method['can_use_for_worldpay_subscription']) {
                $validPaymentMethods[] = $code;
            }
        }

        $tokenList = $this->tokenManager->getListByCustomerId($this->customerSession->getCustomerId());

        /** @var PaymentToken $tokenData */
        foreach ($tokenList as $tokenData) {
            /** @var PaymentToken $token */
            $token = $this->tokenManager->getByPublicHash($tokenData->getPublicHash(), $tokenData->getCustomerId());

            if (in_array($token->getPaymentMethodCode(), $validPaymentMethods)
                && $token->getIsActive()
                && $token->getIsVisible()
            ) {
                $savedPayments[] = $token;
            }
        }

        return $savedPayments;
    }

    /**
     * Get Payments Token Details
     *
     * @param PaymentToken $token
     * @return mixed
     */
    public function getDetails(PaymentToken $token)
    {
        return json_decode($token->getTokenDetails(), true);
    }

    /**
     * Get Payment Icon
     *
     * @param PaymentToken $token
     * @return array
     */
    public function getPaymentIcon(PaymentToken $token)
    {
        $details = $this->getDetails($token);
        $iconList = $this->iconsProvider->getIcons();

        $icon = (isset($iconList[$details['ccType']])) ? $iconList[$details['ccType']] : [];

        return $icon;
    }
    
    /**
     * Region Json
     *
     * @return string
     */
    public function getRegionJson()
    {
        return $this->helper->getRegionJson();
    }
    
    /**
     * Countries With Optional Zip
     *
     * @param type $asJson
     * @return array|string
     */
    public function getCountriesWithOptionalZip($asJson)
    {
        return $this->helper->getCountriesWithOptionalZip($asJson);
    }
}
