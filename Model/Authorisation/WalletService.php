<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class WalletService extends \Magento\Framework\DataObject
{

    protected $checkoutSession;
    protected $updateWorldPayPayment;
    
    /**
     * Constructor
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
        \Sapient\Worldpay\Helper\Data $worldpayHelper
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
    }
    
    /**
     * handles provides authorization data for redirect
     * It initiates a  XML request to WorldPay and registers worldpayRedirectUrl
     */
    public function authorizePayment(
        $mageOrder,
        $quote,
        $orderCode,
        $orderStoreId,
        $paymentDetails,
        $payment
    ) {
        if ($paymentDetails['additional_data']['cc_type'] == 'PAYWITHGOOGLE-SSL') {
            $walletOrderParams = $this->mappingservice->collectWalletOrderParameters(
                $orderCode,
                $quote,
                $orderStoreId,
                $paymentDetails
            );

            $response = $this->paymentservicerequest->walletsOrder($walletOrderParams);
            $directResponse = $this->directResponse->setResponse($response);
            $threeDSecureParams = $directResponse->get3dSecureParams();
            $threeDsEnabled = $this->worldpayHelper->is3DSecureEnabled();
            $threeDSecureChallengeParams = $directResponse->get3ds2Params();
            if ($threeDSecureParams) {
            // Handles success response with 3DS & redirect for varification.
                $this->checkoutSession->setauthenticatedOrderId($mageOrder->getIncrementId());
                $payment->setIsTransactionPending(1);
                $this->_handle3DSecure($threeDSecureParams, $walletOrderParams, $orderCode);
            } elseif ($threeDSecureChallengeParams) {
                // Handles success response with 3DS2 & redirect for varification.
                $this->checkoutSession->setauthenticatedOrderId($mageOrder->getIncrementId());
                $payment->setIsTransactionPending(1);
                $threeDSecureConfig = $this->get3DS2ConfigValues();
                $this->_handle3Ds2($threeDSecureChallengeParams, $walletOrderParams, $orderCode, $threeDSecureConfig);
            } else {
                if ($paymentDetails['additional_data']['cc_type'] == 'PAYWITHGOOGLE-SSL') {
                    $responseXml=$directResponse->getXml();
                    $orderStatus = $responseXml->reply->orderStatus;
                    $paymentxml=$orderStatus->payment;
                    $paymentxml->paymentMethod[0] = 'PAYWITHGOOGLE-SSL';
                }
                $this->updateWorldPayPayment->create()->updateWorldpayPayment($directResponse, $payment);
                $this->_applyPaymentUpdate($directResponse, $payment);
            }
        }
        
        if ($paymentDetails['additional_data']['cc_type'] == 'APPLEPAY-SSL') {
            $applePayOrderParams = $this->mappingservice->collectWalletOrderParameters(
                $orderCode,
                $quote,
                $orderStoreId,
                $paymentDetails
            );

            $response = $this->paymentservicerequest->applePayOrder($applePayOrderParams);
            $directResponse = $this->directResponse->setResponse($response);
            $this->updateWorldPayPayment->create()->updateWorldpayPayment($directResponse, $payment);
            $this->_applyPaymentUpdate($directResponse, $payment);
        }
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
    
    /**
     * Method to apply payment update
     *
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    private function _applyPaymentUpdate(
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        $payment
    ) {
        $paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml($directResponse->getXml());
        $paymentUpdate->apply($payment);
        $this->_abortIfPaymentError($paymentUpdate);
    }

    private function _abortIfPaymentError($paymentUpdate)
    {
        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Refused) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(sprintf('Payment REFUSED'))
            );
        }

        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Cancelled) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(sprintf('Payment CANCELLED'))
            );
        }

        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Error) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(sprintf('Payment ERROR'))
            );
        }
    }
}
