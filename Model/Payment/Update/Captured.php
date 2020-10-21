<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment\Update;

class Captured extends \Sapient\Worldpay\Model\Payment\Update\Base implements \Sapient\Worldpay\Model\Payment\Update
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

    public function apply($payment, $order = null)
    {

        if (!empty($order)) {
            $this->_assertValidPaymentStatusTransition($order, $this->_getAllowedPaymentStatuses());
            $order->capture();
            $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
            $this->_worldPayPayment->updatePrimeroutingData($order->getPayment(), $this->_paymentState);
        }
    }

    /**
     * @return array
     */
    protected function _getAllowedPaymentStatuses()
    {
        return [
            \Sapient\Worldpay\Model\Payment\State::STATUS_SENT_FOR_AUTHORISATION,
            \Sapient\Worldpay\Model\Payment\State::STATUS_PENDING_PAYMENT,
            \Sapient\Worldpay\Model\Payment\State::STATUS_AUTHORISED
        ];
    }
}
