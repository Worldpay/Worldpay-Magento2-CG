<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class VaultService extends \Magento\Framework\DataObject
{
    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingservice,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice
    ) {
        $this->mappingservice = $mappingservice;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->directResponse = $directResponse;
        $this->paymentservice = $paymentservice;
        $this->updateWorldPayPayment = $updateWorldPayPayment;
    }
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
        $this->updateWorldPayPayment->create()->updateWorldpayPayment($directResponse, $payment);
        $this->_applyPaymentUpdate($directResponse, $payment);
    }
    private function _applyPaymentUpdate(
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        $payment
    ) {
        $paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml($directResponse->getXml());
        $paymentUpdate->apply($payment);
        $this->_abortIfPaymentError($paymentUpdate, $directResponse);
    }
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
