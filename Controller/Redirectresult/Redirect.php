<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
 
class Redirect extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    public function __construct(
        Context $context, PageFactory $pageFactory,\Magento\Checkout\Model\Session $checkoutsession,
        \Magento\Sales\Model\Order $mageOrder
    ) { 
        $this->pageFactory = $pageFactory;
        $this->checkoutsession = $checkoutsession;
        $this->mageOrder = $mageOrder;
        return parent::__construct($context);
    }
 
    public function execute()
    { 
        $mageorderobj = $this->mageOrder->loadByIncrementId($this->checkoutsession->getauthenticatedOrderId());
        $mageorderobj->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, true);
        $mageorderobj->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $mageorderobj->save();
        $redirecturl = $this->checkoutsession->getWpRedirecturl();
        $this->checkoutsession->unsWpRedirecturl();
        return $this->resultRedirectFactory->create()->setUrl($redirecturl);
    }    
}