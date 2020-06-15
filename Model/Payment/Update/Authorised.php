<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment\Update;

class Authorised
    extends \Sapient\Worldpay\Model\Payment\Update\Base
    implements \Sapient\Worldpay\Model\Payment\Update
{
    /** @var \Sapient\Worldpay\Helper\Data */
    private $_configHelper;

    /**
     * Constructor
     * @param \Sapient\Worldpay\Model\Payment\State $paymentState
     * @param \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment
     * @param \Sapient\Worldpay\Helper\Data $configHelper
     */
    public function __construct(
        \Sapient\Worldpay\Model\Payment\State $paymentState,
        \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment,
        \Sapient\Worldpay\Helper\Data $configHelper
    ) {
        $this->_paymentState = $paymentState;
        $this->_worldPayPayment = $worldPayPayment;
        $this->_configHelper = $configHelper;
    }

    /**
     * @param $payment
     * @param $order
     */
    public function apply($payment,$order = null)
    {
        if (empty($order)) {
            $this->_applyUpdate($payment);
        } else {
            $this->_assertValidPaymentStatusTransition($order, $this->_getAllowedPaymentStatuses($order));
            $this->_applyUpdate($order->getPayment(),$order);
            $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
            $this->_captureOrderIfAutoCaptureEnabled($order);
        }
    }

    private function _applyUpdate($payment,$order = null)
    {
        $payment->setTransactionId(time());
        $payment->setIsTransactionClosed(0);
        if (!empty($order) && ($order->getPaymentStatus() == \Sapient\Worldpay\Model\Payment\State::STATUS_SENT_FOR_AUTHORISATION)) {
            $currencycode = $this->_paymentState->getCurrency();
            $currencysymbol = $this->_configHelper->getCurrencySymbol($currencycode);
            $amount = $this->_amountAsInt($this->_paymentState->getAmount());
            $magentoorder = $order->getOrder();
            $magentoorder->addStatusToHistory($magentoorder->getStatus(), 'Authorized amount of '.$currencysymbol.''.$amount);
            $transaction = $payment->addTransaction('authorization', null, false, null);
            $transaction->save();
            $magentoorder->save();
        }
    }

    /**
     * @param \Sapient\Worldpay\Model\Order $order
     * @return array
     */
    private function _getAllowedPaymentStatuses(\Sapient\Worldpay\Model\Order $order)
    {
        if ($this->_isDirectIntegrationMode($order)) {
             return array(
                \Sapient\Worldpay\Model\Payment\State::STATUS_SENT_FOR_AUTHORISATION,
                \Sapient\Worldpay\Model\Payment\State::STATUS_AUTHORISED
            );
        }
        if ($this->_isWalletIntegrationMode($order)) {
             return array(
                \Sapient\Worldpay\Model\Payment\State::STATUS_SENT_FOR_AUTHORISATION,
                \Sapient\Worldpay\Model\Payment\State::STATUS_AUTHORISED
            );
        }
        
        return array(\Sapient\Worldpay\Model\Payment\State::STATUS_SENT_FOR_AUTHORISATION);
    }

    /**
     * Capture only if auto-capture enabled AND current XML response is align with the integration mode
     * Do not capture if integration mode is "direct" and an async notification comes in
     * as it could end up double capture
     *
     * @param \Sapient\Worldpay\Model\Order $order
     */
    private function _captureOrderIfAutoCaptureEnabled(\Sapient\Worldpay\Model\Order $order)
    {
        // Capture only if auto-capture enabled
        if ($this->_configHelper->isAutoCaptureEnabled($order->getStoreId()) && !$this->_configHelper->CheckStopAutoInvoice($order->getPaymentMethodCode(), $order->getPaymentType())) {
               if (($this->_paymentState->isAsyncNotification() && $this->_isRedirectIntegrationMode($order))
                || ($this->_paymentState->isAsyncNotification() && $this->_isDirectIntegrationMode($order))
            ){
                $order->capture();
            }else{
                return;
            }
        }else{
            return;
        }
    }

    /**
     * check if integration mode is direct
     * @return bool
     */
    private function _isDirectIntegrationMode(\Sapient\Worldpay\Model\Order $order)
    {
        return $this->_configHelper->getIntegrationModelByPaymentMethodCode($order->getPaymentMethodCode(), $order->getStoreId())
            === \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod::DIRECT_MODEL;
    }
    
    
    /**
     * check if integration mode is wallet
     * @return bool
     */
    private function _isWalletIntegrationMode(\Sapient\Worldpay\Model\Order $order)
    {
        return $this->_configHelper->getIntegrationModelByPaymentMethodCode($order->getPaymentMethodCode(), $order->getStoreId())
            === \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod::WORLDPAY_WALLETS_TYPE;
    }

    /**
     * check if integration mode is redirect
     * @return bool
     */
    private function _isRedirectIntegrationMode(\Sapient\Worldpay\Model\Order $order)
    {
        return $this->_configHelper->getIntegrationModelByPaymentMethodCode($order->getPaymentMethodCode(), $order->getStoreId())
            === \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod::REDIRECT_MODEL;
    }



}
