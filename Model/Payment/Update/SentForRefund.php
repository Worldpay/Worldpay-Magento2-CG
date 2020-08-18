<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment\Update;

class SentForRefund extends \Sapient\Worldpay\Model\Payment\Update\Base implements
    \Sapient\Worldpay\Model\Payment\Update
{
    /** @var \Sapient\Worldpay\Helper\Data */
    private $_configHelper;
    const REFUND_COMMENT = 'Refund has been requested';
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

    public function apply($payment, $order = null)
    {
        $reference = $this->_paymentState->getJournalReference($this->_paymentState->getPaymentStatus());
        if ($reference) {
            $order->refund($reference, self::REFUND_COMMENT);
        } else {
            $amount = $this->_paymentState->getFullRefundAmount();
            $order->refundFull($amount, self::REFUND_COMMENT);
        }
        $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
    }
}
