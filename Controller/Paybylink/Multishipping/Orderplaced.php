<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Controller\Paybylink\Multishipping;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Multishipping\Model\Checkout\Type\Multishipping;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\State;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

/**
 * Multishipping checkout success controller.
 */
class Orderplaced extends Action implements HttpGetActionInterface
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var Multishipping
     */
    private $multishipping;

    /**
     * @var $resultPageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param State $state
     * @param Multishipping $multishipping
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        State $state,
        Multishipping $multishipping,
        PageFactory $resultPageFactory
    ) {
        $this->state = $state;
        $this->multishipping = $multishipping;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }
    /**
     * Multishipping checkout success page
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->state->getCompleteStep(State::STEP_OVERVIEW)) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
        $resultPage = $this->resultPageFactory->create();
        $ids = $this->multishipping->getOrderIds();
        $this->_eventManager->dispatch('multishipping_checkout_controller_success_action', ['order_ids' => $ids]);
        
        return $resultPage;
    }
}
