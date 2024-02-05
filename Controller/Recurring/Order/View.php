<?php
/**
 * Copyright © 2023 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Sapient\Worldpay\Controller\Recurring\Order;

use Sapient\Worldpay\Model\ResourceModel\SubscriptionOrderFactory;

class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var $SubscriptionOrderFactory
     */
    private $SubscriptionOrderFactory;
        
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Constructor
     *
     * @param Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param SubscriptionOrderFactory $SubscriptionOrderFactory
     * @param \Magento\Customer\Model\Session $customerSession
     */

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        SubscriptionOrderFactory $SubscriptionOrderFactory,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->SubscriptionOrderFactory = $SubscriptionOrderFactory;
        $this->customerSession = $customerSession;
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
