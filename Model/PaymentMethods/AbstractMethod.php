<?php
namespace Sapient\Worldpay\Model\PaymentMethods;

use Exception;
use Magento\Payment\Model\Method\AbstractMethod as BaseAbstractMethod;
use Magento\Sales\Model\Order\Payment\Transaction;
use Sapient\Worldpay\Helper\ProductOnDemand;

/**
 * WorldPay Abstract class extended from Magento Abstract Payment class.
 */
abstract class AbstractMethod extends BaseAbstractMethod
{

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapture = true;
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefund = true;
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canVoid = true;
    /**
     * Availability option
     *
     * @var bool
     */
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

    /**
     * @var \Sapient\Worldpay\Model\Request\PaymentServiceRequest
     */
    protected $paymentservicerequest;

    /**
     * @var \Sapient\Worldpay\Model\Authorisation\TokenService
     */
    protected $tokenservice;
    /**
     * @var \Sapient\Worldpay\Model\Authorisation\HostedPaymentPageService
     */
    protected $hostedpaymentpageservice;
    /**
     * @var \Sapient\Worldpay\Model\Authorisation\WalletService
     */
    protected $walletService;
    /**
     * @var \Sapient\Worldpay\Model\Authorisation\PayByLinkService
     */
    protected $paybylinkservice;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Sapient\Worldpay\Helper\Registry
     */
    protected $registryhelper;
    /**
     * @var \Sapient\Worldpay\Model\Worldpayment
     */
    protected $worldpaypaymentmodel;
    /**
     * @var \Sapient\Worldpay\Model\Response\AdminhtmlResponse
     */
    protected $adminhtmlresponse;
    /**
     * @var \Sapient\Worldpay\Model\Utilities\PaymentMethods
     */
    protected $paymentutils;
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $adminsessionquote;
    /**
     * @var \Sapient\Worldpay\Model\SavedTokenFactory
     */
    protected $_savecard;
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;
    /**
     * @var \Sapient\Worldpay\Model\Authorisation\MotoRedirectService
     */
    protected $motoredirectservice;

    /**
     * @var \Sapient\Worldpay\Model\Payment\PaymentTypes
     */
    protected $paymenttypes;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Sapient\Worldpay\Helper\Multishipping
     */
    protected $multishippingHelper;

