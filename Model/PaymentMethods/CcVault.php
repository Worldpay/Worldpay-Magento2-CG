<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Model\PaymentMethods;

use Sapient\Worldpay\Logger\WorldpayLogger;
use Exception;
use Magento\Sales\Model\Order\Payment\Transaction;
use Sapient\Worldpay\Model\PaymentMethods\CreditCards;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\ConfigFactoryInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Block\Form;
use Magento\Vault\Model\VaultPaymentInterface;

class CcVault extends \Magento\Vault\Model\Method\Vault
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'worldpay_cc_vault';
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = true;
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = false;
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = true;
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

    public const DIRECT_MODEL = 'direct';
    /**
     * Ccvault Payment details
     *
     * @var array
     */
    protected static $paymentDetails;

    /**
     * @var WorldpayLogger
     */
    protected $logger;

     /**
      * @var \Sapient\Worldpay\Model\Authorisation\VaultService
      */
    protected $vaultService;

     /**
      * @var \Magento\Quote\Api\CartRepositoryInterface
      */
    protected $quoteRepository;

     /**
      * @var \Sapient\Worldpay\Helper\Data
      */
    protected $worlpayhelper;

     /**
      * @var \Sapient\Worldpay\Model\Worldpayment
      */
    protected $worldpaypaymentmodel;

     /**
      * @var \Sapient\Worldpay\Model\Worldpayment
      */
    protected $worldpaypayment;

     /**
      * @var \Sapient\Worldpay\Model\Utilities\PaymentMethods
      */
    protected $paymentutils;
     /**
      * @var \Sapient\Worldpay\Model\Request\PaymentServiceRequest
      */
    protected $paymentservicerequest;
     /**
      * @var \Sapient\Worldpay\Model\Response\AdminhtmlResponse
      */
    protected $adminhtmlresponse;
     /**
      * @var \Magento\Framework\Registry
      */
    protected $registry;
    
    /**
     * response
     *
     * @var string
     */
    protected $_response;

    /**
     * CcVault constructor
     *
     * @param ConfigInterface $config
     * @param ConfigFactoryInterface $configFactory
     * @param ObjectManagerInterface $objectManager
     * @param MethodInterface $vaultProvider
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param Command\CommandManagerPoolInterface $commandManagerPool
     * @param PaymentTokenManagementInterface $tokenManagement
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param string $code
     * @param WorldpayLogger $logger
     * @param \Sapient\Worldpay\Model\Authorisation\VaultService $vaultService
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment
     * @param \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel
     * @param \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentutils
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Model\Response\AdminhtmlResponse $adminhtmlresponse
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        ConfigInterface $config,
        ConfigFactoryInterface $configFactory,
        ObjectManagerInterface $objectManager,
        MethodInterface $vaultProvider,
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        Command\CommandManagerPoolInterface $commandManagerPool,
        PaymentTokenManagementInterface $tokenManagement,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        $code,
        WorldpayLogger $logger,
        \Sapient\Worldpay\Model\Authorisation\VaultService $vaultService,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment,
        \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel,
        \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentutils,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Model\Response\AdminhtmlResponse $adminhtmlresponse,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct(
            $config,
            $configFactory,
            $objectManager,
            $vaultProvider,
            $eventManager,
            $valueHandlerPool,
            $commandManagerPool,
            $tokenManagement,
            $paymentExtensionFactory,
            $code
        );
        $this->logger = $logger;
        $this->vaultService = $vaultService;
        $this->quoteRepository = $quoteRepository;
        $this->worlpayhelper = $worldpayhelper;
        $this->worldpaypayment = $worldpaypayment;
        $this->worldpaypaymentmodel = $worldpaypaymentmodel;
        $this->paymentutils = $paymentutils;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->adminhtmlresponse = $adminhtmlresponse;
        $this->registry = $registry;
    }

    /**
     * Initializer
     *
     * @param string $paymentAction
     * @param Object $stateObject
     */
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
     *  Generate order code for cc vault
     *
     * @param Quote $quote
     * @return string
     */
    private function _generateOrderCode($quote)
    {
        return $quote->getReservedOrderId() . '-' . time();
    }

    /**
     * Authorize payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->logger->info('Vault Authorize function executed');
        $payment->setAdditionalInformation('method', $payment->getMethod());
        self::$paymentDetails = $payment->getAdditionalInformation();
        $mageOrder = $payment->getOrder();
        $quote = $this->quoteRepository->get($mageOrder->getQuoteId());
        try {
            $orderCode = $this->_generateOrderCode($quote);
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
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error('Authorising payment failed.');
            $errormessage = $this->worlpayhelper->updateErrorMessage($e->getMessage(), $quote->getReservedOrderId());
            $this->logger->error($errormessage);
            throw new \Magento\Framework\Exception\LocalizedException(
                __($errormessage)
            );
        }
         return $this;
    }

    /**
     * Authorisation service abstract method
     *
     * @param int $storeId
     * @return bool
     */
    public function getAuthorisationService($storeId)
    {
        return $this->vaultService;
    }

    /**
     * Create worldpay payment
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
        $wpp = $this->worldpaypayment->create();
        $wpp->setData('order_id', $orderId);
        $wpp->setData('payment_status', \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SENT_FOR_AUTHORISATION);
        $wpp->setData('worldpay_order_id', $orderCode);
        $wpp->setData('store_id', $storeId);
        $wpp->setData('merchant_id', $this->worlpayhelper->getMerchantCode($paymentdetails['cc_type']));
        $wpp->setData('3d_verified', $this->worlpayhelper->isDynamic3DEnabled());
        $wpp->setData('payment_model', $integrationType);
        $wpp->setData('payment_type', $paymentdetails['cc_type']);
        $wpp->setData('interaction_type', $interactionType);
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
        $this->logger->info('Vault capture function executed');
        $mageOrder = $payment->getOrder();
        //$quote = $this->quoteRepository->get($mageOrder->getQuoteId());
        //$worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($quote->getReservedOrderId());
        $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($mageOrder->getIncrementId());
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
        $this->logger->info('Vault refund payment model function executed');
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
     * Get the cc title
     *
     * @return string
     */
    public function getTitle()
    {
        if ($order = $this->registry->registry('current_order')) {
            return $this->worlpayhelper->getPaymentTitleForOrders($order, $this->_code, $this->worldpaypayment);
        } elseif ($invoice = $this->registry->registry('current_invoice')) {
            $order = $this->worlpayhelper->getOrderByOrderId($invoice->getOrderId());
            return $this->worlpayhelper->getPaymentTitleForOrders($order, $this->_code, $this->worldpaypayment);
        } elseif ($creditMemo = $this->registry->registry('current_creditmemo')) {
            $order = $this->worlpayhelper->getOrderByOrderId($creditMemo->getOrderId());
            return $this->worlpayhelper->getPaymentTitleForOrders($order, $this->_code, $this->worldpaypayment);
        } else {
            return $this->worlpayhelper->getCcTitle();
        }
    }
}
