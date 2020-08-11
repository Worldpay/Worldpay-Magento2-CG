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
    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param SavedTokenFactory $savedTokenFactory
     * @param \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
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
        \Magento\Backend\Model\Session\Quote $session
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
    }

    /**
     * Updating Risk gardian
     *
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse
     */
    public function updateWorldpayPayment(\Sapient\Worldpay\Model\Response\DirectResponse $directResponse, \Magento\Payment\Model\InfoInterface $paymentObject, $disclaimerFlag = null)
    {  
		$this->wplogger->info('Step-1 came to updateWorldpayPayment, with response');
		$this->wplogger->info(print_r($directResponse->getXml(), true));
        $responseXml=$directResponse->getXml();
        $merchantCode = $responseXml['merchantCode'];
        $orderStatus = $responseXml->reply->orderStatus;
        $orderCode=$orderStatus['orderCode'];
        $payment=$orderStatus->payment;
        $cardDetail=$payment->paymentMethodDetail->card;
        $cardNumber=$cardDetail['number'];
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

        $wpp = $this->worldpaypayment->create();
        $wpp = $wpp->loadByWorldpayOrderId($orderCode);
        $wpp->setData('card_number',$cardNumber);
        $wpp->setData('payment_status',$paymentStatus);
        if($payment->paymentMethod[0]){
            $this->paymentMethodType = str_replace("_CREDIT", "", $payment->paymentMethod[0]);
            $wpp->setData('payment_type',str_replace("_CREDIT", "", $this->paymentMethodType));
        }
        $wpp->setData('avs_result',$avsnumber);
        $wpp->setData('cvc_result', $cvcnumber);
        $wpp->setData('risk_score', $riskScore);
        $wpp->setData('merchant_id',$responseXml['merchantCode']);
        $wpp->setData('risk_provider_score', $riskProviderScore);
        $wpp->setData('risk_provider_id',$riskProviderid);
        $wpp->setData('risk_provider_threshold', $riskProviderThreshold);
        $wpp->setData('risk_provider_final', $riskProviderFinalScore);
        $wpp->setData('refusal_code', $refusalcode);
        $wpp->setData('refusal_description', $refusaldescription);
        $wpp->setData('aav_address_result_code',$payment->AAVAddressResultCode['description']);
        $wpp->setData('avv_postcode_result_code',$payment->AAVPostcodeResultCode['description']);
        $wpp->setData('aav_cardholder_name_result_code',$payment->AAVCardholderNameResultCode['description']);
        $wpp->setData('aav_telephone_result_code',$payment->AAVTelephoneResultCode['description']);
        $wpp->setData('aav_email_result_code',$payment->AAVEmailResultCode['description']);
        $wpp->save();
		
		$this->wplogger->info('Step-2 saved data to db');
        
        if ($this->customerSession->getIsSavedCardRequested() && $orderStatus->token) {
			$this->wplogger->info('Step-3 customer session is active and order having token data');
			$this->wplogger->info(print_r($orderStatus->token, true));
                $this->customerSession->unsIsSavedCardRequested();
                $tokenNodeWithError = $orderStatus->token->xpath('//error');
				$this->wplogger->info('Step-4 token node '. print_r($tokenNodeWithError, true));
                if (!$tokenNodeWithError) {
					$this->wplogger->info('Step-5 token node without any error');
                    $tokenElement = $orderStatus->token;
                    $this->saveTokenData($tokenElement, $payment, $merchantCode, $disclaimerFlag);
					$this->wplogger->info('Step-6 saving token data in to db');
                    // vault and instant purchase configuration goes here
                    //$paymentToken = $this->getVaultPaymentToken($tokenElement);
					$this->wplogger->info('Step-17 after updating of vault details');
                    //if (null !== $paymentToken) {
                        //$extensionAttributes = $this->getExtensionAttributes($paymentObject);
                        //$extensionAttributes->setVaultPaymentToken($paymentToken);
                    //}
                }
            }
        }

    /**
     * Saved token data
     * @param $tokenElement
     * @param $payment
     * @param $merchantCode
     */
    public function saveTokenData($tokenElement, $payment, $merchantCode, $disclaimerFlag=null)
    {
		$this->wplogger->info('Step-7 came to saveTokenData method');
        $savedTokenFactory = $this->savedTokenFactory->create();
        // checking tokenization exist or not
            $tokenDataExist = $savedTokenFactory->getCollection()
                ->addFieldToFilter('customer_id', $tokenElement[0]->authenticatedShopperID[0])
                ->addFieldToFilter('token_code', $tokenElement[0]->tokenDetails[0]->paymentTokenID[0])
                ->getFirstItem()->getData();
		$this->wplogger->info('Step-8 check token data already exist or not');
		$this->wplogger->info(print_r($tokenDataExist, true));
		$this->wplogger->info('token Id ---'.print_r($tokenElement[0]->tokenDetails[0]->paymentTokenID[0], true));
        // checking storedcredentials exist or not
//        if($storedCredentialsElement){
//            $storedCredentialsDataExist = $savedTokenFactory->getCollection()
//                ->addFieldToFilter('customer_id', $customerId)
//                ->addFieldToFilter('transaction_identifier', $storedCredentialsElement[0]->transactionIdentifier[0])
//                ->getFirstItem()->getData();
//        }
        if (empty($tokenDataExist)) {
			$this->wplogger->info('Step-9 if token data not exist');
            if($payment->schemeResponse){
                $savedTokenFactory->setTransactionIdentifier($payment->schemeResponse[0]->transactionIdentifier[0]);
            }
            $binNumber = $tokenElement[0]->paymentInstrument[0]->cardDetails[0]->derived[0]->bin[0];
            $savedTokenFactory->setTokenCode($tokenElement[0]->tokenDetails[0]->paymentTokenID[0]);
            $dateNode = $tokenElement[0]->tokenDetails->paymentTokenExpiry->date;
            $tokenexpirydate = (int)$dateNode['year'].'-'.(int)$dateNode['month'].'-'.(int)$dateNode['dayOfMonth'];
            $savedTokenFactory->setTokenExpiryDate($tokenexpirydate);
            $savedTokenFactory->setTokenReason($tokenElement[0]->tokenReason[0]);                
            $savedTokenFactory->setCardNumber($tokenElement[0]->paymentInstrument[0]->cardDetails[0]->derived[0]->obfuscatedPAN[0]);
            $savedTokenFactory->setCardholderName($tokenElement[0]->paymentInstrument[0]->cardDetails[0]->cardHolderName[0]);
            $savedTokenFactory->setCardExpiryMonth((int)$tokenElement[0]->paymentInstrument->cardDetails->expiryDate->date['month']);
            $savedTokenFactory->setCardExpiryYear((int)$tokenElement[0]->paymentInstrument->cardDetails->expiryDate->date['year']);
            $paymentmethodmethod = str_replace(array("_CREDIT","_DEBIT"), "", $payment->paymentMethod[0]);
            $savedTokenFactory->setMethod($paymentmethodmethod);
            $savedTokenFactory->setCardBrand($tokenElement[0]->paymentInstrument[0]->cardDetails[0]->derived[0]->cardBrand[0]);
            $savedTokenFactory->setCardSubBrand($tokenElement[0]->paymentInstrument[0]->cardDetails[0]->derived[0]->cardSubBrand[0]);
            $savedTokenFactory->setCardIssuerCountryCode($tokenElement[0]->paymentInstrument[0]->cardDetails[0]->derived[0]->issuerCountryCode[0]);
            $savedTokenFactory->setMerchantCode($merchantCode[0]);
            $savedTokenFactory->setCustomerId($tokenElement[0]->authenticatedShopperID[0]);
            $savedTokenFactory->setAuthenticatedShopperID($tokenElement[0]->authenticatedShopperID[0]);
            if($binNumber){
                $savedTokenFactory->setBinNumber($tokenElement[0]->paymentInstrument[0]->cardDetails[0]->derived[0]->bin[0]);
            }
            if($disclaimerFlag){
                $savedTokenFactory->setDisclaimerFlag($disclaimerFlag);
            }
			$this->wplogger->info('Step-10 token data saving before');
            $savedTokenFactory->save();
			$this->wplogger->info('Step-11 token data saving after');
        } else {
			$this->wplogger->info('Step-12 if token data already exist');
            $this->_messageManager->addNotice(__("You already appear to have this card number stored, if your card details have changed, you can update these via the 'my cards' section"));
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
            'maskedCC' => $this->getLastFourNumbers($tokenElement[0]->paymentInstrument[0]->cardDetails[0]->derived[0]->obfuscatedPAN[0]),
            'expirationDate'=> $this->getExpirationMonthAndYear($tokenElement)
        ]));

        return $paymentToken;
    }
    public function getExpirationMonthAndYear($tokenElement)
    {
        $dateNode = $tokenElement[0]->tokenDetails->paymentTokenExpiry->date;
        return $dateNode['month'].'/'.$dateNode['year'];
    }

    public function getLastFourNumbers($number)
    {
        return substr ($number, -4);
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
        $expDate->add(new \DateInterval('P1M'));
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
}
