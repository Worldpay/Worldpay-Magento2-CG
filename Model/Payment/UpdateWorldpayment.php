<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

use Sapient\Worldpay\Model\SavedTokenFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Vault\Model\CreditCardTokenFactory;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Updating Risk gardian
 */
class UpdateWorldpayment
{
    /**
     * @var \Sapient\Worldpay\Model\WorldpaymentFactory
     */
    protected $worldpaypayment;
    /**
     * @var mixed
     */
    protected $paymentMethodType;
    
    /**
     * @var array
     */
    public $apmMethods = ['ACH_DIRECT_DEBIT-SSL','SEPA_DIRECT_DEBIT-SSL'];
    /**
     * @var \Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory
     */
    private $transactionsFactory;
    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param SavedTokenFactory $savedTokenFactory
     * @param \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param CreditCardTokenFactory $paymentTokenFactory
     * @param \Magento\Backend\Model\Session\Quote $session
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param EncryptorInterface $encryptor
     * @param \Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory $transactionsFactory
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        SavedTokenFactory $savedTokenFactory,
        \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Customer\Model\Session $customerSession,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        CreditCardTokenFactory $paymentTokenFactory,
        \Magento\Backend\Model\Session\Quote $session,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        EncryptorInterface $encryptor,
        \Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory $transactionsFactory
    ) {
        $this->wplogger = $wplogger;
        $this->savedTokenFactory = $savedTokenFactory;
        $this->worldpaypayment = $worldpaypayment;
        $this->worldpayHelper = $worldpayHelper;
        $this->_messageManager = $messageManager;
        $this->customerSession = $customerSession;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->quotesession = $session;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->transactionFactory = $transactionsFactory;
    }

    /**
     * Updating Risk gardian
     *
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse
     * @param \Magento\Payment\Model\InfoInterface $paymentObject
     * @param string|null $tokenId
     * @param string|null $disclaimerFlag
     */
    public function updateWorldpayPayment(
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        \Magento\Payment\Model\InfoInterface $paymentObject,
        $tokenId = null,
        $disclaimerFlag = null
    ) {
        $responseXml=$directResponse->getXml();
        $merchantCode = $responseXml['merchantCode'];
        $orderStatus = $responseXml->reply->orderStatus;
        $orderCode=$orderStatus['orderCode'];
        $payment=$orderStatus->payment;
        $cardDetail=$payment->paymentMethodDetail->card;
        if (isset($cardDetail)) {
            $cardNumber= $cardDetail['number'];
        } else {
            $cardNumber='';
        }
        $paymentStatus=$payment->lastEvent;
        $cvcnumber=$payment->CVCResultCode['description'];
        $avsnumber=$payment->AVSResultCode['description'];
        $riskScore=$payment->riskScore['value'];
        $riskProviderid=$payment->riskScore['RGID'];
        $riskProviderScore=$payment->riskScore['tScore'];
        $riskProviderThreshold=$payment->riskScore['tRisk'];
        $riskProviderFinalScore=$payment->riskScore['finalScore'];
        $refusalcode=$payment->issueResponseCode['code'] ? : $payment->ISO8583ReturnCode['code'];
        $refusaldescription=$payment->issueResponseCode['description'] ? : $payment->ISO8583ReturnCode['description'];
        $lataminstalments=$payment->instalments[0];
        $primeRoutingEnabled = $this->getPrimeRoutingEnabled($paymentObject);
        $networkUsed= $payment->primeRoutingResponse?$payment->primeRoutingResponse->networkUsed[0]:'';
        $issureInsightresponse=$this->getIssuerInsightResponseData($payment);
        $riskProvider = $payment->riskScore?$payment->riskScore['Provider']:'';
        $fraudsightData = $this->getFraudsightData($payment);
        $wpp = $this->worldpaypayment->create();
        $wpp = $wpp->loadByWorldpayOrderId($orderCode);
        
        $wpp->setData('card_number', $cardNumber);
        $wpp->setData('payment_status', $paymentStatus);
        if ($payment->paymentMethod[0]) {
            if (!in_array(
                strtoupper($payment->paymentMethod[0]),
                $this->apmMethods
            )) {
                $this->paymentMethodType = str_replace(
                    ["_CREDIT","_DEBIT","_ELECTRON"],
                    "",
                    $payment->paymentMethod[0]
                );
                $wpp->setData('payment_type', str_replace(
                    ["_CREDIT","_DEBIT","_ELECTRON"],
                    "",
                    $this->paymentMethodType
                ));
            } else {
                $this->paymentMethodType = str_replace("_CREDIT", "", $payment->paymentMethod[0]);
                $wpp->setData('payment_type', str_replace("_CREDIT", "", $this->paymentMethodType));
            }
        }
        $wpp->setData('avs_result', $avsnumber);
        $wpp->setData('cvc_result', $cvcnumber);
        $wpp->setData('risk_score', $riskScore);
        $wpp->setData('merchant_id', $responseXml['merchantCode']);
        $wpp->setData('risk_provider_score', $riskProviderScore);
        $wpp->setData('risk_provider_id', $riskProviderid);
        $wpp->setData('risk_provider_threshold', $riskProviderThreshold);
        $wpp->setData('risk_provider_final', $riskProviderFinalScore);
        $wpp->setData('refusal_code', $refusalcode);
        $wpp->setData('refusal_description', $refusaldescription);
        $wpp->setData('aav_address_result_code', $payment->AAVAddressResultCode['description']);
        $wpp->setData('avv_postcode_result_code', $payment->AAVPostcodeResultCode['description']);
        $wpp->setData('aav_cardholder_name_result_code', $payment->AAVCardholderNameResultCode['description']);
        $wpp->setData('aav_telephone_result_code', $payment->AAVTelephoneResultCode['description']);
        $wpp->setData('aav_email_result_code', $payment->AAVEmailResultCode['description']);
        $wpp->setData('is_recurring_order', $paymentObject->getIsRecurringOrder());
        $wpp->setData('latam_instalments', $lataminstalments);
        $wpp->setData('is_primerouting_enabled', $primeRoutingEnabled);
        $wpp->setData('primerouting_networkused', $networkUsed);
        if ($issureInsightresponse) {
            $wpp->setData('source_type', $issureInsightresponse['sourceType']);
            $wpp->setData('available_balance', $issureInsightresponse['availableBalance']);
            $wpp->setData('prepaid_card_type', $issureInsightresponse['prepaidCardType']);
            $wpp->setData('reloadable', $issureInsightresponse['reloadable']);
            $wpp->setData('card_product_type', $issureInsightresponse['cardProductType']);
            $wpp->setData('affluence', $issureInsightresponse['affluence']);
            $wpp->setData('account_range_id', $issureInsightresponse['accountRangeId']);
            $wpp->setData('issuer_country', $issureInsightresponse['issuerCountry']);
            $wpp->setData('virtual_account_number', $issureInsightresponse['virtualAccountNumber']);
        }
        $wpp->setData('risk_provider', $riskProvider);
        
        if ($fraudsightData) {
            $wpp->setData('fraudsight_message', $fraudsightData['message']);
            if (isset($fraudsightData['score'])) {
                $wpp->setData('fraudsight_score', $fraudsightData['score']);
            }
            if (isset($fraudsightData['reasonCode'])) {
                $wpp->setData('fraudsight_reasoncode', $fraudsightData['reasonCode']);
            }
        }
        
        $wpp->save();
        
        if ($this->customerSession->getIsSavedCardRequested() && $orderStatus->token) {
                $this->customerSession->unsIsSavedCardRequested();
                $tokenNodeWithError = $orderStatus->token->xpath('//error');
            if (!$tokenNodeWithError) {
                $tokenElement = $orderStatus->token;
                $this->saveTokenData($tokenElement, $payment, $merchantCode, $disclaimerFlag, $orderCode);
                // vault and instant purchase configuration goes here
                $paymentToken = $this->getVaultPaymentToken($tokenElement);
                if (null !== $paymentToken) {
                    $extensionAttributes = $this->getExtensionAttributes($paymentObject);
                    $this->getAdditionalInformation($paymentObject);
                    $extensionAttributes->setVaultPaymentToken($paymentToken);
                }
            }
        } else {
             $tokenNodeWithError = $orderStatus->token->xpath('//error');
            if (!$tokenNodeWithError && $tokenId != null) {
                $this->saveTokenDataToTransactions($tokenId, $orderCode);
            }
        }
    }
    
    /**
     * Updating Risk gardian
     *
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse
     * @param InfoInterface $paymentObject
     * @param string|null $tokenId
     * @param string|null $disclaimerFlag
     */
    public function updateWorldpayPaymentForMyAccount(
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        $paymentObject,
        $tokenId = null,
        $disclaimerFlag = null
    ) {
        $responseXml=$directResponse->getXml();
        $merchantCode = $responseXml['merchantCode'];
        $orderStatus = $responseXml->reply->orderStatus;
        $orderCode=$orderStatus['orderCode'];
        $payment=$orderStatus->payment;
        $cardDetail=$payment->paymentMethodDetail->card;
        if (isset($cardDetail)) {
            $cardNumber= $cardDetail['number'];
        } else {
            $cardNumber='';
        }
        $paymentStatus=$payment->lastEvent;
        $cvcnumber=$payment->CVCResultCode['description'];
        $avsnumber=$payment->AVSResultCode['description'];
        $riskScore=$payment->riskScore['value'];
        $riskProviderid=$payment->riskScore['RGID'];
        $riskProviderScore=$payment->riskScore['tScore'];
        $riskProviderThreshold=$payment->riskScore['tRisk'];
        $riskProviderFinalScore=$payment->riskScore['finalScore'];
        $refusalcode=$payment->issueResponseCode['code'] ? : $payment->ISO8583ReturnCode['code'];
        $refusaldescription=$payment->issueResponseCode['description'] ? : $payment->ISO8583ReturnCode['description'];
        $lataminstalments  = "";
        if (($payment->instalments[0]) !== null) {
            $lataminstalments=$payment->instalments[0];
        }
        $wpp = $this->worldpaypayment->create();
        $wpp = $wpp->loadByWorldpayOrderId($orderCode);
        
        $wpp->setData('card_number', $cardNumber);
        $wpp->setData('payment_status', $paymentStatus);
        if ($payment->paymentMethod[0]) {
            $this->paymentMethodType = str_replace(["_CREDIT","_DEBIT","_ELECTRON"], "", $payment->paymentMethod[0]);
            $wpp->setData('payment_type', str_replace(["_CREDIT","_DEBIT","_ELECTRON"], "", $this->paymentMethodType));
        }
        $wpp->setData('avs_result', $avsnumber);
        $wpp->setData('cvc_result', $cvcnumber);
        $wpp->setData('risk_score', $riskScore);
        $wpp->setData('merchant_id', $responseXml['merchantCode']);
        $wpp->setData('risk_provider_score', $riskProviderScore);
        $wpp->setData('risk_provider_id', $riskProviderid);
        $wpp->setData('risk_provider_threshold', $riskProviderThreshold);
        $wpp->setData('risk_provider_final', $riskProviderFinalScore);
        $wpp->setData('refusal_code', $refusalcode);
        $wpp->setData('refusal_description', $refusaldescription);
        $wpp->setData('aav_address_result_code', $payment->AAVAddressResultCode['description']);
        $wpp->setData('avv_postcode_result_code', $payment->AAVPostcodeResultCode['description']);
        $wpp->setData('aav_cardholder_name_result_code', $payment->AAVCardholderNameResultCode['description']);
        $wpp->setData('aav_telephone_result_code', $payment->AAVTelephoneResultCode['description']);
        $wpp->setData('aav_email_result_code', $payment->AAVEmailResultCode['description']);
        $wpp->setData('latam_instalments', $lataminstalments);
        $wpp->save();
        if ($this->customerSession->getIsSavedCardRequested() && $orderStatus->token) {
                $this->customerSession->unsIsSavedCardRequested();
                $tokenNodeWithError = $orderStatus->token->xpath('//error');
            if (!$tokenNodeWithError) {
                $tokenElement = $orderStatus->token;
                $this->saveTokenData($tokenElement, $payment, $merchantCode, $disclaimerFlag, $orderCode);
                // vault and instant purchase configuration goes here
                $this->setVaultPaymentTokenMyAccount($tokenElement);
            }
        } else {
             $tokenNodeWithError = $orderStatus->token->xpath('//error');
            if (!$tokenNodeWithError && $tokenId != null) {
                $this->saveTokenDataToTransactions($tokenId, $orderCode);
            }
        }
    }

    /**
     * Saved token data
     *
     * @param array $tokenElement
     * @param Payment $payment
     * @param string $merchantCode
     * @param string|null $disclaimerFlag
     * @param string|null $orderCode
     */
    public function saveTokenData($tokenElement, $payment, $merchantCode, $disclaimerFlag = null, $orderCode = null)
    {
        $savedTokenFactory = $this->savedTokenFactory->create();
        // checking tokenization exist or not
            $customerId = $tokenElement[0]->authenticatedShopperID[0];
            $tokenScope = 'shopper';
        if (!$customerId) {
            $customerId = $this->customerSession->getCustomerId();
            $tokenScope = 'merchant';
        }
            $tokenDataExist = $savedTokenFactory->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('token_code', $tokenElement[0]->tokenDetails[0]->paymentTokenID[0])
                ->getFirstItem()->getData();
        // checking storedcredentials exist or not
//        if($storedCredentialsElement){
//            $storedCredentialsDataExist = $savedTokenFactory->getCollection()
//                ->addFieldToFilter('customer_id', $customerId)
//                ->addFieldToFilter('transaction_identifier', $storedCredentialsElement[0]->transactionIdentifier[0])
//                ->getFirstItem()->getData();
//        }
        if (empty($tokenDataExist)) {
            if ($payment->schemeResponse) {
                $savedTokenFactory->setTransactionIdentifier($payment->schemeResponse[0]->transactionIdentifier[0]);
            }
            $binNumber = $tokenElement[0]->paymentInstrument[0]->cardDetails[0]->derived[0]->bin[0];
            $savedTokenFactory->setTokenCode($tokenElement[0]->tokenDetails[0]->paymentTokenID[0]);
            $dateNode = $tokenElement[0]->tokenDetails->paymentTokenExpiry->date;
            $tokenexpirydate = (int)$dateNode['year'].'-'.(int)$dateNode['month'].'-'.(int)$dateNode['dayOfMonth'];
            $savedTokenFactory->setTokenExpiryDate($tokenexpirydate);
            $savedTokenFactory->setTokenReason($tokenElement[0]->tokenReason[0]);
            $savedTokenFactory->setCardNumber($tokenElement[0]->paymentInstrument[0]
                    ->cardDetails[0]->derived[0]->obfuscatedPAN[0]);
            $savedTokenFactory->setCardholderName($tokenElement[0]->paymentInstrument[0]
                    ->cardDetails[0]->cardHolderName[0]);
            $savedTokenFactory->setCardExpiryMonth((int)$tokenElement[0]->paymentInstrument
                    ->cardDetails->expiryDate->date['month']);
            $savedTokenFactory->setCardExpiryYear((int)$tokenElement[0]->paymentInstrument
                    ->cardDetails->expiryDate->date['year']);
            $paymentmethodmethod = str_replace(["_CREDIT","_DEBIT","_ELECTRON"], "", $payment->paymentMethod[0]);
            $savedTokenFactory->setMethod($paymentmethodmethod);
            $savedTokenFactory->setCardBrand($tokenElement[0]->paymentInstrument[0]
                    ->cardDetails[0]->derived[0]->cardBrand[0]);
            $savedTokenFactory->setCardSubBrand($tokenElement[0]->paymentInstrument[0]
                    ->cardDetails[0]->derived[0]->cardSubBrand[0]);
            $savedTokenFactory->setCardIssuerCountryCode($tokenElement[0]->paymentInstrument[0]
                    ->cardDetails[0]->derived[0]->issuerCountryCode[0]);
            $savedTokenFactory->setMerchantCode($merchantCode[0]);
            $savedTokenFactory->setCustomerId($customerId);
            $savedTokenFactory->setAuthenticatedShopperID($tokenElement[0]->authenticatedShopperID[0]);
            if ($binNumber) {
                $bin = $tokenElement[0]->paymentInstrument[0]->cardDetails[0]->derived[0]->bin[0];
                $savedTokenFactory->setBinNumber($bin);
            }
            if ($disclaimerFlag) {
                $savedTokenFactory->setDisclaimerFlag($disclaimerFlag);
            }
            $savedTokenFactory->setTokenType($tokenScope);
            $savedTokenFactory->save();
            $tokenId = $savedTokenFactory->getId();
            $this->saveTokenDataToTransactions($tokenId, $orderCode);
        } else {
            $tokenId = $tokenDataExist['id'];
            $this->saveTokenDataToTransactions($tokenId, $orderCode);
            if (!$this->customerSession->getIavCall()) {
                $this->_messageManager->addNotice(__($this->worldpayHelper->getCreditCardSpecificexception('CCAM22')));
            }
            return;
        }
    }

    /**
     * Get vault payment token entity
     *
     * @param array $tokenElement
     * @return PaymentTokenInterface|null
     */
    protected function getVaultPaymentToken($tokenElement)
    {
        // Check token existing in gateway response
        $token = $tokenElement[0]->tokenDetails[0]->paymentTokenID[0];
        if (empty($token)) {
            return null;
        }

        /**
         * Payment token interface
         *
         * @var PaymentTokenInterface $paymentToken
         **/
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($token);
        $paymentToken->setExpiresAt($this->getExpirationDate($tokenElement));
        $paymentToken->setIsVisible(true);
        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => $this->paymentMethodType,
            'maskedCC' => $this->getLastFourNumbers($tokenElement[0]->paymentInstrument[0]
                    ->cardDetails[0]->derived[0]->obfuscatedPAN[0]),
            'expirationDate'=> $this->getExpirationMonthAndYear($tokenElement)
        ]));
        return $paymentToken;
    }
    
    /**
     * Set vault payment token
     *
     * @param int|string $tokenElement
     * @return mixed
     */
    protected function setVaultPaymentTokenMyAccount($tokenElement)
    {
        // Check token existing in gateway response
        $token = $tokenElement[0]->tokenDetails[0]->paymentTokenID[0];
        if (empty($token)) {
            return null;
        }

        /**
         * Payment token interface
         *
         * @var PaymentTokenInterface $paymentToken
         */
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($token);
        $paymentToken->setExpiresAt($this->getExpirationDate($tokenElement));
        $paymentToken->setIsVisible(true);
        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
           'type' => $this->paymentMethodType,
           'maskedCC' => $this->getLastFourNumbers($tokenElement[0]->paymentInstrument[0]
                   ->cardDetails[0]->derived[0]->obfuscatedPAN[0]),
           'expirationDate'=> $this->getExpirationMonthAndYear($tokenElement)
        ]));
        $paymentToken->setIsActive(true);
        $paymentToken->setPaymentMethodCode('worldpay_cc');
        $paymentToken->setCustomerId($this->customerSession->getCustomerId());
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
        $this->paymentTokenRepository->save($paymentToken);
    }
    
    /**
     * Generate public hash
     *
     * @param PaymentTokenInterface $paymentToken
     * @return string
     */
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
    
    /**
     * Get expiration month and year
     *
     * @param array $tokenElement
     * @return string
     */
    public function getExpirationMonthAndYear($tokenElement)
    {
        $month = $tokenElement[0]->paymentInstrument[0]->cardDetails[0]->expiryDate[0]->date['month'];
        $year = $tokenElement[0]->paymentInstrument[0]->cardDetails[0]->expiryDate[0]->date['year'];
        return $month.'/'.$year;
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
     * Generates CC expiration date provided in payment.
     *
     * @param TokenStateInterface $tokenElement
     * @return string
     */
    private function getExpirationDate($tokenElement)
    {
        $dateNode = $tokenElement[0]->tokenDetails->paymentTokenExpiry->date;
        $expDate = new \DateTime(
            (int)$dateNode['year']
            . '-'
            . (int)$dateNode['month']
            . '-'
            . (int)$dateNode['dayOfMonth']
            . ' '
            . '00:00:00',
            new \DateTimeZone('UTC')
        );
       // $expDate->add(new \DateInterval('P1M'));
        return $expDate->format('Y-m-d 00:00:00');
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
     * Retrive the extension attributes
     *
     * @param InfoInterface $payment
     * @return mixed
     */
    private function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }
    
    /**
     * Provides additional information part specific for payment method.
     *
     * @param InfoInterface $payment
     */
    private function getAdditionalInformation(InfoInterface $payment)
    {
        $additionalInformation = $payment->getAdditionalInformation();
        if (null === $additionalInformation) {
            $additionalInformation = $this->paymentExtensionFactory->create();
        }
        $additionalInformation[VaultConfigProvider::IS_ACTIVE_CODE] = true;
        $payment->setAdditionalInformation($additionalInformation);
    }
    
    /**
     * Save token of the given transaction
     *
     * @param string $tokenId
     * @param string $worldpayOrderCode
     * @return void
     */
    public function saveTokenDataToTransactions($tokenId, $worldpayOrderCode)
    {
        $orderId = explode('-', $worldpayOrderCode);
        $transactions = $this->transactionFactory->create();
        $transactions->setWorldpayTokenId($tokenId);
        $transactions->setWorldpayOrderId($worldpayOrderCode);
        $transactions->setOriginalOrderIncrementId($orderId[0]);
        $transactions->save();
    }

    /**
     * Get prime routing enabled
     *
     * @param InfoInterface $paymentObject
     * @return bool
     */
    private function getPrimeRoutingEnabled(InfoInterface $paymentObject)
    {
        $paymentAditionalInformation = $paymentObject->getAdditionalInformation();
        if (!empty($paymentAditionalInformation)
                && array_key_exists('worldpay_primerouting_enabled', $paymentAditionalInformation)) {
            $wpPrimeRoutingEnabled=$paymentAditionalInformation['worldpay_primerouting_enabled'];
            return $wpPrimeRoutingEnabled;
        }
    }
    
    /**
     * Get issue insight response data
     *
     * @param Payment $payment
     * @return array
     */
    private function getIssuerInsightResponseData($payment)
    {
        $issuerInsightData = [];
        $enhancedAuthResponse = $payment->enhancedAuthResponse;
        if (!empty($enhancedAuthResponse)) {
            $issuerInsightData['sourceType'] = $enhancedAuthResponse->fundingSource->sourceType;
            $issuerInsightData['availableBalance'] = $enhancedAuthResponse->fundingSource->availableBalance;
            $issuerInsightData['prepaidCardType'] = $enhancedAuthResponse->fundingSource->prepaidCardType;
            $issuerInsightData['reloadable'] = $enhancedAuthResponse->fundingSource->reloadable;
            $issuerInsightData['cardProductType'] = $enhancedAuthResponse->cardProductType;
            $issuerInsightData['affluence'] = $enhancedAuthResponse->affluence;
            $issuerInsightData['accountRangeId'] = $enhancedAuthResponse->accountRangeId;
            $issuerInsightData['issuerCountry'] = $enhancedAuthResponse->issuerCountry;
            $issuerInsightData['virtualAccountNumber'] = $enhancedAuthResponse->virtualAccountNumber;
            
            return $issuerInsightData;
        }
    }
    
    /**
     * Get fraud sight data
     *
     * @param Payment $payment
     * @return array
     */
    private function getFraudsightData($payment)
    {
        $fraudsightData = [];
        $frausdightProvider = $payment->riskScore?$payment->riskScore['Provider']:'';
        ;
        if (strtoupper($frausdightProvider) === 'FRAUDSIGHT') {
            $fraudsightData['message'] = $payment->riskScore['message'];
            if (isset($payment->FraudSight)) {
                $fraudsightData['score']  = $payment->FraudSight['score'];
            }
            if (isset($payment->FraudSight->reasonCodes)) {
                $reasoncodes = $payment->FraudSight->reasonCodes->reasonCode;
                $fraudsightData['reasonCode']  = $this->getReasoncodes($reasoncodes);
            }
            return $fraudsightData;
        }
    }
    
    /**
     * Get reason codes
     *
     * @param array $reasoncodes
     * @return string
     */
    private function getReasoncodes($reasoncodes)
    {
        $savereasoncode = '';
        foreach ($reasoncodes as $key => $reasoncode) {
            $savereasoncode = $savereasoncode . "," . $reasoncode;
        }
        return ltrim($savereasoncode, ",");
    }
}
