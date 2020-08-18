<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Controller\Recurring;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Sapient\Worldpay\Model\Config\Source\SubscriptionStatus;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;
use Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory;
use Sapient\Worldpay\Helper\MyAccountException;

class Cancel extends \Magento\Framework\App\Action\Action
{
    /**
     * @var SubscriptionFactory
     */
    private $subscriptionFactory;
    
    /**
     * @var SubscriptionFactory
     */
    private $transactionFactory;

    /**
     * @var Session
     */
    private $customerSession;
    
    private $helper;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param SubscriptionFactory $subscriptionFactory
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        SubscriptionFactory $subscriptionFactory,
        TransactionsFactory $transactionFactory,
        MyAccountException $helper
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->transactionFactory = $transactionFactory;
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
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();

        $subscriptionId = $this->getRequest()->getParam('subscription_id');
        $subscription = $this->subscriptionFactory
            ->create()
            ->load($subscriptionId);
        $recurring = $this->transactionFactory
            ->create()
            ->loadBySubscriptionId($subscriptionId);

        $redirectResult = $this->resultRedirectFactory->create()->setPath('*/*');

        if (!$subscription->getId() || $subscription->getId() != $subscriptionId) {
            $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('MCAM15')));

            return $redirectResult;
        }

        if ($subscription->getStatus() == SubscriptionStatus::CANCELLED) {
            $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('MCAM16')));

            return $redirectResult;
        }

        if ($subscription->getCustomerId() != $this->customerSession->getCustomerId()) {
            $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('MCAM17')));

            return $redirectResult;
        }

        try {
            $subscription->setStatus(SubscriptionStatus::CANCELLED)
                ->save();
            $recurring->setStatus(SubscriptionStatus::CANCELLED)
                ->save();

            $this->messageManager->addSuccessMessage(__($this->helper->getConfigValue('MCAM14')));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('MCAM18')));
        }

        return $redirectResult;
    }
}
