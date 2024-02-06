<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Recurring\Order;

use Sapient\Worldpay\Api\CustomerPaymentTokenInterface;

class CustomerPaymentTokens implements CustomerPaymentTokenInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Sapient\Worldpay\Model\SavedTokenFactory $savedTokenFactory
     */
    protected $savedToken;

    /**
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $customerSession;

    /**
     * @var \Sapient\Worldpay\Helper\Data $worldpayhelper
     */
    protected $worldpayhelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Sapient\Worldpay\Model\SavedTokenFactory $savedTokenFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     */

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Sapient\Worldpay\Model\SavedTokenFactory $savedTokenFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Sapient\Worldpay\Helper\Data $worldpayhelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->savedToken = $savedTokenFactory;
        $this->worldpayhelper = $worldpayhelper;
    }

    /**
     * Get All Tokens
     *
     * @param int $customerId
     * @return mixed
     */
    public function getAllTokens($customerId)
    {
        $merchantTokenEnabled = $this->worldpayhelper->getMerchantTokenization();
        $tokenType = $merchantTokenEnabled ? 'merchant' : 'shopper';
        return  $this->savedToken->create()->getCollection()
                                    ->addFieldToSelect([
                                        'card_brand',
                                        'card_number',
                                        'cardholder_name',
                                        'card_expiry_month',
                                        'card_expiry_year',
                                        'transaction_identifier',
                                        'token_code'])
                                    ->addFieldToFilter('customer_id', ['eq' => $customerId])
                                    ->addFieldToFilter('token_type', ['eq' => $tokenType]);
    }
    /**
     * Get All Payment Tokens
     *
     * @return string
     */

    public function getAllPaymentTokens()
    {
        $jsonResponse = [];
        if (!$this->customerSession->getCustomerId()) {
            $jsonResponse['status'] = false;
            $jsonResponse['msg'] = 'Log in to see saved tokens';
        }

        try {
            $savedTokens = $this->getAllTokens($this->customerSession->getCustomerId());
            if ($savedTokens) {
                $alltokens = [];
                foreach ($savedTokens as $key => $token) {
                    $alltokens[$key]['token_id'] = $token->getId();
                    $alltokens[$key]['token_code'] = $token->getData('token_code');
                    $alltokens[$key]['cardholder_name'] = $token->getData('cardholder_name');
                    $alltokens[$key]['card_number'] = $token->getData('card_number');
                    $alltokens[$key]['card_expiry_month'] = $token->getData('card_expiry_month');
                    $alltokens[$key]['card_expiry_year'] = $token->getData('card_expiry_year');
                }

                $jsonResponse['status'] = true;
                $jsonResponse['tokens'] = $alltokens;
                $jsonResponse['msg'] = 'Success';
            }
        } catch (\Exception $e) {
            $jsonResponse['status'] = false;
            $jsonResponse['msg'] = $e->getMessage();
        }
        return json_encode($jsonResponse);
    }
}
