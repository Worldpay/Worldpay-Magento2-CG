<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Controller\Recurring;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;
use Sapient\Worldpay\Model\Config\Source\SubscriptionStatus;
use Sapient\Worldpay\Helper\GeneralException;

class Edit extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var SubscriptionFactory
     */
    private $subscriptionFactory;

    /**
     * @var helper
     */
    private $helper;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     * @param SubscriptionFactory $subscriptionFactory
     * @param Sapient\Worldpay\Helper\GeneralException $helper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        SubscriptionFactory $subscriptionFactory,
        \Sapient\Worldpay\Helper\GeneralException $helper
    ) {
        $this->customerSession = $customerSession;
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->subscriptionFactory = $subscriptionFactory;
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
     * Display subscriptions bought by customer
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $subscriptionId = $this->getRequest()->getParam('subscription_id');
        $subscription = $this->subscriptionFactory
            ->create()
            ->load($subscriptionId);

        if ($this->customerSession->getCustomerId() != $subscription->getCustomerId()) {
            $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('ACAM9')));

            return $this->resultRedirectFactory->create()->setPath('*/*');
        }

        if (!$subscription->getId() || $subscription->getId() != $subscriptionId) {
            $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('ACAM5')));

            return $this->resultRedirectFactory->create()->setPath('*/*');
        }

        if ($subscription->getStatus() == SubscriptionStatus::CANCELLED) {
            $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('ACAM6')));

            return $this->resultRedirectFactory->create()->setPath('*/*');
        }

        /** @var \Magento\Framework\View\Element\Html\Links $navigationBlock */
        //$resultPage->getConfig()->getTitle()->set(__('Update Saved Card'));
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('worldpay/recurring');
        }

        return $resultPage;
    }
}
