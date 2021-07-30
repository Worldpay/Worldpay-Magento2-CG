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

/**
 * Updating Risk gardian
 */
class UpdateWorldpayment
{
    /**
     * @var \Sapient\Worldpay\Model\WorldpaymentFactory
     */
    protected $worldpaypayment;
    protected $paymentMethodType;
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
        $this->transactionFactory = $transactionsFactory;
    }

    /**
     * Updating Risk gardian
     *
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse
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
        $wpp = $this->worldpaypayment->create();
        $wpp = $wpp->loadByWorldpayOrderId($orderCode);
        
        $wpp->setData('card_number', $cardNumber);
        $wpp->setData('payment_status', $paymentStatus);
        if ($payment->paymentMethod[0]) {
            if(!in_array(strtoupper($payment->paymentMethod[0]),
            $this->apmMethods )) {
            $this->paymentMethodType = str_replace(["_CREDIT","_DEBIT","_ELECTRON"], "", $payment->paymentMethod[0]);
            $wpp->setData('payment_type', str_replace(["_CREDIT","_DEBIT","_ELECTRON"], "", $this->paymentMethodType));
            }else {
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
     * Saved token data
     * @param $tokenElement
     * @param $payment
     * @param $merchantCode
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
            $this->_messageManager->addNotice(__($this->worldpayHelper->getCreditCardSpecificexception('CCAM22')));
            return;
        }
    }

    protected function getVaultPaymentToken($tokenElement)
    {
        // Check token existing in gateway response
        $token = $tokenElement[0]->tokenDetails[0]->paymentTokenID[0];
        if (empty($token)) {
            return null;
        }

        /** @var PaymentTokenInterface $paymentToken */
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
    public function getExpirationMonthAndYear($tokenElement)
    {
        $month = $tokenElement[0]->paymentInstrument[0]->cardDetails[0]->expiryDate[0]->date['month'];
        $year = $tokenElement[0]->paymentInstrument[0]->cardDetails[0]->expiryDate[0]->date['year'];
        return $month.'/'.$year;
    }

    public function getLastFourNumbers($number)
    {
        return substr($number, -4);
    }

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

    private function convertDetailsToJSON($details)
    {
        $json = \Zend_Json::encode($details);
        return $json ? $json : '{}';
    }

    private function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }
    
    private function getAdditionalInformation(InfoInterface $payment)
    {
        $additionalInformation = $payment->getAdditionalInformation();
        if (null === $additionalInformation) {
            $additionalInformation = $this->paymentExtensionFactory->create();
        }
        $additionalInformation[VaultConfigProvider::IS_ACTIVE_CODE] = true;
        $payment->setAdditionalInformation($additionalInformation);
    }
    
    public function saveTokenDataToTransactions($tokenId, $worldpayOrderCode)
    {
        $orderId = explode('-', $worldpayOrderCode);
        $transactions = $this->transactionFactory->create();
        $transactions->setWorldpayTokenId($tokenId);
        $transactions->setWorldpayOrderId($worldpayOrderCode);
        $transactions->setOriginalOrderIncrementId($orderId[0]);
        $transactions->save();
    }
}