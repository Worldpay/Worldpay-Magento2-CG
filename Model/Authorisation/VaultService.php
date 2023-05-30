<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class VaultService extends \Magento\Framework\DataObject
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

      /**
       * @var \Sapient\Worldpay\Model\Mapping\Service
       */
    protected $mappingservice;
    /**
     * @var \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory
     */
    protected $updateWorldPayPayment;

      /**
       * @var \Sapient\Worldpay\Logger\WorldpayLogger
       */
    protected $wplogger;
     
    /**
     * @var \Sapient\Worldpay\Model\Response\DirectResponse
     */
    protected $directResponse;
    
     /**
      * @var \Sapient\Worldpay\Model\Payment\Service
      */
    protected $paymentservice;

     /**
      * @var \Sapient\Worldpay\Model\Request\PaymentServiceRequest
      */
    protected $paymentservicerequest;
     /**
      * @var \Sapient\Worldpay\Helper\Registry
      */
    protected $registryhelper;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayHelper;
    
    /**
     * @var \Sapient\Worldpay\Helper\Multishipping
     */
    protected $multishippingHelper;
    /**
     * VaultService constructor
     *
     * @param \Sapient\Worldpay\Model\Mapping\Service $mappingservice
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse
     * @param \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Registry $registryhelper
     */
    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingservice,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Registry $registryhelper
    ) {
        $this->mappingservice = $mappingservice;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->directResponse = $directResponse;
        $this->paymentservice = $paymentservice;
        $this->updateWorldPayPayment = $updateWorldPayPayment;
        $this->checkoutSession = $checkoutSession;
        $this->worldpayHelper = $worldpayHelper;
        $this->wplogger = $wplogger;
        $this->registryhelper = $registryhelper;
    }
    /**
     * Handles provides authorization data for vault
     *
     * It initiates a  XML request to WorldPay and registers worldpayRedirectUrl
     *
     * @param MageOrder $mageOrder
     * @param Quote $quote
     * @param string $orderCode
     * @param string $orderStoreId
     * @param array $paymentDetails
     * @param Payment $payment
     */
    public function authorizePayment(
        $mageOrder,
        $quote,
        $orderCode,
        $orderStoreId,
        $paymentDetails,
        $payment
    ) {
        $directOrderParams = $this->mappingservice->collectVaultOrderParameters(
            $orderCode,
            $quote,
            $orderStoreId,
            $paymentDetails
        );

        $response = $this->paymentservicerequest->order($directOrderParams);
        $directResponse = $this->directResponse->setResponse($response);
        if (!empty($directOrderParams['primeRoutingData'])) {
            $additionalInformation = $payment->getAdditionalInformation();
            $additionalInformation["worldpay_primerouting_enabled"]=true;
                $payment->setAdditionalInformation(
                    $additionalInformation
                );
        }
        $threeDSecureParams = $directResponse->get3dSecureParams();
        $threeDsEnabled = $this->worldpayHelper->is3dsEnabled();
        $threeDSecureChallengeParams = $directResponse->get3ds2Params();
        $threeDSecureConfig = [];
        if ($threeDSecureParams) {
            if (!$threeDsEnabled) {
                $this->wplogger->info("3Ds attempted but 3DS is not enabled for the store. Please contact merchant.");
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("3Ds attempted but 3DS is not enabled for the store. Please contact merchant.")
                );
            }
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
            $this->updateWorldPayPayment->create()->updateWorldpayPayment($directResponse, $payment);
            $this->_applyPaymentUpdate($directResponse, $payment);
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
    /**
     * Handles 3d secure for direct
     *
     * @param array $threeDSecureParams
     * @param array $directOrderParams
     * @param string $mageOrderId
     */
    private function _handle3DSecure($threeDSecureParams, $directOrderParams, $mageOrderId)
    {
        $this->wplogger->info('HANDLING 3DS IN VAULT');
        $this->registryhelper->setworldpayRedirectUrl($threeDSecureParams);
        $this->checkoutSession->set3DSecureParams($threeDSecureParams);
        $this->checkoutSession->setDirectOrderParams($directOrderParams);
        $this->checkoutSession->setAuthOrderId($mageOrderId);
        $this->checkoutSession->setInstantPurchaseOrder(true);
    }
    /**
     * Handles 3ds2 secure for direct
     *
     * @param array $threeDSecureChallengeParams
     * @param array $directOrderParams
     * @param string $mageOrderId
     * @param array $threeDSecureConfig
     */
    private function _handle3Ds2($threeDSecureChallengeParams, $directOrderParams, $mageOrderId, $threeDSecureConfig)
    {
        $this->wplogger->info('HANDLING 3DS2 IN VAULT');
        $this->registryhelper->setworldpayRedirectUrl($threeDSecureChallengeParams);
        $this->checkoutSession->set3Ds2Params($threeDSecureChallengeParams);
        $this->checkoutSession->setDirectOrderParams($directOrderParams);
        $this->checkoutSession->setAuthOrderId($mageOrderId);
        $this->checkoutSession->set3DS2Config($threeDSecureConfig);
    }
    /**
     * Apply payment update
     *
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse
     * @param Payment $payment
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
     * Abort if payment error
     *
     * @param Object $paymentUpdate
     * @param \SimpleXMLObject $directResponse
     */
    private function _abortIfPaymentError($paymentUpdate, $directResponse)
    {
        $responseXml = $directResponse->getXml();
        $orderStatus = $responseXml->reply->orderStatus;
        $payment = $orderStatus->payment;
        $wpayCode = $payment->ISO8583ReturnCode['code'] ? $payment->ISO8583ReturnCode['code'] : 'Payment REFUSED';
        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Refused) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($wpayCode)
            );
        }

        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Cancelled) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Payment CANCELLED')
            );
        }

        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Error) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Payment ERROR')
            );
        }
    }
}
