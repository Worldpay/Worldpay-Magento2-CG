<?php
/**
 * Copyright © 2023 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Sapient\Worldpay\Controller\Recurring\Order;

use Sapient\Worldpay\Model\ResourceModel\SkipSubscriptionOrderFactory;

class Skiporders extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var $SubscriptionOrderFactory
     */
    private $skipSubscriptionOrderFactory;
        
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    private $worldpayhelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param SkipSubscriptionOrderFactory $skipSubscriptionOrderFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     */

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        SkipSubscriptionOrderFactory $skipSubscriptionOrderFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Sapient\Worldpay\Helper\Data $worldpayhelper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->skipSubscriptionOrderFactory = $skipSubscriptionOrderFactory;
        $this->customerSession = $customerSession;
        $this->worldpayhelper = $worldpayhelper;
    }

    /**
     * Excute and redirect on result page
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        }
        return $this->resultPageFactory->create();
    }
}