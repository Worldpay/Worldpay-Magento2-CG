<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Savedcard;

use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action {

	protected $_resultPageFactory;
	protected $customerSession;	 
	public function __construct(
		Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Customer\Model\Session $customerSession					
	) {
        
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;        
    }

	public function execute() {	 		
		if (!$this->customerSession->isLoggedIn()) {
			$this->_redirect('customer/account/login');
		return;
		}
	    $resultPage = $this->_resultPageFactory->create();
	    $resultPage->getConfig()->getTitle()->set(__('My Saved Card'));
        return $resultPage;
	 }

}