<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Token;

use Sapient\Worldpay\Model\SavedToken;
use Sapient\Worldpay\Model\Token\StateInterface as TokenStateInterface;
use Magento\Vault\Model\CreditCardTokenFactory;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\Encryption\EncryptorInterface;
/**
 * Worldpay token
 */
class WorldpayToken
{
    protected $paymentTokenManagement;
    protected $encryptor;

    /**
     * Constructor
     *
     * @param SavedToken $savedtoken
     * @param Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
    public function __construct(
        SavedToken $savedtoken,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        CreditCardTokenFactory $paymentTokenFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        EncryptorInterface $encryptor
    ) {
        $this->savedtoken = $savedtoken;
        $this->wplogger = $wplogger;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->encryptor = $encryptor;
    }

    /**
     * Update token of the given customer
     *
     * @param Sapient\WorldPay\Model\Token $token
     * @param \Magento\Customer\Model\Customer $customer
     * @throws Sapient\WorldPay\Model\Token\AccessDeniedException
     */
    public function updateTokenByCustomer(SavedToken $token, \Magento\Customer\Model\Customer $customer)
    {
        $this->_assertAccessForToken($token, $customer);
        $token->save();
    }

    /**
     * @param Sapient\WorldPay\Model\Token $token
     * @param \Magento\Customer\Model\Customer $customer
     * @throws Sapient\WorldPay\Model\Token\AccessDeniedException
     */
    private function _assertAccessForToken(SavedToken $token, \Magento\Customer\Model\Customer $customer)
    {
        if (!$this->hasCustomerAccessForToken($token, $customer)) {
            throw new AccessDeniedException(
                sprintf('Access denied: token "%s" is not owned by customer "%d"', $token->getTokenCode(), $customer->getId())
            );
        }
    }

    /**
     * Tells if customer has any tokenised card
     *
     * @param Sapient\WorldPay\Model\Token $token
     * @param \Magento\Customer\Model\Customer $customer
     * @return bool
     */
    public function hasCustomerAccessForToken(SavedToken $token, \Magento\Customer\Model\Customer $customer)
    {
        return $token->getCustomerId() == $customer->getId();
    }

    /**
     * Delete token from a given customer
     * Throws exception if customer has no access to the given token (if token is not owned by the customer)
     *
     * @param Sapient\WorldPay\Model\Token $token
     * @param \Magento\Customer\Model\Customer $customer
     * @throws Sapient\WorldPay\Model\Token\AccessDeniedException
     */
    public function deleteTokenByCustomer(SavedToken $token, \Magento\Customer\Model\Customer $customer)
    {
        $this->_assertAccessForToken($token, $customer);
        $token->delete();
    }

    /**
     * Update token of the given customer
     *
     * @param Sapient\WorldPay\Model\Token $token
     * @param \Magento\Customer\Model\Customer $customer
     * @throws Sapient\WorldPay\Model\Token\AccessDeniedException
     */
    public function updateOrInsertToken(TokenStateInterface $tokenState, $paymentObject)
    {
        if (!$tokenState->getTokenCode()) {
            return;
        }

        $tokenModel = $this->savedtoken;
        $tokenModel->loadByTokenCode($tokenState->getTokenCode());
        $tokenModel->getResource()->beginTransaction();

        try {
            $tokenModel->setTokenCode($tokenState->getTokenCode());
            $tokenModel->setTokenReason($tokenState->getTokenReason());
            $tokenModel->setTokenExpiryDate($tokenState->getTokenExpiryDate()->format('Y-m-d'));
            $tokenModel->setCardNumber($tokenState->getObfuscatedCardNumber());
            $tokenModel->setCardholderName($tokenState->getCardholderName());
            $tokenModel->setMethod(str_replace("_CREDIT", "", $tokenState->getPaymentMethod()));
            $tokenModel->setCardBrand($tokenState->getCardBrand());
            $tokenModel->setCardSubBrand($tokenState->getCardSubBrand());
            $tokenModel->setCardIssuerCountryCode($tokenState->getCardIssuerCountryCode());
            $tokenModel->setCardExpiryMonth($tokenState->getCardExpiryMonth());
            $tokenModel->setCardExpiryYear($tokenState->getCardExpiryYear());
            $tokenModel->setMerchantCode($tokenState->getMerchantCode());
            $tokenModel->setAuthenticatedShopperId($tokenState->getAuthenticatedShopperId());
            $tokenModel->setCustomerId($tokenState->getAuthenticatedShopperId());
            $tokenModel->save();
            $tokenModel->getResource()->commit();
            if ('worldpay_cc' == $paymentObject->getMethod()) {
                // vault and instant purchase configuration goes here
                $this->_updateToVault($tokenState, $paymentObject);
            }
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            $tokenModel->getResource()->rollBack();
            throw $e;
        }
    }

    private function _updateToVault(TokenStateInterface $tokenState, $paymentObject)
    {
        $paymentToken = $this->getVaultPaymentToken($tokenState);
        if (null !== $paymentToken) {
            $extensionAttributes = $this->getExtensionAttributes($paymentObject);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
        // saves payment token manually.
        // set all payment token attributes.
        if ($paymentToken->getEntityId() !== null) {
            $this->paymentTokenManagement->addLinkToOrderPayment(
                $paymentToken->getEntityId(),
                $paymentObject->getEntityId()
            );
            return $this;
        }
        $paymentToken->setCustomerId($tokenState->getAuthenticatedShopperId());
        $paymentToken->setIsActive(true);
        $paymentToken->setPaymentMethodCode($paymentObject->getMethod());
        $additionalInformation = $paymentObject->getAdditionalInformation();
        $paymentToken->setIsVisible(true);
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
        $this->paymentTokenManagement->saveTokenWithPaymentLink($paymentToken, $paymentObject);
        $extensionAttributes->setVaultPaymentToken($paymentToken);
    }

    protected function generatePublicHash(PaymentTokenInterface $paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }
        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }

    private function getExtensionAttributes($payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }

    protected function getVaultPaymentToken(TokenStateInterface $tokenElement)
    {
        // Check token existing in gateway response
        $token = $tokenElement->getTokenCode();
        if (empty($token)) {
            return null;
        }

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($token);
        $paymentToken->setExpiresAt($this->getExpirationDate($tokenElement));

        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => str_replace("_CREDIT", "", $tokenElement->getPaymentMethod()),
            'maskedCC' => $this->getLastFourNumbers($tokenElement->getObfuscatedCardNumber()),
            'expirationDate'=> $this->getExpirationMonthAndYear($tokenElement)
        ]));
        return $paymentToken;
    }

    public function getLastFourNumbers($number)
    {
        return substr ($number, -4);
    }

    public function getExpirationMonthAndYear(TokenStateInterface $tokenElement)
    {
        return $tokenElement->getCardExpiryMonth().'/'.$tokenElement->getCardExpiryYear();
    }

    private function getExpirationDate(TokenStateInterface $tokenElement)
    {
        return $tokenElement->getTokenExpiryDate()->format('Y-m-d H:i:s');
    }

    private function convertDetailsToJSON($details)
    {
        $json = \Zend_Json::encode($details);
        return $json ? $json : '{}';
    }

}
