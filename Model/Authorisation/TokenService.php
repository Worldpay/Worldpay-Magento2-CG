<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class TokenService extends \Magento\Framework\DataObject
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_session;
    /**
     * @var \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory
     */
    protected $updateWorldPayPayment;

    /**
     * TokenService constructor
     *
     * @param \Sapient\Worldpay\Model\Mapping\Service $mappingservice
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse
     * @param \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     * @param \Sapient\Worldpay\Helper\Registry $registryhelper
     * @param \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
     */
    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingservice,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
    ) {
        $this->mappingservice = $mappingservice;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->wplogger = $wplogger;
        $this->directResponse = $directResponse;
        $this->paymentservice = $paymentservice;
        $this->updateWorldPayPayment = $updateWorldPayPayment;
        $this->checkoutSession = $checkoutSession;
        $this->worldpayHelper = $worldpayHelper;
        $this->registryhelper = $registryhelper;
        $this->multishippingHelper = $multishippingHelper;
    }

    /**
     * Handles provides authorization data
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
        /** Start Multishipping Code */
        if ($this->worldpayHelper->isMultiShipping($quote)) {
            $sessionOrderCode = $this->multishippingHelper->getOrderCodeFromSession();
            if (!empty($sessionOrderCode)) {
				$this->checkoutSession->setauthenticatedOrderId($mageOrder->getIncrementId());
                $orgWorldpayPayment = $this->multishippingHelper->getOrgWorldpayId($sessionOrderCode);
                $orgOrderId = $orgWorldpayPayment['order_id'];
                $isOrg = false;
                $this->multishippingHelper->_createWorldpayMultishipping($mageOrder, $sessionOrderCode, $isOrg);
                $this->multishippingHelper->_copyWorldPayPayment($orgOrderId, $orderCode);
                $is3dsOrder = $this->multishippingHelper->is3dsOrder();
                if ($is3dsOrder) {
                    $payment->setIsTransactionPending(1);
                } else {
                    $payment->setTransactionId(time());
                    $payment->setIsTransactionClosed(0);
                }
                return;
            } else {
                $isOrg = true;
                $this->multishippingHelper->_createWorldpayMultishipping($mageOrder, $orderCode, $isOrg);
            }
        }
        /** End Multishipping Code */
        $tokenOrderParams = $this->mappingservice->collectTokenOrderParameters(
            $orderCode,
            $quote,
            $orderStoreId,
            $paymentDetails
        );

        $response = $this->paymentservicerequest->orderToken($tokenOrderParams);
        $directResponse = $this->directResponse->setResponse($response);
        $threeDSecureParams = $directResponse->get3dSecureParams();
        $threeDsEnabled = $this->worldpayHelper->is3DSecureEnabled();
        $threeDSecureChallengeParams = $directResponse->get3ds2Params();
        $isRecurringOrder = 0;
        if (isset($paymentDetails['additional_data']['isRecurringOrder'])) {
            $isRecurringOrder =  $paymentDetails['additional_data']['isRecurringOrder'];
        }
        if (!empty($tokenOrderParams['primeRoutingData'])) {
            $additionalInformation = $payment->getAdditionalInformation();
            $additionalInformation["worldpay_primerouting_enabled"]=true;
               $payment->setAdditionalInformation(
                   $additionalInformation
               );
        }
        $payment->setIsRecurringOrder($isRecurringOrder);
        $threeDSecureConfig = [];
        if ($threeDSecureParams) {
            // Handles success response with 3DS & redirect for varification.
            $this->checkoutSession->setauthenticatedOrderId($mageOrder->getIncrementId());
            $payment->setIsTransactionPending(1);
            $this->_handle3DSecure($threeDSecureParams, $tokenOrderParams, $orderCode);
        } elseif ($threeDSecureChallengeParams) {
            // Handles success response with 3DS2 & redirect for varification.
            $this->checkoutSession->setauthenticatedOrderId($mageOrder->getIncrementId());
            $payment->setIsTransactionPending(1);
            $threeDSecureConfig = $this->get3DS2ConfigValues();
            $this->_handle3Ds2($threeDSecureChallengeParams, $tokenOrderParams, $orderCode, $threeDSecureConfig);
        } else {
            
            // Normal order goes here.(without 3DS).
            $tokenId = isset($tokenOrderParams['id'])? $tokenOrderParams['id'] : '';
            $this->updateWorldPayPayment->create()->updateWorldpayPayment($directResponse, $payment, $tokenId);
            $this->_applyPaymentUpdate($directResponse, $payment);
        }
        $quote->setActive(false);
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
        $this->wplogger->info('HANDLING 3DS');
        $this->registryhelper->setworldpayRedirectUrl($threeDSecureParams);
        $this->checkoutSession->set3DSecureParams($threeDSecureParams);
        $this->checkoutSession->setDirectOrderParams($directOrderParams);
        $this->checkoutSession->setAuthOrderId($mageOrderId);
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
        $this->wplogger->info('HANDLING 3DS2');
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
