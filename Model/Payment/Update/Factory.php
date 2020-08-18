<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment\Update;

class Factory
{
    /** @var \Sapient\Worldpay\Helper\Data */
    private $_configHelper;
    /**
     * Constructor
     * @param \Sapient\Worldpay\Helper\Data $configHelper
     * @param \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment
     */
    public function __construct(
        \Sapient\Worldpay\Helper\Data $configHelper,
        \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldpaymentmodel
    ) {
            $this->_configHelper = $configHelper;
            $this->worldpaymentmodel = $worldpaymentmodel;
    }

    /**
     * @param \Sapient\Worldpay\Model\Payment\State $paymentState
     * @return object
     */
    public function create(\Sapient\Worldpay\Model\Payment\State $paymentState)
    {
        switch ($paymentState->getPaymentStatus()) {
            case \Sapient\Worldpay\Model\Payment\State::STATUS_AUTHORISED:
                return new \Sapient\Worldpay\Model\Payment\Update\Authorised(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\State::STATUS_CAPTURED:
                return new \Sapient\Worldpay\Model\Payment\Update\Captured(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\State::STATUS_SENT_FOR_REFUND:
                return new \Sapient\Worldpay\Model\Payment\Update\SentForRefund(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\State::STATUS_REFUNDED:
                return new \Sapient\Worldpay\Model\Payment\Update\Refunded(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\State::STATUS_REFUND_FAILED:
                return new \Sapient\Worldpay\Model\Payment\Update\RefundFailed(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );
            
            case \Sapient\Worldpay\Model\Payment\State::STATUS_REFUND_EXPIRED:
                return new \Sapient\Worldpay\Model\Payment\Update\RefundFailed(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\State::STATUS_CANCELLED:
                return new \Sapient\Worldpay\Model\Payment\Update\Cancelled(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\State::STATUS_REFUSED:
                return new \Sapient\Worldpay\Model\Payment\Update\Refused(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\State::STATUS_ERROR:
                return new \Sapient\Worldpay\Model\Payment\Update\Error(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\State::STATUS_PENDING_PAYMENT:
                return new \Sapient\Worldpay\Model\Payment\Update\PendingPayment(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            default:
                return new \Sapient\Worldpay\Model\Payment\Update\Defaultupdate(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );
        }
    }
}
