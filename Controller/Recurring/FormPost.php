<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Controller\Recurring;

use Magento\Customer\Model\Session;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Vault\Model\PaymentTokenFactory;
use Sapient\Worldpay\Model\Recurring\Subscription;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;
use Sapient\Worldpay\Helper\MyAccountException;

class FormPost extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var SubscriptionFactory
     */
    private $subscriptionFactory;

    /**
     * @var CollectionFactory
     */
    private $regionCollectionFactory;

    /**
     * @var PaymentTokenFactory
     */
    private $tokenFactory;
    
    /**
     * @var MyAccountException
     */
    protected $helper;
    
    /**
     * @param Context $context
     * @param Session $customerSession
     * @param Validator $validator
     * @param SubscriptionFactory $subscriptionFactory
     * @param PaymentTokenFactory $tokenFactory
     * @param CollectionFactory $regionCollectionFactory
     * @param MyAccountException $helper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Validator $validator,
        SubscriptionFactory $subscriptionFactory,
        PaymentTokenFactory $tokenFactory,
        CollectionFactory $regionCollectionFactory,
        MyAccountException $helper
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->validator = $validator;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->tokenFactory = $tokenFactory;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->helper = $helper;
    }

    /**
     * Check customer authentication
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->_objectManager->get(\Magento\Customer\Model\Url::class)->getLoginUrl();

        if (!$this->customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }
        return parent::dispatch($request);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $request = $this->getRequest();

        $subscriptionId = $request->getParam('subscription_id');
        if (!$subscriptionId) {
            $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('MCAM4')));

            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        $resultRedirect->setPath('*/*/edit', ['subscription_id' => $this->getRequest()->getParam('subscription_id')]);

        if (!$this->validator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('MCAM4')));

            return $resultRedirect;
        }

        $subscription = $this->subscriptionFactory
            ->create()
            ->load($subscriptionId);

        $customerId = $this->customerSession->getCustomerId();

        if (!$subscription
            || $subscription->getId() != $subscriptionId
            || $subscription->getCustomerId() != $customerId
        ) {
            $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('MCAM4')));

            return $resultRedirect;
        }

        $data = $this->getRequest()->getParams();

        try {
            if ($data['plan_id'] != $subscription->getPlanId()) {
                $subscription->changePlan((int)$data['plan_id']);
            }

            if ($subscription->getBillingAddress()) {
                $this->updateBillingAddress($subscription, $data);
                if ($subscription->getBillingAddress()->hasDataChanges()) {
                    $subscription->setHasDataChanges(true);
                }
            }
            //$this->updatePaymentInfo($subscription, $data);

            /* Only update if changes exist */
            if ($subscription->hasDataChanges()) {
                $subscription->save();
            }

            $this->messageManager->addSuccessMessage($this->helper->getConfigValue('MCAM10'));
            $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('MCAM11')));
        }

        return $resultRedirect;
    }

    /**
     * Update Subscription Billing Address
     *
     * @param Subscription $subscription
     * @param array $data
     * @return $this
     */
    public function updateBillingAddress(Subscription $subscription, array $data)
    {
        /** @var \Sapient\Worldpay\Model\Recurring\Subscription\Address $billingAddress */
        $billingAddress = $subscription->getBillingAddress();

        if ($data['firstname'] != $billingAddress->getFirstname() ||
            $data['lastname'] != $billingAddress->getLastname()
        ) {
            $billingAddress->setFirstname($data['firstname']);
            $billingAddress->setLastname($data['lastname']);
            $subscription->setBillingName($data['firstname'] . ' ' . $data['lastname']);
        }

        if ($data['street'] != $billingAddress->getStreet()) {
            $billingAddress->setStreet($data['street']);
        }

        if ($data['city'] != $billingAddress->getCity()) {
            $billingAddress->setCity($data['city']);
        }

        /* 'region_id' may not be set for some countries */
        if (isset($data['region_id']) && $data['region_id'] != $billingAddress->getRegionId()) {
            $region = $this->regionCollectionFactory
                ->create()
                ->getItemById((int)$data['region_id']);

            $billingAddress->setRegionId($region->getRegionId());
            $billingAddress->setRegion($region->getName());
        }

        if ($data['postcode'] != $billingAddress->getPostcode()) {
            $billingAddress->setPostcode($data['postcode']);
        }

        if ($data['country_id'] != $billingAddress->getCountryId()) {
            $billingAddress->setCountryId($data['country_id']);
        }

        return $this;
    }

    /**
     * Update subscription payment information
     *
     * @param Subscription $subscription
     * @param array $data
     * @return $this
     */
    public function updatePaymentInfo($subscription, array $data)
    {
        $customerId = $this->customerSession->getCustomerId();

        if ($data['worldpay_subscription_payment'] == -2) {
            $subscription->setPaypageRegistrationId($data['paypage_registration_id']);
        } elseif ($data['worldpay_subscription_payment'] >= 0) {
            /** @var \Magento\Vault\Model\PaymentToken $token */
            $token = $this->tokenFactory->create()->load($data['worldpay_subscription_payment']);

            $details = json_decode($token->getTokenDetails());

            if ($token->getCustomerId() == $customerId) {
                $subscription->setToken($token->getGatewayToken());
                $subscription->setPaymentMethod($token->getPaymentMethodCode());
                $subscription->setPaymentCcExpMonth($details->ccExpMonth);
                $subscription->setPaymentCcExpYear($details->ccExpYear);
                $subscription->setPaymentCcLast4($details->ccLast4);
            }
        }

        return $this;
    }
}
