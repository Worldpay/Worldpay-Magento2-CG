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
//use Sapient\Worldpay\Model\Ui\CcConfigProvider;

class Edit extends \Magento\Framework\View\Element\Template
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
     * @param Template\Context $context
     * @param SubscriptionFactory $subscriptionFactory
     * @param Recurring $recurringHelper
     * @param ProductFactory $productFactory
     * @param IconsProvider $iconsProvider
     * @param PaymentTokenManagementInterface $tokenManager
     * @param Session $customerSession
     * @param CcConfigProvider $ccConfigProvider
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
        //CcConfigProvider $ccConfigProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->subscriptionFactory = $subscriptionFactory;
        $this->recurringHelper = $recurringHelper;
        $this->productFactory = $productFactory;
        $this->tokenManager = $tokenManager;
        $this->customerSession = $customerSession;
        //$this->ccConfigProvider = $ccConfigProvider;
        $this->iconsProvider = $iconsProvider;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->pageConfig->getTitle()->set(__('Edit Subscription'));

        return $this;
    }

    /**
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
     * @param Plan $plan
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
     * @param PaymentToken $token
     * @return mixed
     */
    public function getDetails(PaymentToken $token)
    {
        return json_decode($token->getTokenDetails(), true);
    }

    /**
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
}
