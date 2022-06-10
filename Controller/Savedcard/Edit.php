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
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        SavedTokenFactory $savecard,
        Session $customerSession,
        \Sapient\Worldpay\Helper\Data $worldpayHelper
    ) {
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
        $this->savecard = $savecard;
        $this->customerSession = $customerSession;
        $this->worldpayHelper = $worldpayHelper;
    }

    /**
     * Execute action
     *
     * @return null
     */
    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        }
        $resultPage = $this->_resultPageFactory->create();
        $id = $this->getRequest()->getParam('id');
        $customerId = $this->customerSession->getCustomer()->getId();
        if ($id) {
            $cardDetails = $this->savecard->create()->load($id);
            if ($cardDetails->getCustomerId() != $customerId) {
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('404notfound');
                return $resultRedirect;
            }
            $resultPage->getConfig()->getTitle()->set($this->worldpayHelper->getAccountLabelbyCode('AC7'));
            return $resultPage;
        } else {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('404notfound');
            return $resultRedirect;
        }
    }
}
