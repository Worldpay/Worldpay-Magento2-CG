<?php
namespace Sapient\Worldpay\Model\PaymentMethods;

use Exception;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * WorldPay Abstract class extended from Magento Abstract Payment class.
 */
abstract class AbstractMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canCapturePartial = true;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $_wplogger;
    /**
     * @var \Sapient\Worldpay\Model\Authorisation\DirectService
     */
    protected $directservice;
    /**
     * @var array
     */
    protected static $paymentDetails;
    /**
     * @var \Sapient\Worldpay\Model\WorldpaymentFactory
     */
    protected $worldpaypayment;
    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worlpayhelper;
    /**
     * @var array
     */
    protected $paymentdetailsdata;
    protected $_isInitializeNeeded = true;

    const REDIRECT_MODEL = 'redirect';
    const RECURRING_MODEL = 'recurring';
    const DIRECT_MODEL = 'direct';
    const WORLDPAY_CC_TYPE = 'worldpay_cc';
    const WORLDPAY_APM_TYPE = 'worldpay_apm';
    const WORLDPAY_WALLETS_TYPE = 'worldpay_wallets';
    const WORLDPAY_MOTO_TYPE = 'worldpay_moto';
    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Backend\Model\Session\Quote $adminsessionquote
     * @param \Sapient\Worldpay\Model\Authorisation\DirectService $directservice
     * @param \Sapient\Worldpay\Model\Authorisation\RedirectService $redirectservice
     * @param \Sapient\Worldpay\Model\Authorisation\TokenService $tokenservice
     * @param \Sapient\Worldpay\Model\Authorisation\MotoRedirectService $motoredirectservice
     * @param \Sapient\Worldpay\Model\Authorisation\HostedPaymentPageService $hostedpaymentpageservice
     * @param \Sapient\Worldpay\Model\Authorisation\WalletService $walletService
     * @param \Sapient\Worldpay\Helper\Registry $registryhelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment
     * @param \Sapient\Worldpay\Model\SavedTokenFactory $savecard
     * @param \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel
     * @param \Magento\Framework\Pricing\Helper\Data $pricinghelper
     * @param \Sapient\Worldpay\Model\Response\AdminhtmlResponse $adminhtmlresponse
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentutils
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Backend\Model\Session\Quote $adminsessionquote,
        \Sapient\Worldpay\Model\Authorisation\DirectService $directservice,
        \Sapient\Worldpay\Model\Authorisation\RedirectService $redirectservice,
        \Sapient\Worldpay\Model\Authorisation\TokenService $tokenservice,
        \Sapient\Worldpay\Model\Authorisation\MotoRedirectService $motoredirectservice,
        \Sapient\Worldpay\Model\Authorisation\HostedPaymentPageService $hostedpaymentpageservice,
        \Sapient\Worldpay\Model\Authorisation\WalletService $walletService,
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment,
        \Sapient\Worldpay\Model\SavedTokenFactory $savecard,
        \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel,
        \Magento\Framework\Pricing\Helper\Data $pricinghelper,
        \Sapient\Worldpay\Model\Response\AdminhtmlResponse $adminhtmlresponse,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentutils,
        \Sapient\Worldpay\Model\Payment\PaymentTypes $paymenttypes,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_wplogger = $wplogger;
        $this->directservice = $directservice;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->redirectservice = $redirectservice;
        $this->tokenservice = $tokenservice;
        $this->hostedpaymentpageservice = $hostedpaymentpageservice;
        $this->walletService = $walletService;
        $this->quoteRepository = $quoteRepository;
        $this->registryhelper = $registryhelper;
        $this->urlbuilder = $urlBuilder;
        $this->worlpayhelper = $worldpayhelper;
        $this->worldpaypayment=$worldpaypayment;
        $this->worldpaypaymentmodel = $worldpaypaymentmodel;
        $this->pricinghelper = $pricinghelper;
        $this->adminhtmlresponse = $adminhtmlresponse;
        $this->paymentutils = $paymentutils;
        $this->adminsessionquote = $adminsessionquote;
        $this->_savecard = $savecard;
        $this->authSession = $authSession;
        $this->motoredirectservice = $motoredirectservice;
        $this->paymenttypes = $paymenttypes;
        $this->registry = $registry;
    }
    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $amount = $payment->formatAmount($order->getBaseTotalDue(), true);
        $payment->setBaseAmountAuthorized($amount);
        $payment->setAmountAuthorized($order->getTotalDue());
        $data = $payment->getMethodInstance()->getCode();
        $payment->getMethodInstance()->authorize($payment, $amount);
        $this->_addtransaction($payment, $amount);
        $stateObject->setStatus('pending');
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $stateObject->setIsNotified(false);
    }

    /**
     * Authorize payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     */

    public function getOrderPlaceRedirectUrl()
    {
        return $this->registryhelper->getworldpayRedirectUrl();
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $mageOrder = $payment->getOrder();
        $quote = $this->quoteRepository->get($mageOrder->getQuoteId());
        if ($this->authSession->isLoggedIn()) {
            $adminquote = $this->adminsessionquote->getQuote();
            if (empty($quote->getReservedOrderId()) && !empty($adminquote->getReservedOrderId())) {
                $quote = $adminquote;
            }
        }

        $orderCode = $this->_generateOrderCode($quote);
        $this->authSession->setCurrencyCode($quote->getQuoteCurrencyCode());
        $this->paymentdetailsdata = self::$paymentDetails;
        try {
            $this->validatePaymentData(self::$paymentDetails);
            if (self::$paymentDetails['method'] != self::WORLDPAY_WALLETS_TYPE) {
                $this->_checkpaymentapplicable($quote);
            }
            $this->_checkShippingApplicable($quote);
            $this->_createWorldPayPayment($payment, $orderCode, $quote->getStoreId(), $quote->getReservedOrderId());
            
            $authorisationService = $this->getAuthorisationService($quote->getStoreId());
            $authorisationService->authorizePayment(
                $mageOrder,
                $quote,
                $orderCode,
                $quote->getStoreId(),
                self::$paymentDetails,
                $payment
            );
            $this->authSession->setOrderCode($orderCode);
        } catch (Exception $e) {
            $this->_wplogger->error($e->getMessage());
            $this->_wplogger->error('Authorising payment failed.');
            $errormessage = $this->worlpayhelper->updateErrorMessage($e->getMessage(), $quote->getReservedOrderId());
            $this->_wplogger->error($errormessage);
            $this->authSession->setOrderCode(false);
            throw new \Magento\Framework\Exception\LocalizedException(
                __($errormessage)
            );
        }
    }
    public function validatePaymentData($paymentData)
    {
        $mode = $this->worlpayhelper->getCcIntegrationMode();
        $method = $paymentData['method'];
        $generalErrorMessage = __($this->worlpayhelper->getCreditCardSpecificexception('CCAM13'));

        if ($method == self::WORLDPAY_CC_TYPE || $method == self::WORLDPAY_MOTO_TYPE) {
            if (isset($paymentData['additional_data'])) {
                $data = $paymentData['additional_data'];
                if (($method == self::WORLDPAY_MOTO_TYPE) &&
                       (isset($data['cpf_enabled']) && $data['cpf_enabled'] ||
                        isset($data['instalment_enabled']) && $data['instalment_enabled'])) {
                    //validating cpf number-
                    if (isset($data['cpf']) && !(preg_match("/^\d{11}$/", $data['cpf'], $matches) ||
                            preg_match("/^\d{14}$/", $data['cpf'], $matches))) {
                        $errorMsg = $this->worlpayhelper->getCreditCardSpecificexception('CCAM20');
                        throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg), 1);
                    }
                    if (isset($data['statement']) && !preg_match("/^[a-zA-Z0-9 ]*$/", $data['statement'], $matches)) {
                        $errorMsg = $this->worlpayhelper->getCreditCardSpecificexception('CCAM21');
                        throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg), 1);
                    }
                }
                if ($mode == 'redirect') {
                    if (!isset($data['cc_type'])) {
                        throw new \Magento\Framework\Exception\LocalizedException($generalErrorMessage, 1);
                    }
                    if (isset($data['cc_number']) && $data['cc_number'] != null) {
                        $errorMsg = $this->worlpayhelper->getCreditCardSpecificexception('CCAM24');
                        throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg), 1);
                    }
                } elseif ($mode == self::DIRECT_MODEL) {
                    if (!isset($data['cc_type'])) {
                        throw new \Magento\Framework\Exception\LocalizedException($generalErrorMessage, 1);
                    }
                    if ($data['cc_type'] != 'savedcard') {
                        if (!isset($data['cc_exp_year'])) {
                            $errorMsg = $this->worlpayhelper->getCreditCardSpecificexception('CCAM25');
                            throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg));
                        }
                        if (!isset($data['cc_exp_month'])) {
                            $errorMsg = $this->worlpayhelper->getCreditCardSpecificexception('CCAM26');
                            throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg));
                        }
                        if (!isset($data['cc_number'])) {
                            $errorMsg = $this->worlpayhelper->getCreditCardSpecificexception('CCAM27');
                            throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg));
                        }
                        if (!isset($data['cc_name'])) {
                            $errorMsg = $this->worlpayhelper->getCreditCardSpecificexception('CCAM28');
                            throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg));
                        }
                    }
                }
            } else {
                $errorMsg = $this->worlpayhelper->getCreditCardSpecificexception('CCAM13');
                throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg), 1);
            }
        } elseif ($method == self::WORLDPAY_APM_TYPE && !isset($paymentData['additional_data']['cc_type'])) {
            throw new \Magento\Framework\Exception\LocalizedException($generalErrorMessage, 1);
        } elseif ($method == self::WORLDPAY_WALLETS_TYPE && !isset($paymentData['additional_data']['cc_type'])) {
            throw new \Magento\Framework\Exception\LocalizedException($generalErrorMessage, 1);
        }
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        self::$paymentDetails = $data->getData();
        return $this;
    }

    /**
     * @return string
     */
    private function _generateOrderCode($quote)
    {
        return $quote->getReservedOrderId() . '-' . time();
    }

    /**
     * Save Risk gardian
     */
    private function _createWorldPayPayment(
        \Magento\Payment\Model\InfoInterface $payment,
        $orderCode,
        $storeId,
        $orderId,
        $interactionType = 'ECOM'
    ) {
        $paymentdetails = self::$paymentDetails;
        $integrationType =$this->worlpayhelper->getIntegrationModelByPaymentMethodCode($payment->getMethod(), $storeId);
        if ($paymentdetails['method'] == self::WORLDPAY_WALLETS_TYPE) {
            $integrationType = 'direct';
        }
        if ($paymentdetails['additional_data']['cc_type'] === 'ACH_DIRECT_DEBIT-SSL') {
            $integrationType = 'direct';
        }
        $mode = $this->worlpayhelper->getCcIntegrationMode();
        $method = $paymentdetails['method'];
        if (($mode == 'redirect') && $method == self::WORLDPAY_MOTO_TYPE) {
            $integrationType = 'redirect';
        }
        $wpp = $this->worldpaypayment->create();
        $wpp->setData('order_id', $orderId);
        $wpp->setData('payment_status', \Sapient\Worldpay\Model\Payment\State::STATUS_SENT_FOR_AUTHORISATION);
        $wpp->setData('worldpay_order_id', $orderCode);
        $wpp->setData('store_id', $storeId);
        $wpp->setData(
            'merchant_id',
            $this->worlpayhelper->getMerchantCode($paymentdetails['additional_data']['cc_type'])
        );
        $wpp->setData('3d_verified', $this->worlpayhelper->isDynamic3DEnabled());
        $wpp->setData('payment_model', $integrationType);
        if ($paymentdetails && !empty($paymentdetails['additional_data']['cc_type'])
                && empty($paymentdetails['additional_data']['tokenCode'])) {
            $wpp->setData('payment_type', $paymentdetails['additional_data']['cc_type']);
        } else {
            $wpp->setData('payment_type', $this->_getpaymentType());
        }
        if ($paymentdetails['method'] == self::WORLDPAY_MOTO_TYPE) {
            $interactionType='MOTO';
        }
        if ($integrationType == self::DIRECT_MODEL && $this->worlpayhelper->isCseEnabled()) {
            $wpp->setData('client_side_encryption', true);
        }
        $wpp->setData('interaction_type', $interactionType);
        // Check for Merchant Token
        $wpp->setData('token_type', $this->worlpayhelper->getMerchantTokenization());
        $wpp->save();
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->worlpayhelper->isWorldPayEnable()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Worldpay Plugin is not available')
            );
            return false;
        }
        
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $autoInvoice = $this->_scopeConfig->getValue('worldpay/general_config/capture_automatically', $storeScope);
        $partialCapture = $this->_scopeConfig->getValue('worldpay/partial_capture_config/partial_capture', $storeScope);
 
        //check the partial capture is enabled and auto invocie is disabled. if so do the partial capture.
        if ($partialCapture && !$autoInvoice) {
            return $this;
        }
            
        $mageOrder = $payment->getOrder();
        $quote = $this->quoteRepository->get($mageOrder->getQuoteId());
        $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($quote->getReservedOrderId());
        $orderId = '';
        if ($quote->getReservedOrderId()) {
            $orderId = $quote->getReservedOrderId();
        } else {
            $orderId = $mageOrder->getIncrementId();
        }
        $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($orderId);
              
        $paymenttype = $worldPayPayment->getPaymentType();
        if ($this->paymentutils->checkCaptureRequest($payment->getMethod(), $paymenttype)) {
            $this->paymentservicerequest->capture(
                $payment->getOrder(),
                $worldPayPayment,
                $payment->getMethod()
            );
        }
        $payment->setTransactionId(time());
        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->worlpayhelper->isWorldPayEnable()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Worldpay Plugin is not available')
            );
            return false;
        }
        
        $this->_wplogger->info('refund payment model function executed');
        if ($order = $payment->getOrder()) {
            $mageOrder = $payment->getOrder();
            $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($mageOrder->getIncrementId());
            $payment->getCreditmemo()->save();
            $xml = $this->paymentservicerequest->refund(
                $payment->getOrder(),
                $worldPayPayment,
                $payment->getMethod(),
                $amount,
                $payment->getCreditmemo()->getIncrementId()
            );

            $this->_response = $this->adminhtmlresponse->parseRefundResponse($xml);

            if ($this->_response->reply->ok) {
                return $this;
            }
        }
        $gatewayError = 'No matching order found in WorldPay to refund';
        $errorMsg = 'Please visit your WorldPay merchant interface and refund the order manually.';
        throw new \Magento\Framework\Exception\LocalizedException(
            __($gatewayError.' '.$errorMsg)
        );
    }

    public function canRefund()
    {
        $payment = $this->getInfoInstance()->getOrder()->getPayment();
        $mageOrder = $payment->getOrder();
        $quote = $this->quoteRepository->get($mageOrder->getQuoteId());
        $wpPayment = $this->worldpaypaymentmodel->loadByPaymentId($quote->getReservedOrderId());

        if ($wpPayment) {
            return $this->_isRefundAllowed($wpPayment->getPaymentStatus());
        }

        return parent::canRefund();
    }

    /**
     * @return bool
     */
    private function _isRefundAllowed($state)
    {
        $allowed = in_array(
            $state,
            [
                \Sapient\Worldpay\Model\Payment\State::STATUS_CAPTURED,
                \Sapient\Worldpay\Model\Payment\State::STATUS_SETTLED,
                \Sapient\Worldpay\Model\Payment\State::STATUS_SETTLED_BY_MERCHANT,
                \Sapient\Worldpay\Model\Payment\State::STATUS_SENT_FOR_REFUND,
                \Sapient\Worldpay\Model\Payment\State::STATUS_REFUNDED,
                \Sapient\Worldpay\Model\Payment\State::STATUS_REFUNDED_BY_MERCHANT,
                \Sapient\Worldpay\Model\Payment\State::STATUS_REFUND_FAILED
            ]
        );
        return $allowed;
    }

    /**
     * check paymentmethod is available for billing country
     *
     * @param $quote
     * @return bool
     * @throw Exception
     */
    protected function _checkpaymentapplicable($quote)
    {
        $type = strtoupper($this->_getpaymentType());
        $billingaddress = $quote->getBillingAddress();
        $countryId = $billingaddress->getCountryId();
        $paymenttypes = json_decode($this->paymenttypes->getPaymentType($countryId));
    }

    /**
     * check paymentmethod is available for shipping country
     * No shipping country was mentioned in config it will be applicable for all shipping country
     *
     * @param $quote
     * @return bool
     * @throw Exception
     */
    protected function _checkShippingApplicable($quote)
    {
        $type = strtoupper($this->_getpaymentType());
//        if ($type == 'KLARNA-SSL') {
//            $shippingaddress = $quote->getShippingAddress();
//            $billingaddress = $quote->getBillingAddress();
//            $shippingCountryId = $shippingaddress->getCountryId();
//            $countryId = isset($shippingCountryId)?$shippingCountryId:$billingaddress->getCountryId();
//            $paymenttypes = json_decode($this->paymenttypes->getPaymentType($countryId));
//            if (!in_array($type, $paymenttypes)) {
//                throw new \Magento\Framework\Exception\LocalizedException(
//                    __('Payment Type not valid for the shipping country')
//                );
//            }
//        }
    }

    /**
     * payment method
     *
     * @return bool
     */
    protected function _getpaymentType()
    {
        if (empty($this->paymentdetailsdata['additional_data']['tokenCode'])) {
            return  $this->paymentdetailsdata['additional_data']['cc_type'];
        } else {
            $merchantTokenEnabled = $this->worlpayhelper->getMerchantTokenization();
            $tokenType = $merchantTokenEnabled ? 'merchant' : 'shopper';
            $savedCard= $this->_savecard->create()->getCollection()
                ->addFieldToSelect(['method'])
                ->addFieldToFilter('token_code', ['eq' => $this->paymentdetailsdata['additional_data']['tokenCode']])
                ->addFieldToFilter('token_type', ['eq' => $tokenType])
                ->getData();
            if ($savedCard) {
                return $savedCard[0]['method'];
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Inavalid Card deatils. Please Refresh and check again')
                );
            }
        }
    }

    protected function _addtransaction($payment, $amount)
    {
        $order = $payment->getOrder();
        $formattedAmount = $order->getBaseCurrency()->formatTxt($amount);

        if ($payment->getIsTransactionPending()) {
            $message = 'Sent for authorization %1.';
        } else {
            $message = 'Authorized amount of %1.';
        }

        $message = __($message, $formattedAmount);

        $transaction = $payment->addTransaction(Transaction::TYPE_AUTH);
        $message = $payment->prependMessage($message);
        $payment->addTransactionCommentsToOrder($transaction, $message);
    }
    
    public function canVoidSale($order)
    {

        $payment = $order->getPayment();
        $mageOrder = $order->getOrder();
        $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($mageOrder->getIncrementId());
        $worldpaydata = $worldPayPayment->getData();

        $paymenttype = $worldPayPayment->getPaymentType();
        $isPrimeRoutingRequest = $worldPayPayment->getIsPrimeroutingEnabled();
        if (($paymenttype === 'ACH_DIRECT_DEBIT-SSL' || $isPrimeRoutingRequest)
                && !($worldPayPayment->getPaymentStatus() === 'VOIDED')) {
            $xml = $this->paymentservicerequest->voidSale(
                $payment->getOrder(),
                $worldPayPayment,
                $payment->getMethod()
            );
            $payment->setTransactionId(time());
            $this->_response = $this->adminhtmlresponse->parseVoidSaleRespone($xml);
            if ($this->_response->reply->ok) {
                return $this;
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('The void action is not available.'
                    . 'Possible reason this was already executed for this order. '
                    . 'Please check Payment Status below for confirmation.'));
        }
    }
    
    public function updateOrderStatusForVoidSale($order)
    {
        $payment = $order->getPayment();
        $mageOrder = $order->getOrder();
        $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($mageOrder->getIncrementId());
        $paymentStatus = $worldPayPayment->getPaymentStatus();
       
        if ($paymentStatus === 'VOIDED') {
            $mageOrder->setState(\Magento\Sales\Model\Order::STATE_CLOSED, true);
            $mageOrder->setStatus(\Magento\Sales\Model\Order::STATE_CLOSED);
            $mageOrder->save();
        }
    }
}
