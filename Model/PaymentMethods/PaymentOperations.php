<?php

namespace Sapient\Worldpay\Model\PaymentMethods;

class PaymentOperations extends \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod
{
    /**
     * Update status for void order abstract method
     *
     * @param array $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateOrderStatusForVoidSale($order)
    {
        if (!empty($order)) {
            $payment = $order->getPayment();
            $mageOrder = $order->getOrder();
            $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($mageOrder->getIncrementId());
        
            if (isset($worldPayPayment)) {
                $paymentStatus = $worldPayPayment->getPaymentStatus();
                if ($paymentStatus === 'VOIDED') {
                    $mageOrder->setState(\Magento\Sales\Model\Order::STATE_CLOSED, true);
                    $mageOrder->setStatus(\Magento\Sales\Model\Order::STATE_CLOSED);
                    $mageOrder->save();
                }
            } else {
                $this->_wplogger->info('No Payment');
                throw new \Magento\Framework\Exception\LocalizedException(__('No Payment'));
            }
        } else {
            $this->_wplogger->info('No Payment');
            throw new \Magento\Framework\Exception\LocalizedException(__('No Payment'));
        }
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
        if (!empty($order)) {
            $payment = $order->getPayment();
            $mageOrder = $order->getOrder();
            $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($mageOrder->getIncrementId());
            if (isset($worldPayPayment)) {
                $paymentStatus = $worldPayPayment->getPaymentStatus();
                if ($paymentStatus === 'CANCELLED') {
                    $mageOrder->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
                    $mageOrder->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
                    $mageOrder->save();
                }
            } else {
                $this->_wplogger->info('No Payment');
                throw new \Magento\Framework\Exception\LocalizedException(__('No Payment'));
            }
        } else {
            $this->_wplogger->info('No Payment');
            throw new \Magento\Framework\Exception\LocalizedException(__('No Payment'));
        }
    }
}
