<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Phrase;

class DirectService extends \Magento\Framework\DataObject
{
    /**
     * @var checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var updateWorldPayPayment
     */
    protected $updateWorldPayPayment;
    
    /**
     * Declare variable
     *
     * @var \Magento\Framework\DataObject\Copy
     */
    private $objectCopyService;

    /**
     * __construct
     *
     * @param \Sapient\Worldpay\Model\Mapping\Service $mappingservice
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse
     * @param \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Helper\Registry $registryhelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     */
    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingservice,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        \Magento\Framework\DataObject\Copy $objectCopyService
    ) {
        $this->mappingservice = $mappingservice;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->wplogger = $wplogger;
        $this->directResponse = $directResponse;
        $this->paymentservice = $paymentservice;
        $this->checkoutSession = $checkoutSession;
        $this->updateWorldPayPayment = $updateWorldPayPayment;
        $this->worldpayHelper = $worldpayHelper;
        $this->registryhelper = $registryhelper;
        $this->urlBuilders    = $urlBuilder;
        $this->objectCopyService = $objectCopyService;
    }

    /**
     * AuthorizePayment
     *
     * @param int|string $mageOrder
     * @param int|string $quote
     * @param int|string $orderCode
     * @param int|string $orderStoreId
     * @param int|string $paymentDetails
     * @param int|string $payment
     * @return string
     */
    public function authorizePayment(
        $mageOrder,
        $quote,
        $orderCode,
        $orderStoreId,
        $paymentDetails,
        $payment
    ) {
        if ($paymentDetails['additional_data']['cc_type'] == 'ACH_DIRECT_DEBIT-SSL') {
            $directOrderParams = $this->mappingservice->collectACHOrderParameters(
                $orderCode,
                $quote,
                $orderStoreId,
                $paymentDetails
            );
            $response = $this->paymentservicerequest->achOrder($directOrderParams);
        } else {
            $directOrderParams = $this->mappingservice->collectDirectOrderParameters(
                $orderCode,
                $quote,
                $orderStoreId,
                $paymentDetails
            );
        
            $response = $this->paymentservicerequest->order($directOrderParams);
        }
        
        $directResponse = $this->directResponse->setResponse($response);
        $threeDSecureParams = $directResponse->get3dSecureParams();
        $threeDsEnabled = $this->worldpayHelper->is3DSecureEnabled();
        $threeDSecureChallengeParams = $directResponse->get3ds2Params();
        $threeDSecureConfig = [];
        $disclaimerFlag = '';
        
        if (!empty($directOrderParams['primeRoutingData'])) {
            $additionalInformation = $payment->getAdditionalInformation();
            $additionalInformation["worldpay_primerouting_enabled"]=true;
                $payment->setAdditionalInformation(
                    $additionalInformation
                );
        }
        
        if (isset($paymentDetails['additional_data']['disclaimerFlag'])) {
            $disclaimerFlag = $paymentDetails['additional_data']['disclaimerFlag'];
        }
        
        if ($threeDSecureParams) {
            // Handles success response with 3DS & redirect for varification.
            $this->checkoutSession->setauthenticatedOrderId($mageOrder->getIncrementId());
            $payment->setIsTransactionPending(1);
            $this->_handle3DSecure($threeDSecureParams, $directOrderParams, $orderCode);
        } elseif ($threeDSecureChallengeParams) {
            // Handles success response with 3DS2 & redirect for varification.
            $this->checkoutSession->setauthenticatedOrderId($mageOrder->getIncrementId());
            $payment->setIsTransactionPending(1);
            $threeDSecureConfig = $this->get3DS2ConfigValues();
            $this->_handle3Ds2($threeDSecureChallengeParams, $directOrderParams, $orderCode, $threeDSecureConfig);
        } else {
            // Normal order goes here.(without 3DS).
            $this->updateWorldPayPayment->create()->updateWorldpayPayment($directResponse, $payment, $disclaimerFlag);
            $this->_applyPaymentUpdate($directResponse, $payment);
        }
    }

    /**
     * _handle3DSecure
     *
     * @param int|string $threeDSecureParams
     * @param int|string $directOrderParams
     * @param int|string $mageOrderId
     * @return string
     */
    private function _handle3DSecure($threeDSecureParams, $directOrderParams, $mageOrderId)
    {
        $this->registryhelper->setworldpayRedirectUrl($threeDSecureParams);
        $this->checkoutSession->set3DSecureParams($threeDSecureParams);
        $this->checkoutSession->setDirectOrderParams($directOrderParams);
        $this->checkoutSession->setAuthOrderId($mageOrderId);
    }
    
    /**
     * _handle3Ds2
     *
     * @param int|string $threeDSecureChallengeParams
     * @param int|string $directOrderParams
     * @param int|string $mageOrderId
     * @param int|string $threeDSecureConfig
     * @return string
     */
    private function _handle3Ds2($threeDSecureChallengeParams, $directOrderParams, $mageOrderId, $threeDSecureConfig)
    {
        $this->registryhelper->setworldpayRedirectUrl($threeDSecureChallengeParams);
        $this->checkoutSession->set3Ds2Params($threeDSecureChallengeParams);
        $this->checkoutSession->setDirectOrderParams($directOrderParams);
        $this->checkoutSession->setAuthOrderId($mageOrderId);
        $this->checkoutSession->set3DS2Config($threeDSecureConfig);
    }

    /**
     * ApplyPaymentUpdate
     *
     * @param int|string $directResponse
     * @param int|string $payment
     * @return string
     */
    private function _applyPaymentUpdate(
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        $payment
    ) {
        $paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml($directResponse->getXml());
        $paymentUpdate->apply($payment);
        $this->_abortIfPaymentError($paymentUpdate, $directResponse);
    }

    /**
     * _abortIfPaymentError
     *
     * @param int|string $paymentUpdate
     * @param int|string $directResponse
     * @return string
     */
    private function _abortIfPaymentError($paymentUpdate, $directResponse)
    {
        $responseXml = $directResponse->getXml();
        $orderStatus = $responseXml->reply->orderStatus;
        $payment = $orderStatus->payment;
        $wpayCode = $payment->ISO8583ReturnCode['code'] ? $payment->ISO8583ReturnCode['code'] : 'Payment REFUSED';
        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Refused) {
            $msg =  new Phrase($wpayCode);
            throw new PaymentException($msg);
        }

        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Cancelled) {
            $msg =  new Phrase('Payment CANCELLED');
            throw new PaymentException($msg);
        }

        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Error) {
            $msg =  new Phrase('Payment ERROR');
            throw new PaymentException($msg);
        }
    }
    
    /**
     * Get 3ds2 params from the configuration and set to checkout session
     *
     * @return array
     */
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
}
