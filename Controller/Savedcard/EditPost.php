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
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenManagement;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Exception;
use Sapient\Worldpay\Helper\MyAccountException;

/**
 * Controller for Updating Saved card
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
     * @var Sapient\Worldpay\Helper\Data;
     */
    protected $helper;

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
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param PaymentTokenRepositoryInterface $tokenRepository
     * @param PaymentTokenManagement $paymentTokenManagement
     * @param MyAccountException $helper
     */
    public function __construct(
        Context $context,
        SavedTokenFactory $savecard,
        Session $customerSession,
        Validator $formKeyValidator,
        StoreManagerInterface $storeManager,
        \Sapient\Worldpay\Model\Token\Service $tokenService,
        \Sapient\Worldpay\Model\Token\WorldpayToken $worldpayToken,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        PaymentTokenRepositoryInterface $tokenRepository,
        PaymentTokenManagement $paymentTokenManagement,
        MyAccountException $helper
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->formKeyValidator = $formKeyValidator;
        $this->savecard = $savecard;
        $this->customerSession = $customerSession;
        $this->_tokenService = $tokenService;
        $this->_worldpayToken = $worldpayToken;
        $this->wplogger = $wplogger;
        $this->tokenRepository = $tokenRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->helper = $helper;
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
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        }
        $validFormKey = $this->formKeyValidator->validate($this->getRequest());
        if ($validFormKey && $this->getRequest()->isPost()) {
            try {
                $tokenUpdateResponse = $this->_tokenService->getTokenUpdate(
                    $this->_getTokenModel(),
                    $this->customerSession->getCustomer(),
                    $this->getStoreId()
                );
                $tokenInquiryResponse = $this->_tokenService->getTokenInquiry(
                    $this->_getTokenModel(),
                    $this->customerSession->getCustomer(),
                    $this->getStoreId()
                );
            } catch (Exception $e) {
                $this->wplogger->error($e->getMessage());
                $this->messageManager->addException($e, __('Error: ').$e->getMessage());
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('*/savedcard/edit', ['id' => $this->_getTokenModel()->getId()]);
                return $resultRedirect;
            }
            if ($tokenUpdateResponse->isSuccess()) {
                $this->_applyTokenUpdate();
                $this->_applyVaultTokenUpdate();
            } else {
                $this->messageManager->addError(__($this->helper->getConfigValue('MCAM7')));
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('*/savedcard/edit', ['id' => $this->_getTokenModel()->getId()]);
                return $resultRedirect;
            }
            if ($tokenInquiryResponse->getTokenCode()) {
                $this->_applyTokenInquiry($tokenInquiryResponse);
                $this->_applyVaultTokenUpdate();
            } else {
                $this->messageManager->addError(__($this->helper->getConfigValue('MCAM7')));
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('*/savedcard/edit', ['id' => $this->_getTokenModel()->getId()]);
                return $resultRedirect;
            }
            $this->messageManager->addSuccess(__($this->helper->getConfigValue('MCAM9')));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/savedcard');
            return $resultRedirect;
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
     * Get token model
     *
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

    /**
     * Apply vault token update
     */
    protected function _applyVaultTokenUpdate()
    {
        $existingVaultPaymentToken = $this->paymentTokenManagement->getByGatewayToken(
            $this->_getTokenModel()->getTokenCode(),
            'worldpay_cc',
            $this->customerSession->getCustomer()->getId()
        );
        $this->_saveVaultToken($existingVaultPaymentToken);
    }

    /**
     * Save vault token
     *
     * @param PaymentTokenInterface $vaultToken
     * @return bool
     * @throws Exception
     */
    protected function _saveVaultToken(PaymentTokenInterface $vaultToken)
    {
        $vaultToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => $this->_getTokenModel()->getMethod(),
            'maskedCC' => $this->getLastFourNumbers($this->_getTokenModel()->getCardNumber()),
            'expirationDate'=> $this->getExpirationMonthAndYear($this->_getTokenModel())
        ]));
        try {
            $this->tokenRepository->save($vaultToken);
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            $this->messageManager->addException($e, __('Error: ').$e->getMessage());
        }
        return true;
    }

    /**
     * Get expiration month and year
     *
     * @param array $token
     * @return string
     */
    public function getExpirationMonthAndYear($token)
    {
        return $token->getCardExpiryMonth().'/'.$token->getCardExpiryYear();
    }

    /**
     * Finding the last four digits by given number
     *
     * @param string $number
     * @return string
     */
    public function getLastFourNumbers($number)
    {
        return substr($number, -4);
    }

    /**
     * Convert payment token details to JSON
     *
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON($details)
    {
        $json = \Zend_Json::encode($details);
        return $json ? $json : '{}';
    }
    
    /**
     * Update Saved Card Detail
     *
     * @param array $tokenInquiryResponse
     */
    protected function _applyTokenInquiry($tokenInquiryResponse)
    {
        $this->_worldpayToken->updateTokenByCustomer(
            $this->_getTokenModelInquiry($tokenInquiryResponse),
            $this->customerSession->getCustomer()
        );
    }
    
    /**
     * Get token model inquiry
     *
     * @param array $tokenInquiryResponse
     * @return Sapient/WorldPay/Model/Token
     */
    protected function _getTokenModelInquiry($tokenInquiryResponse)
    {
        if (! $tokenId = $this->getRequest()->getParam('token_id')) {
            $tokenData = $this->getRequest()->getParam('token');
            $tokenId = $tokenData['id'];
        }
        $token = $this->savecard->create()->loadByTokenCode($tokenId);
        $tokenUpdateData = $this->getRequest()->getParam('token');
        if (! empty($tokenUpdateData) && ! empty($tokenInquiryResponse->isSuccess())) {
            $token->setBinNumber(trim($tokenInquiryResponse->isSuccess()));
        }
        return $token;
    }
}
