<?php
namespace Sapient\Worldpay\Controller\Hostedpaymentpage;

class Jwt extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;
    
    /**
     * Jwt connstructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return string
     */
    public function execute()
    {
        return $this->_pageFactory->create();
    }
}
