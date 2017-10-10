<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Token;

use Sapient\Worldpay\Model\SavedToken;

class Service {

    protected $_paymentServiceRequest;

    public function __construct(
        \Sapient\Worldpay\Model\Payment\Update\Factory $paymentupdatefactory,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Model\Worldpayment $worldpayPayment,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) {
        $this->_wplogger = $wplogger;
        $this->paymentupdatefactory = $paymentupdatefactory;
        $this->_paymentServiceRequest = $paymentservicerequest;
        $this->worldpayPayment = $worldpayPayment;
    }

    public function getTokenUpdate(
        SavedToken $tokenModel,
        \Magento\Customer\Model\Customer $customer,
        $storeId
    ) {
        $rawXml = $this->_paymentServiceRequest->tokenUpdate($tokenModel, $customer, $storeId);
        $xml = simplexml_load_string($rawXml);
        return new UpdateXml($xml);
    }

    public function getTokenDelete(
        SavedToken $tokenModel,
        \Magento\Customer\Model\Customer $customer,
        $storeId
    ) {
        $rawXml = $this->_paymentServiceRequest->tokenDelete($tokenModel, $customer, $storeId);
        $xml = simplexml_load_string($rawXml);
        return new DeleteXml($xml);
    }
}
