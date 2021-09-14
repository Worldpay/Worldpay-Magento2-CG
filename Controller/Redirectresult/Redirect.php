<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
 
/**
 * Redirect to the page return from worldpay
 */

class Redirect extends \Magento\Framework\App\Action\Action
{

   /**
    * @var Magento\Framework\View\Result\PageFactory
    */
    protected $pageFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Magento\Checkout\Model\Session $checkoutsession
     * @param \Magento\Sales\Model\Order $mageOrder
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Magento\Sales\Model\Order $mageOrder
    ) {
        $this->pageFactory = $pageFactory;
        $this->checkoutsession = $checkoutsession;
        $this->mageOrder = $mageOrder;
        return parent::__construct($context);
    }
    
    public function execute()
    {
        $redirecturl = $this->checkoutsession->getWpRedirecturl();
        $this->checkoutsession->unsWpRedirecturl();
        return $this->resultRedirectFactory->create()->setUrl($redirecturl);
    }
}
