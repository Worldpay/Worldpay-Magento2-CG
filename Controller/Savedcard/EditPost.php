<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Savedcard;

use Magento\Framework\App\Action\Context;
use \Sapient\Worldpay\Model\SavedTokenFactory;
use \Magento\Customer\Model\Session;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Store\Model\StoreManagerInterface;

use Exception;

/**
 *Controller for Updating Saved card
 */
class EditPost extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
    /**
     * @var Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * Constructor
     *
     * @param Context $context     
     * @param SavedTokenFactory $savecard
     * @param Session $customerSession
     * @param Validator $formKeyValidator
     * @param StoreManagerInterface $storeManager
     * @param \Sapient\Worldpay\Model\Token\Service $tokenService
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpayToken
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger     
     */
    public function __construct(
        Context $context,
        SavedTokenFactory $savecard,
        Session $customerSession,
        Validator $formKeyValidator,
        StoreManagerInterface $storeManager,
        \Sapient\Worldpay\Model\Token\Service $tokenService,
        \Sapient\Worldpay\Model\Token\WorldpayToken $worldpayToken,
        //\Magento\Framework\Message\ManagerInterface $messageManager,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->formKeyValidator = $formKeyValidator;
        $this->savecard = $savecard;
        $this->customerSession = $customerSession;
        $this->_tokenService = $tokenService;
        $this->_worldpayToken = $worldpayToken;
       // $this->_messageManager = $messageManager;
        $this->wplogger = $wplogger;
    }

    /**
     * Retrive store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * Receive http post request to update saved card details
     */
    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }
        $validFormKey = $this->formKeyValidator->validate($this->getRequest());
        if ($validFormKey && $this->getRequest()->isPost()) {
            try {
                $tokenUpdateResponse = $this->_tokenService->getTokenUpdate(
                    $this->_getTokenModel(),
                    $this->customerSession->getCustomer(),
                    $this->getStoreId());
            } catch (Exception $e) {
                $this->wplogger->error($e->getMessage());
                $this->messageManager->addException($e, __('Error: ').$e->getMessage());
                $this->_redirect('*/savedcard/edit', array('id' => $this->_getTokenModel()->getId()));
                return;
            }
            if ($tokenUpdateResponse->isSuccess()) {
                $this->_applyTokenUpdate();
            } else {
                $this->messageManager->addError(__('Error: the card has not been updated.'));
                $this->_redirect('*/savedcard/edit', array('id' => $this->_getTokenModel()->getId()));
                return;
            }
            $this->messageManager->addSuccess(__('The card has been updated.'));
            $this->_redirect('*/savedcard');
            return;
        }
    }

    /** 
     * Update Saved Card Detail 
     */
    protected function _applyTokenUpdate()
    {
        $this->_worldpayToken->updateTokenByCustomer(
            $this->_getTokenModel(),
            $this->customerSession->getCustomer()
        );
    }
    
    /**
     * @return Sapient/WorldPay/Model/Token
     */
    protected function _getTokenModel()
    {
        if (! $tokenId = $this->getRequest()->getParam('token_id')) {
            $tokenData = $this->getRequest()->getParam('token');
            $tokenId = $tokenData['id'];
        }
        $token = $this->savecard->create()->loadByTokenCode($tokenId);
        $tokenUpdateData = $this->getRequest()->getParam('token');
        if (! empty($tokenUpdateData)) {
            $token->setCardholderName(trim($tokenUpdateData['cardholder_name']));
            $token->setCardExpiryMonth(sprintf('%02d', $tokenUpdateData['card_expiry_month']));
            $token->setCardExpiryYear(sprintf('%d', $tokenUpdateData['card_expiry_year']));
        }
        return $token;
    }

}