    protected ProductOnDemand $productOnDemandHelper;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    public const REDIRECT_MODEL = 'redirect';
    public const RECURRING_MODEL = 'recurring';
    public const DIRECT_MODEL = 'direct';
    public const WORLDPAY_CC_TYPE = 'worldpay_cc';
    public const WORLDPAY_APM_TYPE = 'worldpay_apm';
    public const WORLDPAY_WALLETS_TYPE = 'worldpay_wallets';
    public const WORLDPAY_MOTO_TYPE = 'worldpay_moto';

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
     * @param \Sapient\Worldpay\Model\Authorisation\TokenService $tokenservice
     * @param \Sapient\Worldpay\Model\Authorisation\MotoRedirectService $motoredirectservice
     * @param \Sapient\Worldpay\Model\Authorisation\HostedPaymentPageService $hostedpaymentpageservice
     * @param \Sapient\Worldpay\Model\Authorisation\WalletService $walletService
     * @param \Sapient\Worldpay\Model\Authorisation\PayByLinkService $paybylinkservice
     * @param \Sapient\Worldpay\Helper\Registry $registryhelper
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment
     * @param \Sapient\Worldpay\Model\SavedTokenFactory $savecard
     * @param \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel
     * @param \Sapient\Worldpay\Model\Response\AdminhtmlResponse $adminhtmlresponse
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentutils
     * @param \Sapient\Worldpay\Model\Payment\PaymentTypes $paymenttypes
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
     * @param array $data
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
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
        \Sapient\Worldpay\Model\Authorisation\TokenService $tokenservice,
        \Sapient\Worldpay\Model\Authorisation\MotoRedirectService $motoredirectservice,
        \Sapient\Worldpay\Model\Authorisation\HostedPaymentPageService $hostedpaymentpageservice,
        \Sapient\Worldpay\Model\Authorisation\WalletService $walletService,
        \Sapient\Worldpay\Model\Authorisation\PayByLinkService $paybylinkservice,
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment,
        \Sapient\Worldpay\Model\SavedTokenFactory $savecard,
        \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel,
        \Sapient\Worldpay\Model\Response\AdminhtmlResponse $adminhtmlresponse,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentutils,
        \Sapient\Worldpay\Model\Payment\PaymentTypes $paymenttypes,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Sapient\Worldpay\Helper\Multishipping $multishippingHelper,
        ProductOnDemand $productOnDemandHelper,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
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
        $this->tokenservice = $tokenservice;
        $this->hostedpaymentpageservice = $hostedpaymentpageservice;
        $this->walletService = $walletService;
        $this->paybylinkservice = $paybylinkservice;
        $this->quoteRepository = $quoteRepository;
        $this->registryhelper = $registryhelper;
        $this->worlpayhelper = $worldpayhelper;
        $this->worldpaypayment = $worldpaypayment;
        $this->worldpaypaymentmodel = $worldpaypaymentmodel;
        $this->adminhtmlresponse = $adminhtmlresponse;
        $this->paymentutils = $paymentutils;
        $this->adminsessionquote = $adminsessionquote;
        $this->_savecard = $savecard;
        $this->authSession = $authSession;
        $this->motoredirectservice = $motoredirectservice;
        $this->paymenttypes = $paymenttypes;
        $this->registry = $registry;
        $this->_request = $request;
        $this->multishippingHelper = $multishippingHelper;
        $this->productOnDemandHelper = $productOnDemandHelper;
    }
    /**
     * Initializer
     *
     * @param string $paymentAction
     * @param Object $stateObject
     */
    public function initialize($paymentAction, $stateObject)
    {
        $this->multishippingHelper->checkIsMultishippingIssue();
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

    /**
     * Authorize payment abstract method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
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
        $increment_id = '';
        $orderId = $quote->getReservedOrderId();
        if ($this->worlpayhelper->isMultiShipping($quote)) {
            $increment_id = $mageOrder->getIncrementId();
            $orderId = $increment_id;
        }
        $paymentAdditionalData = $payment->getData('additional_data');
        $orderCode = $this->_generateOrderCode($quote, $increment_id);

        if (
            !empty($this->worlpayhelper->getOrderCodeFromCheckoutSession())
            && self::$paymentDetails['method'] === self::WORLDPAY_CC_TYPE
            && $this->worlpayhelper->getCcIntegrationMode() === self::REDIRECT_MODEL
            && $this->worlpayhelper->isIframeIntegration()
        ) {
            $orderCode = $this->worlpayhelper->getOrderCodeFromCheckoutSession();
        }

        $this->authSession->setCurrencyCode($quote->getQuoteCurrencyCode());
        $this->paymentdetailsdata = self::$paymentDetails;
        try {
            $this->validatePaymentData(self::$paymentDetails);
            if (self::$paymentDetails['method'] != self::WORLDPAY_WALLETS_TYPE) {
                $this->_checkpaymentapplicable($quote);
            }
            $this->_checkShippingApplicable($quote);
            $this->_createWorldPayPayment($payment, $orderCode, $quote->getStoreId(), $orderId);

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
            if ($this->worlpayhelper->isMultiShipping($quote)) {
                $this->multishippingHelper->setMultishippingOrderCode($orderCode);
            }
        } catch (Exception $e) {
            if ($this->worlpayhelper->isMultiShipping($quote)) {
                $this->multishippingHelper->setMultishippingIssue($orderCode);
            }
            $this->_wplogger->error($e->getMessage());
            $this->_wplogger->error('Authorising payment failed.');
            $errormessage = $this->worlpayhelper->updateErrorMessage($e->getMessage(), $orderId);
            $this->_wplogger->error($errormessage);
            $this->authSession->setOrderCode(false);
            throw new \Magento\Framework\Exception\LocalizedException(
                __($errormessage)
            );
        }
    }
    /**
     * Validate payment data
     *
     * @param array $paymentData
     */
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
                        throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg));
                    }
                    if (isset($data['statement']) && !preg_match("/^[a-zA-Z0-9 ]*$/", $data['statement'], $matches)) {
                        $errorMsg = $this->worlpayhelper->getCreditCardSpecificexception('CCAM21');
                        throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg));
                    }
                }
                if ($method == self::WORLDPAY_MOTO_TYPE && $mode == self::DIRECT_MODEL) {
                    $selectedCardType = $data['cc_type'];
                    $errorCamMsg = $this->worlpayhelper->getCreditCardSpecificexception('CTYP01');
                    $errorMsg = $errorCamMsg?$errorCamMsg:'Card number entered does not match with card type selected';
                    if ($selectedCardType !== 'savedcard') {
                        $cardTypeFromCardNum = $this -> getCardTypeFromCreditCardNumber($data['cc_number']);
                        $checkCbCarteBlue = $data['cc_type'] == 'CB-SSL' || $data['cc_type'] == 'CARTEBLEUE-SSL';
                        $selectedModifiedCardType = $checkCbCarteBlue?'ECMC-SSL':$selectedCardType;
                        if ($cardTypeFromCardNum != $selectedModifiedCardType) {
                            throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg));
                        }
                    }
                }
                if ($mode == 'redirect' && $method != self::WORLDPAY_MOTO_TYPE) {
                    if (!isset($data['cc_type'])) {
                        throw new \Magento\Framework\Exception\LocalizedException($generalErrorMessage);
                    }
                    if (isset($data['cc_number']) && $data['cc_number'] != null) {
                        $errorMsg = $this->worlpayhelper->getCreditCardSpecificexception('CCAM24');
                        throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg));
                    }
                } elseif ($mode == self::DIRECT_MODEL) {
                    if (!isset($data['cc_type'])) {
                        throw new \Magento\Framework\Exception\LocalizedException($generalErrorMessage);
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
                throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg));
            }
        } elseif ($method == self::WORLDPAY_APM_TYPE && !isset($paymentData['additional_data']['cc_type'])) {
            throw new \Magento\Framework\Exception\LocalizedException($generalErrorMessage);
        } elseif ($method == self::WORLDPAY_WALLETS_TYPE && !isset($paymentData['additional_data']['cc_type'])) {
            throw new \Magento\Framework\Exception\LocalizedException($generalErrorMessage);
        }
    }

    /**
     * Assign data to info model instance
     *
     * @param \Magento\Framework\DataObject|mixed $data
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        self::$paymentDetails = $data->getData();
        return $this;
    }

    /**
     *  Generate order code for reserved order
     *
     * @param Quote $quote
     * @param string|null $increment_id
     * @return string
     */
    private function _generateOrderCode($quote, $increment_id = null)
    {
        if (!empty($increment_id)) {
            return $increment_id . '-' . time();
        }
        return $quote->getReservedOrderId() . '-' . time();
    }

    /**
     * Save Risk gardian
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $orderCode
     * @param int $storeId
     * @param string $orderId
     * @param string $interactionType
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
        if ($paymentdetails['additional_data']['cc_type'] === 'ACH_DIRECT_DEBIT-SSL' ||
        $paymentdetails['additional_data']['cc_type'] === 'SEPA_DIRECT_DEBIT-SSL') {
            $integrationType = 'direct';
        }
        $mode = $this->worlpayhelper->getCcIntegrationMode();
        $method = $paymentdetails['method'];
        if (($mode == 'redirect') && $method == self::WORLDPAY_MOTO_TYPE) {
             $integrationType = 'direct';
        //      $integrationType = 'redirect'; uncomment to support moto redirect
        }
        $wpp = $this->worldpaypayment->create();

        $cardType = $paymentdetails['additional_data']['cc_type'];
        if ($cardType == 'savedcard') {
            $cardType = $this->_getpaymentType();
            if ($mode == 'redirect') {
                $tokenId = $this->getTokenIdByCode($paymentdetails['additional_data']['tokenCode']);
                if (empty($this->registry->registry('token_code'))) {
                    $this->registry->register('token_code', $tokenId);
                }
            }
        }

        $wpp->setData('order_id', $orderId);
        $wpp->setData('payment_status', \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SENT_FOR_AUTHORISATION);
        $wpp->setData('worldpay_order_id', $orderCode);
        $wpp->setData('store_id', $storeId);
        $wpp->setData(
            'merchant_id',
            $this->worlpayhelper->getMerchantCode($cardType)
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
        if ($this->worlpayhelper->isMultiShipping()) {
            $wpp->setData('is_multishipping_order', true);
        }
        if ($integrationType == self::DIRECT_MODEL && $this->worlpayhelper->isCseEnabled()) {
            $wpp->setData('client_side_encryption', true);
        }
        $wpp->setData('interaction_type', $interactionType);
        // Check for Merchant Token
        $wpp->setData('token_type', $this->worlpayhelper->getMerchantTokenization());
        $wpp->save();
    }

    /**
     * Capture payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->worlpayhelper->isWorldPayEnable()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Worldpay Plugin is not available')
            );
        }

        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $autoInvoice = $this->_scopeConfig->getValue('worldpay/general_config/capture_automatically', $storeScope);
        $partialCapture = $this->_scopeConfig->getValue('worldpay/partial_capture_config/partial_capture', $storeScope);

        $mageOrder = $payment->getOrder();
        $orderId = $mageOrder->getIncrementId();

        $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($orderId);
        $captureArray = '';
        //added Klarna check
        if (strpos($worldPayPayment->getPaymentType(), "KLARNA") !== false) {
            $paymenttype = "KLARNA-SSL";
            $captureArray = $this->_request->getParams();
        } else {
            $paymenttype = $worldPayPayment->getPaymentType();
        }

        $isProductOnDemand = $this->productOnDemandHelper->isProductOnDemandQuoteId($mageOrder->getQuoteId());
        $captureRequest = $this->paymentutils->checkCaptureRequest($payment->getMethod(), $paymenttype);

        if ($captureRequest && !$isProductOnDemand) {
            //total amount from invoice and order should not be same for partial capture
            if (floatval($amount) != floatval($payment->getOrder()->getGrandTotal())) {
                // to restrict Partical capture call for AMP's
                $isAPM = !$this->checkAPMforPartialCapture($paymenttype);

                if ($partialCapture && $isAPM) {
                    $this->paymentservicerequest->partialCapture(
                        $payment->getOrder(),
                        $worldPayPayment,
                        $amount,
                        $captureArray,
                        $payment->getMethod()
                    );
                } else {
                    $this->_wplogger->info("Partial Capture is disabled or not supported.");
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __("Partial Capture is disabled or not supported.")
                    );
                }
            }
            if (floatval($amount) == floatval($payment->getOrder()->getGrandTotal())) {
                //normal capture
                $this->paymentservicerequest->capture(
                    $payment->getOrder(),
                    $worldPayPayment,
                    $payment->getMethod(),
                    $captureArray
                );
            }
        }
        $payment->setTransactionId(time());
        return $this;
    }

    /**
     * Refund capture
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->worlpayhelper->isWorldPayEnable()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Worldpay Plugin is not available')
            );
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

    /**
     * Check refund availability
     *
     * @return bool
     */
    public function canRefund()
    {
        $payment = $this->getInfoInstance()->getOrder()->getPayment();
        $mageOrder = $payment->getOrder();
        //$quote = $this->quoteRepository->get($mageOrder->getQuoteId());
        $wpPayment = $this->worldpaypaymentmodel->loadByPaymentId($mageOrder->getIncrementId());
        if ($wpPayment) {
            return $this->_isRefundAllowed($wpPayment->getPaymentStatus());
        }

        return parent::canRefund();
    }

    /**
     * Is refund allowed?
     *
     * @param State $state
     * @return bool
     */
    private function _isRefundAllowed($state)
    {
        $allowed = in_array(
            $state,
            [
                \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_CAPTURED,
                \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SETTLED,
                \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SETTLED_BY_MERCHANT,
                \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SENT_FOR_REFUND,
                \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_REFUNDED,
                \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_REFUNDED_BY_MERCHANT,
                \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_REFUND_FAILED
            ]
        );
        return $allowed;
    }

    /**
     * Check paymentmethod is available for billing country
     *
     * @param Quote $quote
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
     * Check paymentmethod is available for shipping country
     *
     * No shipping country was mentioned in config it will be applicable for all shipping country
     *
     * @param Quote $quote
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
     * Payment method
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
                return str_replace(["_CREDIT","_DEBIT","_ELECTRON"], "", $savedCard[0]['method']);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Inavalid Card deatils. Please Refresh and check again')
                );
            }
        }
    }

    /**
     * Add transaction
     *
     * @param Payment $payment
     * @param float $amount
     */
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

    /**
     * Void the order abstract method
     *
     * @param array $order
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
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

    /**
     * Update status for void order abstract method
     *
     * @param array $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
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

    /**
     * Cancel the order abstract method
     *
     * @param array $order
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function canCancel($order)
    {
        $payment = $order->getPayment();
        $mageOrder = $order->getOrder();
        $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($mageOrder->getIncrementId());
        $orderStatus = $mageOrder->getStatus();
        $paymentStatus = $worldPayPayment->getPaymentStatus();
        if (strtoupper($orderStatus) !== 'CANCELED') {
            /** Start Multishipping Code */
            if ($this->worlpayhelper->isMultishippingOrder($mageOrder->getQuoteId())) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    $this->multishippingHelper->getConfigValue($order, 'ACAM14')
                ));
            }
            /** End Multishipping End */
            $xml = $this->paymentservicerequest->cancelOrder(
                $payment->getOrder(),
                $worldPayPayment,
                $payment->getMethod()
            );

            $payment->setTransactionId(time());
            $this->_response = $this->adminhtmlresponse->parseCancelOrderRespone($xml);
            if ($this->_response->reply->ok) {
                return $this;
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('Cancel operation was already executed on '
                   . 'this order. '
                   . 'Please check Payment Status or Order Status below for confirmation.'));
        }
    }

    /**
     * Update status for cancel order abstract method
     *
     * @param array $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateOrderStatusForCancelOrder($order)
    {
        $payment = $order->getPayment();
        $mageOrder = $order->getOrder();
        $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($mageOrder->getIncrementId());
        $paymentStatus = $worldPayPayment->getPaymentStatus();

        if ($paymentStatus === 'CANCELLED') {
            $mageOrder->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
            $mageOrder->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
            $mageOrder->save();
        }
    }

    /**
     * Get card type from credit card number
     *
     * @param string $ccnumber
     * @return string
     */
    public function getCardTypeFromCreditCardNumber($ccnumber)
    {
        $visaRegex = '/^4[0-9]{0,20}$/';
        $mastercardRegex =
                '/^(?:5[1-5][0-9]{0,2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{0,2}|27[01][0-9]|2720)[0-9]{0,12}$/';
        $amexRegex = '/^3$|^3[47][0-9]{0,13}$/';
        $discoverRegex = '/^6[05]$|^601[1]?$|^65[0-9][0-9]?$|^6(?:011|5[0-9]{2})[0-9]{0,12}$/';
        $jcbRegex = '/^35(2[89]|[3-8][0-9])/';
        $dinersRegex = '/^36/';
        $maestroRegex = '/^(5018|5020|5038|6304|679|6759|676[1-3])/';
        $dankortRegex = '/^(5019)/';
        if (preg_match($visaRegex, $ccnumber)) {
            return 'VISA-SSL';
        } elseif (preg_match($mastercardRegex, $ccnumber)) {
            return 'ECMC-SSL';
        } elseif (preg_match($amexRegex, $ccnumber)) {
            return 'AMEX-SSL';
        } elseif (preg_match($discoverRegex, $ccnumber)) {
            return 'DISCOVER-SSL';
        } elseif (preg_match($jcbRegex, $ccnumber)) {
            return 'JCB-SSL';
        } elseif (preg_match($dinersRegex, $ccnumber)) {
            return 'DINERS-SSL';
        } elseif (preg_match($maestroRegex, $ccnumber)) {
            return 'MAESTRO-SSL';
        } elseif (preg_match($dankortRegex, $ccnumber)) {
            return 'DANKORT-SSL';
        }
    }

    /**
     * Check apm partial capture
     *
     * @param string $paymenttype
     * @return bool
     */
    private function checkAPMforPartialCapture($paymenttype)
    {
        $activeAPMs = $this->worlpayhelper->getApmTypes(self::WORLDPAY_APM_TYPE);
        $typePresent = false;
        foreach ($activeAPMs as $key => $value) {
            if (stristr($key, strtoupper(strtok($paymenttype, '-'))) !== false) {
                $typePresent = true;
            }
        }
        return $typePresent;
    }

    /**
     * Get TokenId by token code
     *
     * @param string $tokenCode
     */
    public function getTokenIdByCode($tokenCode)
    {
        $merchantTokenEnabled = $this->worlpayhelper->getMerchantTokenization();
        $tokenType = $merchantTokenEnabled ? 'merchant' : 'shopper';
        $savedCard= $this->_savecard->create()->getCollection()
                ->addFieldToSelect(['method'])
                ->addFieldToFilter('token_code', ['eq' => $tokenCode])
                ->addFieldToFilter('token_type', ['eq' => $tokenType])
                ->getData();
        if ($savedCard) {
            return $savedCard[0]['id'];
        }
        return "";
    }
}
