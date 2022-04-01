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
            $this->_redirect('customer/account/login');
            return;
        }
        $validFormKey = $this->formKeyValidator->validate($this->getRequest());
        $wpTokenId = $this->_getTokenModel()->getId();
        if (!empty($this->getRequest()->getParam('wp_token_id'))) {
            $wpTokenId =  $this->getRequest()->getParam('wp_token_id');
        }
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
                $this->_redirect('*/savedcard/edit', ['id' => $wpTokenId]);
                return;
            }
            // added fix for github issue 71
            try {
                if ($tokenUpdateResponse->isSuccess()) {
                    $this->_applyTokenUpdate();
                    $this->_applyVaultTokenUpdate();
                } else {
                    $this->messageManager->addError(__($this->helper->getConfigValue('MCAM7')));
                    $this->_redirect('*/savedcard/edit', ['id' => $wpTokenId]);
                    return;
                }
                if ($tokenInquiryResponse->getTokenCode()) {
                    $this->_applyTokenInquiry($tokenInquiryResponse);
                    $this->_applyVaultTokenUpdate();
                } else {
                    $this->messageManager->addError(__($this->helper->getConfigValue('MCAM7')));
                    $this->_redirect('*/savedcard/edit', ['id' => $wpTokenId]);
                    return;
                }
            } catch (Exception $e) {
                $this->wplogger->error($e->getMessage());
                $this->messageManager->addException($e, __('Error: ').$this->helper->getConfigValue('MCAM7'));
                $this->_redirect('*/savedcard/edit', ['id' => $wpTokenId]);
                return;
            }
            
            $this->messageManager->addSuccess(__($this->helper->getConfigValue('MCAM9')));
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

    protected function _applyVaultTokenUpdate()
    {
        $existingVaultPaymentToken = $this->paymentTokenManagement->getByGatewayToken(
            $this->_getTokenModel()->getTokenCode(),
            'worldpay_cc',
            $this->customerSession->getCustomer()->getId()
        );
        $this->_saveVaultToken($existingVaultPaymentToken);
    }

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

    public function getExpirationMonthAndYear($token)
    {
        return $token->getCardExpiryMonth().'/'.$token->getCardExpiryYear();
    }

    public function getLastFourNumbers($number)
    {
        return substr($number, -4);
    }

    private function convertDetailsToJSON($details)
    {
        $json = \Zend_Json::encode($details);
        return $json ? $json : '{}';
    }
    
    /**
     * Update Saved Card Detail
     */
    protected function _applyTokenInquiry($tokenInquiryResponse)
    {
        $this->_worldpayToken->updateTokenByCustomer(
            $this->_getTokenModelInquiry($tokenInquiryResponse),
            $this->customerSession->getCustomer()
        );
    }
    
    /**
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
