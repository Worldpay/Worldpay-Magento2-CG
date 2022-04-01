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
use Sapient\Worldpay\Helper\MyAccountException;
use Exception;

/**
 * Controller for Updating Saved card
 */
class AddnewcardPost extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    protected $checkoutSession;
    const THIS_TRANSACTION = 'thisTransaction';
    const LESS_THAN_THIRTY_DAYS = 'lessThanThirtyDays';
    const THIRTY_TO_SIXTY_DAYS = 'thirtyToSixtyDays';
    const MORE_THAN_SIXTY_DAYS = 'moreThanSixtyDays';
    const DURING_TRANSACTION = 'duringTransaction';
    const CREATED_DURING_TRANSACTION = 'createdDuringTransaction';
    const CHANGED_DURING_TRANSACTION = 'changedDuringTransaction';
    const NO_ACCOUNT = 'noAccount';
    const NO_CHANGE = 'noChange';

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
        Session $customerSession,
        Validator $formKeyValidator,
        StoreManagerInterface $storeManager,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Magento\Integration\Model\Oauth\TokenFactory $tokenModelFactory,
        \Magento\SalesSequence\Model\Manager $sequenceManager,
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        MyAccountException $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->formKeyValidator = $formKeyValidator;
        $this->customerSession = $customerSession;
        $this->wplogger = $wplogger;
        $this->addressRepository = $addressRepository;
        $this->scopeConfig = $scopeConfig;
        $this->worldpayHelper = $worldpayHelper;
        $this->_paymentservicerequest = $paymentservicerequest;
        $this->session = $session;
        $this->directResponse = $directResponse;
        $this->updateWorldPayPayment = $updateWorldPayPayment;
        $this->paymentservice = $paymentservice;
        $this->_tokenModelFactory = $tokenModelFactory;
        $this->sequenceManager = $sequenceManager;
        $this->registryhelper = $registryhelper;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
    }
    
    public function getShopperAccountChangeIndicator($fromDate, $toDate, $differenceFormat = '%a')
    {
        $datetime1 = date_create($fromDate);
        $datetime2 = date_create($toDate);
        $interval = date_diff($datetime1, $datetime2);
        $days = $interval->format($differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            return self::CHANGED_DURING_TRANSACTION;
        }
    }
    
    public function getShopperAccountPasswordChangeIndicator($fromDate, $toDate, $differenceFormat = '%a')
    {
        $datetime1 = date_create($fromDate);
        $datetime2 = date_create($toDate);
        $interval = date_diff($datetime1, $datetime2);
        $days = $interval->format($differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            $indicator = !empty($this->customerSession->getCustomer()->getId())
                            ? self::CHANGED_DURING_TRANSACTION : self::NO_CHANGE;
            return $indicator;
        }
    }
    
    public function getShopperAccountShippingAddressUsageIndicator(
        $fromDate,
        $toDate,
        $differenceFormat = '%a'
    ) {
        $datetime1 = date_create($fromDate);
        $datetime2 = date_create($toDate);
        $interval = date_diff($datetime1, $datetime2);
        $days = $interval->format($differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            return self::THIS_TRANSACTION;
        }
    }
    
    public function getShopperAccountPaymentAccountIndicator($fromDate, $toDate, $differenceFormat = '%a')
    {
        $datetime1 = date_create($fromDate);
        $datetime2 = date_create($toDate);
        $interval = date_diff($datetime1, $datetime2);
        $days = $interval->format($differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            $indicator = !empty($this->customerSession->getCustomer()->getId())
                                ? self::DURING_TRANSACTION : self::NO_ACCOUNT;
            return $indicator;
        }
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
    
    public function getReservedOrderId()
    {
        return $this->sequenceManager->getSequence(
            \Magento\Sales\Model\Order::ENTITY,
            $this->getStoreId()
        )
        ->getNextValue();
    }
    
    /**
     * @return string
     */
    private function _generateOrderCode()
    {
        return $this->getReservedOrderId();
    }
    /**
     * Receive http post request to update saved card details
     */
    public function execute()
    {
       // $this->messageManager->getMessages(true);
        if (!$this->customerSession->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }
        //$validFormKey = $this->formKeyValidator->validate($this->getRequest());
        if ($this->getRequest()->isPost()) {
            try {
                $customer = $this->customerSession->getCustomer();
                $store = $this->_storeManager->getStore();
                $paymentType = "worldpay_cc";
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
                $billingaddress = $this->addressRepository->getById($customer->getData('default_billing'));
                //$merchantCode = $this->scopeConfig->getValue('worldpay/general_config/merchant_code', $storeScope);
                //$merchantCode = $this->worldpayHelper->getMerchantCode($fullRequest->payment->paymentType);
                $currencyCode = $store->getCurrentCurrencyCode();
                $exponent = $this->worldpayHelper->getCurrencyExponent($currencyCode);
                $billingadd = $this->getAddress($billingaddress);
                $fullRequest = json_decode($this->getRequest()->getContent());

                $merchantCode = $this->worldpayHelper->getMerchantCode($fullRequest->payment->paymentType);
                
                $payment = [
                    'cardNumber' => $fullRequest->payment->cardNumber,
                    'paymentType' => $fullRequest->payment->paymentType,
                    'cardHolderName' => $fullRequest->payment->cardHolderName,
                    'expiryMonth' => $fullRequest->payment->expiryMonth,
                    'expiryYear' => $fullRequest->payment->expiryYear,
                    'cseEnabled' => $fullRequest->payment->cseEnabled
                ];
                if (isset($fullRequest->payment->cvc) &&
                        !$fullRequest->payment->cvc == '' && !empty($fullRequest->payment->cvc)) {
                    $payment['cvc'] = $fullRequest->payment->cvc;
                }
                if ($this->worldpayHelper->isDynamic3DS2Enabled() && $fullRequest->payment->dfReferenceId) {
                    $payment['dfReferenceId']  = $fullRequest->payment->dfReferenceId;
                    $this->checkoutSession->setIavCall(true);
                    $this->customerSession->setIavCall(true);
                }
                $payment['sessionId'] = $this->session->getSessionId();
                $payment['myaccountSave'] = 1;
                if ($this->isIAVEnabled()) {
                    $payment['isIAVEnabled'] = 1;
                    $this->checkoutSession->setIavCall(true);
                    $this->customerSession->setIavCall(true);
                }
                $payment['shopperIpAddress'] = $this->_getClientIPAddress();
                $payment['token_type'] = $this->worldpayHelper->getMerchantTokenization();
                $payment['dynamicInteractionType'] = $this->worldpayHelper->getDynamicIntegrationType($paymentType);
                $orderParams = [];
                $incrementId = $this->_generateOrderCode();
                $isNominalAmount = $payment['paymentType'] =='DINERS-SSL' || $payment['paymentType'] == 'DANKORT-SSL' ;
                $orderParams['orderCode'] = $incrementId. '-' . time();
                $orderParams['merchantCode'] = $merchantCode;
                $orderParams['orderDescription'] = 'Add new card in My account';
                $orderParams['currencyCode'] = $currencyCode;
                $orderParams['amount'] = $isNominalAmount?1: 0;
                $orderParams['paymentDetails'] = $payment;
                $orderParams['cardAddress'] = $billingadd;
                $orderParams['billingAddress'] = $billingadd;
                $orderParams['shopperEmail'] = $customer->getEmail();
                $orderParams['exponent'] = $exponent;
                $orderParams['tokenRequestConfig'] = 1;
                $orderParams['tokenizationEnabled'] = 1;
                $orderParams['storedCredentialsEnabled'] = 1;
                $orderParams['acceptHeader'] = php_sapi_name() !== "cli" ?
                    filter_input(INPUT_SERVER, 'HTTP_ACCEPT') : '';
                $orderParams['userAgentHeader'] = php_sapi_name() !== "cli" ? filter_input(
                    INPUT_SERVER,
                    'HTTP_USER_AGENT',
                    FILTER_SANITIZE_STRING,
                    FILTER_FLAG_STRIP_LOW
                ) : '';
                $orderParams['method'] = $paymentType;
                $orderParams['orderStoreId'] = $store->getId();
                $orderParams['shopperId'] = $customer->getId();
                $orderParams['saveCardEnabled'] = 1;
                $orderParams['save_my_card'] = 1;
                $orderParams['threeDSecureConfig'] = $this->_getThreeDSecureConfig();
                $orderParams['dynamicInteractionType'] = $this->worldpayHelper->getDynamicIntegrationType($paymentType);
                $orderParams['cusDetails'] = $this->getCustomerDetailsfor3DS2();
                $orderParams['exemptionEngine'] = $this->getExemptionEngineDetails();
                $orderParams['shippingfee'] = 0;
                $orderParams['exponent'] = $exponent;
                $orderParams['primeRoutingData'] = $this->getPrimeRoutingDetails($billingadd['countryCode']);
                $payment['additional_data'] = [
                            'save_my_card' => 1,
                            'isRecurringOrder' => 0,
                            'isMyAccountSaveNewCard' => 1,
                            'subscriptionStatus' => ''
                        ];
                $this->customerSession->setIsSavedCardRequested(true);
                $response = $this->_paymentservicerequest->order($orderParams);
                $paymentService = new \SimpleXmlElement($response);
                $lastEvent = $paymentService->xpath('//lastEvent');
                $directResponse = $this->directResponse->setResponse($response);
                $threeDSecureParams = $directResponse->get3dSecureParams();
                $threeDSecureChallengeParams = $directResponse->get3ds2Params();
                if (!$this->worldpayHelper->is3dsEnabled() && isset($threeDSecureParams)) {
                    $this->wplogger->info("3Ds attempted but 3DS is not enabled for the store. "
                            . "Please contact merchant.");
                    $this->messageManager->addErrorMessage(
                        "3Ds attempted but 3DS is not enabled for the store. Please contact merchant. "
                    );
                  //return $this->resultRedirectFactory->create()->setPath('worldpay/savedcard', ['_current' => true]);
                    return  $this->resultJsonFactory->create()->setData(['success' => false]);
                }
                $threeDSecureConfig = [];
                $disclaimerFlag = isset($fullRequest->payment->disclaimerFlag)?$fullRequest->payment->disclaimerFlag:0;

                if (!empty($orderParams['primeRoutingData'])) {
                    $payment['additional_data']['worldpay_primerouting_enabled'] = true;
                } else {
                    $payment['additional_data']['worldpay_primerouting_enabled'] = false;
                }
               // $redirecturl =  $this->_url->getUrl('worldpay/threedsecure/auth', ['_secure' => true]);
                if ($threeDSecureParams) {
                    // Handles success response with 3DS & redirect for varification.
                    $this->checkoutSession->setauthenticatedOrderId($incrementId);
                     $this->checkoutSession->setIavCall(true);
                     $this->customerSession->setIavCall(true);
                    $this->_handle3DSecure($threeDSecureParams, $orderParams, $incrementId);
                    return  $this->resultJsonFactory->create()
                        ->setData(['threeD' => true]);
                } elseif ($threeDSecureChallengeParams) {
                    // Handles success response with 3DS2 & redirect for varification.
                    $this->checkoutSession->setauthenticatedOrderId($incrementId);
                    $this->checkoutSession->setIavCall(true);
                    $threeDSecureConfig = $this->get3DS2ConfigValues();
                    $this->_handle3Ds2(
                        $threeDSecureChallengeParams,
                        $orderParams,
                        $incrementId,
                        $threeDSecureConfig
                    );
                } else {
                    // Normal order goes here.(without 3DS).
                    $responseXml=$directResponse->getXml();
                    $orderStatus = $responseXml->reply->orderStatus;
                    $payment=$orderStatus->payment;
                    $riskScore=$payment->riskScore['value'];
                    $riskProviderFinalScore=$payment->riskScore['finalScore'];
                    if (($lastEvent[0] == 'AUTHORISED') ||
                        ($this->isIAVEnabled() && ($lastEvent[0] == 'CANCELLED') &&
                         ($riskScore < 100 || $riskProviderFinalScore < 100))) {
                        $this->messageManager->getMessages(true);
                        $this->updateWorldPayPayment->create()
                        ->updateWorldpayPaymentForMyAccount($directResponse, $payment, '', $disclaimerFlag);
                        $this->messageManager->addSuccess(
                            $this->worldpayHelper->getMyAccountSpecificexception('IAVMA3')
                                ? $this->worldpayHelper->getMyAccountSpecificexception('IAVMA3')
                            : 'The card has been added'
                        );
                        return  $this->resultJsonFactory->create()
                        ->setData(['success' => true]);
                    } else {
                        $this->messageManager->getMessages(true);
                        $this->messageManager->addError(
                            $this->worldpayHelper->getMyAccountSpecificexception('IAVMA4')
                                ? $this->worldpayHelper->getMyAccountSpecificexception('IAVMA4')
                            : 'Your card could not be saved'
                        );
                        return  $this->resultJsonFactory->create()
                        ->setData(['success' => false]);
                    }
                }
            } catch (Exception $e) {
                $this->wplogger->error($e->getMessage());
                $this->messageManager->getMessages(true);
                if ($e->getMessage()=== 'Unique constraint violation found') {
                    $this->messageManager
                        ->addError(__($this->worldpayHelper
                                ->getCreditCardSpecificException('CCAM22')));
                } else {
                    $this->messageManager->addException($e, __('Error: ').$e->getMessage());
                    return  $this->resultJsonFactory->create()
                        ->setData(['threeDError' => true]);
                }
                return  $this->resultJsonFactory->create()
                       ->setData(['success' => false]);
            }
        }
    }
    
     /**
      * Get order id column value
      *
      * @return string
      */
   
    public function isIAVEnabled()
    {
        return $this->worldpayHelper->isIAVEnabled();
    }
    
    private function _handle3DSecure($threeDSecureParams, $directOrderParams, $mageOrderId)
    {
        $this->registryhelper->setworldpayRedirectUrl($threeDSecureParams);
        $this->checkoutSession->set3DSecureParams($threeDSecureParams);
        $this->checkoutSession->setDirectOrderParams($directOrderParams);
        $this->checkoutSession->setAuthOrderId($mageOrderId);
    }
    
    private function _handle3Ds2($threeDSecureChallengeParams, $directOrderParams, $mageOrderId, $threeDSecureConfig)
    {
        $this->registryhelper->setworldpayRedirectUrl($threeDSecureChallengeParams);
        $this->checkoutSession->set3Ds2Params($threeDSecureChallengeParams);
        $this->checkoutSession->setDirectOrderParams($directOrderParams);
        $this->checkoutSession->setAuthOrderId($mageOrderId);
        $this->checkoutSession->set3DS2Config($threeDSecureConfig);
    }
    // get 3ds2 params from the configuration and set to checkout session
    public function get3DS2ConfigValues()
    {
        $data = [];
        $data['jwtApiKey'] =  $this->worldpayHelper->isJwtApiKey();
        $data['jwtIssuer'] =  $this->worldpayHelper->isJwtIssuer();
        $data['organisationalUnitId'] = $this->worldpayHelper->isOrganisationalUnitId();
        $data['challengeWindowType'] = $this->worldpayHelper->getChallengeWindowSize();
    
        $mode = $this->worldpayHelper->getEnvironmentMode();
        if ($mode == 'Test Mode') {
            $data['challengeurl'] =  $this->worldpayHelper->isTestChallengeUrl();
        } else {
            $data['challengeurl'] =  $this->worldpayHelper->isProductionChallengeUrl();
        }
        
        return $data;
    }
    public function getPrimeRoutingDetails($countryCode)
    {
        if ($countryCode==='US') {
            if ($this->worldpayHelper->isPrimeRoutingEnabled()
                && $this->worldpayHelper->isAdvancedPrimeRoutingEnabled()) {
                $details['primerouting'] = $this->worldpayHelper->isPrimeRoutingEnabled();
                $details['advanced_primerouting'] = $this->worldpayHelper->isAdvancedPrimeRoutingEnabled();
                $details['routing_preference'] = $this->worldpayHelper->getRoutingPreference();
                $details['debit_networks'] = $this->worldpayHelper->getDebitNetworks();
                return $details;
            } elseif ($this->worldpayHelper->isPrimeRoutingEnabled()) {
                $details['primerouting'] = $this->worldpayHelper->isPrimeRoutingEnabled();
            
                return $details;
            }
        }
    }
    
    private function _getClientIPAddress()
    {
        $REMOTE_ADDR = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
        $remoteAddresses = explode(',', $REMOTE_ADDR);
        return trim($remoteAddresses[0]);
    }
    
    private function _getThreeDSecureConfig()
    {
            return [
                'isDynamic3D' => (bool) $this->worldpayHelper->isDynamic3DEnabled(),
                'is3DSecure' => (bool) $this->worldpayHelper->is3DSecureEnabled()
            ];
    }
    
    public function getCustomerDetailsfor3DS2()
    {
        $cusDetails = [];
        $customer = $this->customerSession->getCustomer();
        $billingaddress = $this->addressRepository->getById($customer->getData('default_billing'));
        $billingadd = $this->getAddress($billingaddress);
        $now = new \DateTime();
        $cusDetails['created_at'] = !empty($customer->getCreatedAt())
               ? $customer->getCreatedAt() : $now->format('Y-m-d H:i:s');
        $cusDetails['updated_at'] = !empty($customer->getUpdatedAt())
               ? $customer->getUpdatedAt() : $now->format('Y-m-d H:i:s');
        $orderDetails = $this->worldpayHelper->getOrderDetailsByEmailId($customer->getEmail());
        $orderDetails['created_at'] = !empty($orderDetails['created_at'])
               ? $orderDetails['created_at'] : $now->format('Y-m-d H:i:s');
        $orderDetails['updated_at'] = !empty($orderDetails['updated_at'])
               ? $orderDetails['updated_at'] : $now->format('Y-m-d H:i:s');
        $orderDetails['previous_purchase'] = !empty($orderDetails['updated_at'])
               ? 'true' : 'false';
        
        $orderCount = $this->worldpayHelper->getOrdersCountByEmailId($customer->getEmail());
        if ($customer->getId()) {
            $savedCardCount = $this->worldpayHelper->getSavedCardsCount($customer->getId());
        } else {
            $savedCardCount = 0;
        }
        
        $cusDetails['shopperAccountAgeIndicator'] = $this->
               getshopperAccountAgeIndicator($cusDetails['created_at'], $now->format('Y-m-d H:i:s'));
        $cusDetails['shopperAccountChangeIndicator'] = $this->
               getShopperAccountChangeIndicator($cusDetails['updated_at'], $now->format('Y-m-d H:i:s'));
        $cusDetails['shopperAccountPasswordChangeIndicator'] = $this->
               getShopperAccountPasswordChangeIndicator($cusDetails['updated_at'], $now->format('Y-m-d H:i:s'));
        $cusDetails['shopperAccountShippingAddressUsageIndicator'] = $this->
          getShopperAccountShippingAddressUsageIndicator($orderDetails['created_at'], $now->format('Y-m-d H:i:s'));
        $cusDetails['shopperAccountPaymentAccountIndicator'] = $this->
          getShopperAccountPaymentAccountIndicator($orderDetails['created_at'], $now->format('Y-m-d H:i:s'));
        
        $cusDetails['order_details'] = $orderDetails;
        $cusDetails['order_count'] = $orderCount;
        $cusDetails['card_count'] = $savedCardCount;
        $cusDetails['shipping_method'] = '';

        //Fraudsight
        $cusDetails['shopperName'] = $billingadd['firstName'];
        $cusDetails['shopperId'] = $customer->getId();
        $cusDetails['birthDate'] = $customer->getDob();

        return $cusDetails;
    }
    public function getShopperAccountAgeIndicator($fromDate, $toDate, $differenceFormat = '%a')
    {
        $datetime1 = date_create($fromDate);
        $datetime2 = date_create($toDate);
        $interval = date_diff($datetime1, $datetime2);
        $days = $interval->format($differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            $indicator = !empty($this->customerSession->getCustomer()->getId())
                    ? self::CREATED_DURING_TRANSACTION : self::NO_ACCOUNT;
            return $indicator;
        }
    }
    /**
     * Frame Shipping Address
     * @return array
     */
    private function getAddress($addressDetails)
    {
        
        $address = [
                        'firstName'     => $addressDetails->getFirstName(),
                        'lastName'      => $addressDetails->getLastName(),
                        'street'        => $addressDetails->getStreet()[0],
                        'postalCode'      => $addressDetails->getPostcode(),
                        'city'          => $addressDetails->getCity(),
                        'countryCode'    => $addressDetails->getCountryId()
                    ];
        return $address;
    }
    
    public function getExemptionEngineDetails()
    {
        $exemptionEngine = [];
        $exemptionEngine['enabled'] = $this->scopeConfig->
               getValue(
                   'worldpay/exemption_config/exemption_engine',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE
               );
        $exemptionEngine['placement'] = $this->scopeConfig->
               getValue(
                   'worldpay/exemption_config/exemption_engine_placement',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE
               );
        $exemptionEngine['type'] = $this->scopeConfig->
               getValue(
                   'worldpay/exemption_config/exemption_engine_type',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE
               );
        return $exemptionEngine;
    }
    /**
     * @return Sapient/WorldPay/Model/Token
     */
    protected function _getPaymentModel()
    {
        $paymentData = $this->getRequest()->getContent();
        
        return $paymentData;
    }
}
