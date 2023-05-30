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
     * @var \Sapient\Worldpay\Model\Payment\WorldPayPayment
     */
    private $worldpaymentmodel;

    /**
     * @var \Sapient\Worldpay\Helper\Multishipping
     */
    private $_multishippingHelper;

    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Helper\Data $configHelper
     * @param \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldpaymentmodel
     * @param \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
     */
    public function __construct(
        \Sapient\Worldpay\Helper\Data $configHelper,
        \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldpaymentmodel,
        \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
    ) {
            $this->_configHelper = $configHelper;
            $this->worldpaymentmodel = $worldpaymentmodel;
            $this->_multishippingHelper = $multishippingHelper;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param \Sapient\Worldpay\Model\Payment\StateInterface $paymentState
     * @return object
     */
    public function create(\Sapient\Worldpay\Model\Payment\StateInterface $paymentState)
    {
        switch ($paymentState->getPaymentStatus()) {
            case \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_AUTHORISED:
                return new \Sapient\Worldpay\Model\Payment\Update\Authorised(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper,
                    $this->_multishippingHelper
                );

            case \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_CAPTURED:
                return new \Sapient\Worldpay\Model\Payment\Update\Captured(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SENT_FOR_REFUND:
                return new \Sapient\Worldpay\Model\Payment\Update\SentForRefund(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_REFUNDED:
                return new \Sapient\Worldpay\Model\Payment\Update\Refunded(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_REFUND_FAILED:
                return new \Sapient\Worldpay\Model\Payment\Update\RefundFailed(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );
            
            case \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_REFUND_EXPIRED:
                return new \Sapient\Worldpay\Model\Payment\Update\RefundFailed(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_CANCELLED:
                return new \Sapient\Worldpay\Model\Payment\Update\Cancelled(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper
                );

            case \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_REFUSED:
                return new \Sapient\Worldpay\Model\Payment\Update\Refused(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper,
                    $this->_multishippingHelper
                );

            case \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_ERROR:
                return new \Sapient\Worldpay\Model\Payment\Update\Error(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper,
                    $this->_multishippingHelper
                );

            case \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_PENDING_PAYMENT:
                return new \Sapient\Worldpay\Model\Payment\Update\PendingPayment(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper,
                    $this->_multishippingHelper
                );

            default:
                return new \Sapient\Worldpay\Model\Payment\Update\Defaultupdate(
                    $paymentState,
                    $this->worldpaymentmodel,
                    $this->_configHelper,
                    $this->_multishippingHelper
                );
        }
    }
}
