<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Controller\Recurring\Order;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Sapient\Worldpay\Model\SubscriptionOrder;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;
use Sapient\Worldpay\Helper\MyAccountException;

class Cancel extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var \Magento\Customer\Model\Url
     */

    private $customerUrl;
    /**
     * @var SubscriptionOrder
     */
    protected $subscriptionOrder;

    /**
     * @var SubscriptionFactory
     */
    private $subscriptionFactory;
    
    /**
     * @var MyAccountException
     */

      private $exceptionhelper;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param SubscriptionOrder $subscriptionOrder
     * @param SubscriptionFactory $subscriptionFactory
     * @param MyAccountException $exceptionhelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        \Magento\Customer\Model\Url $customerUrl,
        SubscriptionOrder $subscriptionOrder,
        SubscriptionFactory $subscriptionFactory,
        MyAccountException $exceptionhelper
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->exceptionhelper = $exceptionhelper;
        $this->customerUrl = $customerUrl;
        $this->subscriptionOrder = $subscriptionOrder;
        $this->subscriptionFactory = $subscriptionFactory;
    }

    /**
     * Check customer authentication
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->customerUrl->getLoginUrl();
        if (!$this->customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }
        return parent::dispatch($request);
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $redirectResult = $this->resultRedirectFactory->create();
        $orderId = $this->getRequest()->getParam('id');
        $order = $this->subscriptionOrder->getOrderbyOriginalId($orderId);
        $subscription = $this->subscriptionFactory
                        ->create()
                        ->load($order->getWorldpaySubscriptionId());
        if ($subscription->getCustomerId() != $this->customerSession->getCustomerId()) {
            $redirectResult->setPath('noroute');
            $this->messageManager
                ->addErrorMessage(__('Order not found.'));
            return $redirectResult;
        }
        try {
            $order->cancel()->save();
            $redirectResult->setPath(
                'sales/order/view/order_id/'.$orderId,
                ['_secure' => true]
            );
                $this->messageManager
                    ->addSuccessMessage(__($this->exceptionhelper->getConfigValue('MCAM14')));
        } catch (\Exception $e) {
            $redirectResult->setPath(
                'worldpay/recurring_order/'.$subscription->getSubscriptionId(),
                ['_secure' => true]
            );
            $this->messageManager->addErrorMessage(__($this->exceptionhelper->getConfigValue('MCAM18')));
        }
        return $redirectResult;
    }
}
