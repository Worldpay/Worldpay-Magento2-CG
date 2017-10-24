<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Savedcard;

use Magento\Framework\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;
use \Sapient\Worldpay\Model\SavedTokenFactory;
use \Magento\Customer\Model\Session;
/**
 *  Display Saved card form
 */
class Edit extends \Magento\Framework\App\Action\Action 
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory 
     */
    protected $_resultPageFactory;
    
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Constructor
     *
     * @param Context $context     
     * @param PageFactory $resultPageFactory
     * @param SavedTokenFactory $savecard
     * @param Session $customerSession         
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        SavedTokenFactory $savecard,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
        $this->savecard = $savecard;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Update Saved Card'));
        return $resultPage;
    }
}
