<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Token;

use Sapient\Worldpay\Model\SavedToken;
use Sapient\Worldpay\Model\Token\StateInterface as TokenStateInterface;
/**
 * Worldpay token
 */
class WorldpayToken 
{

    /**
     * Constructor
     *
     * @param SavedToken $savedtoken
     * @param Sapient\Worldpay\Logger\WorldpayLogger $wplogger     
     */
    public function __construct(
        SavedToken $savedtoken,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) {
        $this->savedtoken = $savedtoken;
        $this->wplogger = $wplogger;
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
            $this->wplogger->error($e->getMessage());
            $tokenModel->getResource()->rollBack();
            throw $e;
        }
    }

}
