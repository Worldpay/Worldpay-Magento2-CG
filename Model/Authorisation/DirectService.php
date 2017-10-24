<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class DirectService extends \Magento\Framework\DataObject
{
    protected $checkoutSession;
    protected $updateWorldPayPayment;

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

    public function authorizePayment(
        $mageOrder,
        $quote,
        $orderCode,
        $orderStoreId,
        $paymentDetails,
        $payment
    ) {
        $directOrderParams = $this->mappingservice->collectDirectOrderParameters(
            $orderCode,
            $quote,
            $orderStoreId,
            $paymentDetails
        );

        $response = $this->paymentservicerequest->order($directOrderParams);
        $directResponse = $this->directResponse->setResponse($response);
        $threeDSecureParams = $directResponse->get3dSecureParams();
        $threeDsEnabled = $this->worldpayHelper->is3DSecureEnabled();
        if ($threeDSecureParams) {
            // Handles success response with 3DS & redirect for varification.
            $this->checkoutSession->setauthenticatedOrderId($mageOrder->getIncrementId());
            $payment->setIsTransactionPending(1);
            $this->_handle3DSecure($threeDSecureParams, $directOrderParams, $orderCode);
        } else {
            if ($threeDsEnabled) {
                // Set flag to handles success response without 3DS & consider as normal order.
                $this->checkoutSession->setThreeDSEnabledWithError(true);
            }
            // Normal order goes here.(without 3DS).
            $this->updateWorldPayPayment->create()->updateWorldpayPayment($directResponse);
            $this->_applyPaymentUpdate($directResponse, $payment);
        }
    }
    private function _handle3DSecure($threeDSecureParams, $directOrderParams, $mageOrderId)
    {
        $this->registryhelper->setworldpayRedirectUrl($threeDSecureParams);
        $this->checkoutSession->set3DSecureParams($threeDSecureParams);
        $this->checkoutSession->setDirectOrderParams($directOrderParams);
        $this->checkoutSession->setAuthOrderId($mageOrderId);
    }

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
             throw new Exception(sprintf('Payment REFUSED'));
         }

        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Cancelled) {
            throw new Exception(sprintf('Payment CANCELLED'));
        }
    }

}
