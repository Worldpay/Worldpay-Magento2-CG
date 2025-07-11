<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment\Update;

use \Sapient\Worldpay\Model\Payment\UpdateInterface;

class SentForRefund extends \Sapient\Worldpay\Model\Payment\Update\Base implements UpdateInterface
{
    /** @var \Sapient\Worldpay\Helper\Data */
    private $_configHelper;
    public const REFUND_COMMENT = 'Refund has been requested';
    /**
     * Constructor
     * @param \Sapient\Worldpay\Model\Payment\StateInterface $paymentState
     * @param \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment
     * @param \Sapient\Worldpay\Helper\Data $configHelper
     */
    public function __construct(
        \Sapient\Worldpay\Model\Payment\StateInterface $paymentState,
        \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment,
        \Sapient\Worldpay\Helper\Data $configHelper
    ) {
        $this->_paymentState = $paymentState;
        $this->_worldPayPayment = $worldPayPayment;
        $this->_configHelper = $configHelper;
    }

    /**
     * Apply
     *
     * @param Payment $payment
     * @param \Sapient\Worldpay\Model\Order $order
     */
    public function apply($payment, $order = null)
    {
        $reference = $this->_paymentState->getJournalReference($this->_paymentState->getPaymentStatus());

        if ($reference) {
            $refundAuth = $this->_paymentState->getRefundAuthorisationJournalReference($this->_paymentState->getPaymentStatus());
            if (!$refundAuth) {
                $order->refundOffline(
                    $reference,
                    __("The refund was processed offline; please contact the merchant for details.")
                );
            } else {
                $order->refund($reference, __("The refund has been processed successfully."));
            }
        } else {
            $amount = $this->_paymentState->getFullRefundAmount();
            $order->refundFull($amount, self::REFUND_COMMENT);
        }
        $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
    }
}
