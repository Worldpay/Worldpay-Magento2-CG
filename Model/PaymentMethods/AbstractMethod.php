<?php
namespace Sapient\Worldpay\Model\PaymentMethods;
use Exception;

/**
 * WorldPay Abstract class extended from Magento Abstract Payment class.
 */
abstract class AbstractMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;

    protected $_wplogger;
    protected $directservice;
    protected static $paymentDetails;
    protected $worldpaypayment;
    protected $worlpayhelper;
    protected $paymentdetailsdata;

    const REDIRECT_MODEL = 'redirect';
    const RECURRING_MODEL = 'recurring';
    const DIRECT_MODEL = 'direct';

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
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Psr\Log\LoggerInterface $magelogger,
        \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment,
        \Sapient\Worldpay\Model\SavedTokenFactory $savecard,
        \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel,
        \Magento\Framework\Pricing\Helper\Data $pricinghelper,
        \Sapient\Worldpay\Model\Response\AdminhtmlResponse $adminhtmlresponse,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentutils,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
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
        $this->magelogger = $magelogger;
        $this->directservice = $directservice;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->redirectservice = $redirectservice;
        $this->tokenservice = $tokenservice;
        $this->hostedpaymentpageservice = $hostedpaymentpageservice;
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
        $this->motoredirectservice = $motoredirectservice;

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

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount){

        $mageOrder = $payment->getOrder();
        $quote = $this->quoteRepository->get($mageOrder->getQuoteId());
        $adminquote = $this->adminsessionquote->getQuote();

        if(empty($quote->getReservedOrderId()) && !empty($adminquote->getReservedOrderId())){
            $quote = $adminquote;
        }

        $orderCode = $this->_generateOrderCode($quote);
        $this->paymentdetailsdata = self::$paymentDetails;

        try {
            $this->_checkpaymentapplicable($quote);
            $this->_createWorldPayPayment($payment,$orderCode,$quote->getStoreId(),$quote->getReservedOrderId());
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
            $this->_wplogger->error($e->getMessage());
            $this->_wplogger->error('Authorising payment failed.');
            $errormessage = $this->worlpayhelper->updateErrorMessage($e->getMessage(),$quote->getReservedOrderId());
            $this->_wplogger->error($errormessage);
            throw new \Magento\Framework\Exception\LocalizedException(
                __($errormessage)
            );
        }

    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        self::$paymentDetails = $data->getData();
        return $this;
    }

    private function _generateOrderCode($quote)
    {
        return $quote->getReservedOrderId() . '-' . time();
    }

    private function _createWorldPayPayment(\Magento\Payment\Model\InfoInterface $payment, $orderCode, $storeId,$orderId,$interactionType='ECOM')
    {
        $paymentdetails = self::$paymentDetails;
        $wpp = $this->worldpaypayment->create();
        $wpp->setData('order_id',$orderId);
        $wpp->setData('payment_status',\Sapient\Worldpay\Model\Payment\State::STATUS_SENT_FOR_AUTHORISATION);
        $wpp->setData('worldpay_order_id',$orderCode);
        $wpp->setData('store_id',$storeId);
        $wpp->setData('merchant_id',$this->worlpayhelper->getMerchantCode($paymentdetails['additional_data']['cc_type']));
        $wpp->setData('3d_verified',$this->worlpayhelper->isDynamic3DEnabled());
        $wpp->setData('payment_model',$this->worlpayhelper->getIntegrationModelByPaymentMethodCode($payment->getMethod(),$storeId));
        if($paymentdetails && !empty($paymentdetails['additional_data']['cc_type']) && empty($paymentdetails['additional_data']['tokenCode'])){
            $wpp->setData('payment_type',$paymentdetails['additional_data']['cc_type']);
        } else {
            $wpp->setData('payment_type',$this->_getpaymentType());
        }
        if($paymentdetails['method'] == 'worldpay_moto'){
            $interactionType='MOTO';
        }
        $wpp->setData('interaction_type',$interactionType);
        $wpp->save();
    }


    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $this->_wplogger->info('capture function executed');
        $mageOrder = $payment->getOrder();
        $quote = $this->quoteRepository->get($mageOrder->getQuoteId());
        $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($quote->getReservedOrderId());
        $paymenttype = $worldPayPayment->getPaymentType();
        if($this->paymentutils->CheckCaptureRequest($payment->getMethod(), $paymenttype)){
            $this->paymentservicerequest->capture(
                $payment->getOrder(),
                $worldPayPayment,
                $payment->getMethod()
            );
        }
        $payment->setTransactionId(time());
        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
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

        throw new \Magento\Framework\Exception\LocalizedException(
            __('No matching order found in WorldPay to refund. Please visit your WorldPay merchant interface and refund the order manually.')
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

    private function _isRefundAllowed($state)
    {
        $allowed = in_array(
            $state,
            array(
                \Sapient\Worldpay\Model\Payment\State::STATUS_CAPTURED,
                \Sapient\Worldpay\Model\Payment\State::STATUS_SETTLED,
                \Sapient\Worldpay\Model\Payment\State::STATUS_SETTLED_BY_MERCHANT,
                \Sapient\Worldpay\Model\Payment\State::STATUS_SENT_FOR_REFUND,
                \Sapient\Worldpay\Model\Payment\State::STATUS_REFUNDED,
                \Sapient\Worldpay\Model\Payment\State::STATUS_REFUNDED_BY_MERCHANT,
                \Sapient\Worldpay\Model\Payment\State::STATUS_REFUND_FAILED
            )
        );
        return $allowed;
    }

    protected function _checkpaymentapplicable($quote){
        $type = $this->_getpaymentType();
        $paymentmethod = $quote->getPayment()->getMethod();
        $applicabletypes = $this->paymentutils->loadEnabledByType($paymentmethod);
        if($paymentmethod == 'worldpay_apm' || $paymentmethod == 'worldpay_cc'){
            if (array_key_exists($type, $applicabletypes)) {
                return true;
            }else{
                throw new Exception('Payment Type not valid for the billing country');
            }
        }
    }

    protected function _getpaymentType(){
        if(empty($this->paymentdetailsdata['additional_data']['tokenCode'])){
            return  $this->paymentdetailsdata['additional_data']['cc_type'];
        }
        else{

            $savedCard= $this->_savecard->create()->getCollection()
                ->addFieldToSelect(array('method'))
                ->addFieldToFilter('token_code', array('eq' => $this->paymentdetailsdata['additional_data']['tokenCode']))
                ->getData();

            return $savedCard[0]['method'];

        }

    }



}
