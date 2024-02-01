<?php
/**
 * Copyright © 2023 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Sapient\Worldpay\Controller\Skiporder;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Sapient\Worldpay\Model\ResourceModel\SkipSubscriptionOrder\CollectionFactory;
use Sapient\Worldpay\Model\SkipSubscriptionOrderFactory;
use Sapient\Worldpay\Model\SkipSubscriptionOrderRepository;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    private $worldpayhelper;

    /**
     * @var \Magento\Customer\Model\Url
     */
    private $customerUrl;

    /**
     * @var $skipOrderRepository
     */
    private $skipOrderRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param SkipSubscriptionOrderRepository $skipOrderRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Magento\Customer\Model\Url $customerUrl,
        SkipSubscriptionOrderRepository $skipOrderRepository
    ) {
        $this->customerSession = $customerSession;
        $this->worldpayhelper = $worldpayhelper;
        $this->customerUrl = $customerUrl;
        $this->skipOrderRepository = $skipOrderRepository;
        parent::__construct($context);
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
     * Display subscriptions bought by customer
     *
     * @return void
     */
    public function execute()
    {
        if ($this->getRequest()->isAjax()) {
            $params = $this->getRequest()->getParams('subscriptionId');
            $isSkipped = $this->skipOrderRepository->updateSkipHistory($params);
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            if ($isSkipped) {
                $this->messageManager->addSuccess("Order skipped successfully");
                $resultJson->setData(["message" => ("Order skipped successfully"), "suceess" => true]);
            } else {
                $this->messageManager
                    ->addNoticeMessage("Someting went wrong. Please try again later.");
                $resultJson->setData(["message" => ("Order skipped successfully"), "suceess" => false]);
            }
            return $resultJson;
        }
          $resultRedirect = $this->resultRedirectFactory->create();
          $resultRedirect->setPath('404notfound');
          return $resultRedirect;
    }
}
