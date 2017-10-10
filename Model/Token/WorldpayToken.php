<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Token;

use Sapient\Worldpay\Model\SavedToken;
use Sapient\Worldpay\Model\Token\StateInterface as TokenStateInterface;

class WorldpayToken 
{

    public function __construct(SavedToken $savedtoken)
    {
        $this->savedtoken = $savedtoken;
    }

    public function updateTokenByCustomer(SavedToken $token, \Magento\Customer\Model\Customer $customer)
    {
        $this->_assertAccessForToken($token, $customer);
        $token->save();
    }

    private function _assertAccessForToken(SavedToken $token, \Magento\Customer\Model\Customer $customer)
    {
        if (!$this->hasCustomerAccessForToken($token, $customer)) {
            throw new AccessDeniedException(
                sprintf('Access denied: token "%s" is not owned by customer "%d"', $token->getTokenCode(), $customer->getId())
            );
        }
    }

    public function hasCustomerAccessForToken(SavedToken $token, \Magento\Customer\Model\Customer $customer)
    {
        return $token->getCustomerId() == $customer->getId();
    }

    public function deleteTokenByCustomer(SavedToken $token, \Magento\Customer\Model\Customer $customer)
    {
        $this->_assertAccessForToken($token, $customer);
        $token->delete();
    }

    public function updateOrInsertToken(TokenStateInterface $tokenState)
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
        } catch (Exception $e) {
            $tokenModel->getResource()->rollBack();
            throw $e;
        }
    }

}
