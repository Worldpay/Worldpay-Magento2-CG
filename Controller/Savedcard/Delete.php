<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Savedcard;

use Magento\Framework\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;
use \Sapient\Worldpay\Model\SavedTokenFactory;
use \Magento\Customer\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenManagement;
use Sapient\Worldpay\Helper\MyAccountException;

/**
 * Perform delete card
 */
class Delete extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
        
    protected $helper;

    protected $vaultPaymentToken;

    /**
     * Constructor
     *
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param SavedTokenFactory $savecard
     * @param Session $customerSession
     * @param \Sapient\Worldpay\Model\Token\Service $tokenService
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpayToken
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Context $context,
        PageFactory $resultPageFactory,
        SavedTokenFactory $savecard,
        Session $customerSession,
        \Sapient\Worldpay\Model\Token\Service $tokenService,
        \Sapient\Worldpay\Model\Token\WorldpayToken $worldpayToken,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        PaymentTokenRepositoryInterface $tokenRepository,
        PaymentTokenManagement $paymentTokenManagement,
        MyAccountException $helper,
        \Magento\Vault\Model\PaymentToken $vaultPaymentToken
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_resultPageFactory = $resultPageFactory;
        $this->savecard = $savecard;
        $this->customerSession = $customerSession;
        $this->_tokenService = $tokenService;
        $this->_worldpayToken = $worldpayToken;
        $this->wplogger = $wplogger;
        $this->tokenRepository = $tokenRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->helper = $helper;
        $this->vaultPaymentToken = $vaultPaymentToken;
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
     * perform card deletion
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $model = $this->savecard->create();
                $model->load($id);
                if ($this->customerSession->getId() == $model->getCustomerId()) {
                    if ($model->getTokenCode()) {
                        $tokenDeleteResponse = $this->_tokenService->getTokenDelete(
                            $model,
                            $this->customerSession->getCustomer(),
                            $this->getStoreId()
                        );
                        if ($tokenDeleteResponse->isSuccess()) {
                            // Delete Worldpay Token.
                            $this->_applyTokenDelete($model, $this->customerSession->getCustomer());
                            // Delete Vault Token.
                            $this->_applyVaultTokenDelete($model, $this->customerSession->getCustomer());
                        }
                    } else {
                        $model->delete($id);
                    }
                    $this->messageManager->addSuccess(__($this->helper->getConfigValue('MCAM5')));
                } else {
                    $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('MCAM6')));
                }
            } catch (\Exception $e) {
                $this->wplogger->error($e->getMessage());
                if ($this->_tokenNotExistOnWorldpay($e->getMessage())) {
                    $this->_applyTokenDelete($model, $this->customerSession->getCustomer());
                    $this->_applyVaultTokenDelete($model, $this->customerSession->getCustomer());

                    $this->messageManager->addSuccess(__($this->helper->getConfigValue('MCAM5')));
                } else {
                    $this->messageManager->addException($e, __('Error: ').$e->getMessage());
                }
            }
        }
        $this->_redirect('worldpay/savedcard/index');
    }

    /**
     * @return bool
     */
    protected function _tokenNotExistOnWorldpay($error)
    {
        $message = "Token does not exist";
        if ($error == $message) {
            return true;
        }
        return false;
    }

    /**
     * Delete card of customer
     */
    protected function _applyTokenDelete($tokenModel, $customer)
    {
        $this->_worldpayToken->deleteTokenByCustomer(
            $tokenModel,
            $customer
        );
    }

    /**
     * Delete vault card of customer
     */
    protected function _applyVaultTokenDelete($tokenModel, $customer)
    {
        $paymentToken = $this->paymentTokenManagement->getByGatewayToken(
            $tokenModel->getTokenCode(),
            'worldpay_cc',
            $customer->getId()
        );
        if ($paymentToken === null) {
            return;
        }
        $vaultToken = $this->vaultPaymentToken->load($paymentToken->getEntityId());
        try {
            $vaultToken->delete();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($this->helper->getConfigValue('MCAM6')));
        }
    }
}
