<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Hostedpaymentpage;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

/**
 * Redirect to payment hosted page
 */ 
class Pay extends \Magento\Framework\App\Action\Action
{   
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Sapient\Worldpay\Model\Checkout\Hpp\State
     */
    protected $_status;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Sapient\Worldpay\Model\Checkout\Hpp\State
     * @param \Sapient\Worldpay\Logger\WorldpayLogger    
     */
    public function __construct(
        Context $context, 
        PageFactory $pageFactory,
        \Sapient\Worldpay\Model\Checkout\Hpp\State $hppstate,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) { 
        $this->pageFactory = $pageFactory;
        $this->wplogger = $wplogger;
        $this->hppstate = $hppstate;
        return parent::__construct($context);

    }
 
    public function execute()
    {

        if (!$this->_getStatus()->isInitialised()) { 
            return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
        } 
        return $this->pageFactory->create(); 
    }

    protected function _getStatus()
    {
        if (is_null($this->_status)) {
            $this->_status = $this->hppstate;
        }

        return $this->_status;
    }

    
}