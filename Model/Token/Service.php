<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Token;

use Sapient\Worldpay\Model\SavedToken;
/** 
 * Communicate with WP server and gives back meaningful answer object
 */
class Service 
{

    /**
     * @var Sapient\WorldPay\Model\Request\PaymentServiceRequest
     */
    protected $_paymentServiceRequest;

    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Model\Payment\Update\Factory $paymentupdatefactory
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Model\Worldpayment $worldpayPayment
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
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

    /**
     * Send token update request to WP server and gives back the answer
     *
     * @param Sapient\Worldpay\Model\Token $tokenModel
     * @param \Magento\Customer\Model\Customer $customer
     * @param $storeId
     * @return Sapient\Worldpay\Model\Token\UpdateXml
     */
    public function getTokenUpdate(
        SavedToken $tokenModel,
        \Magento\Customer\Model\Customer $customer,
        $storeId
    ) {
        $rawXml = $this->_paymentServiceRequest->tokenUpdate($tokenModel, $customer, $storeId);
        $xml = simplexml_load_string($rawXml);
        return new UpdateXml($xml);
    }

    /**
     * Send token delete request to WP server and gives back the answer
     *
     * @param Sapient\Worldpay\Model\Token $tokenModel
     * @param \Magento\Customer\Model\Customer $customer
     * @param $storeId
     * @return Sapient\Worldpay\Model\Token\DeleteXml
     */
    public function getTokenDelete(
        SavedToken $tokenModel,
        \Magento\Customer\Model\Customer $customer,
        $storeId
    ) {
        $rawXml = $this->_paymentServiceRequest->tokenDelete($tokenModel, $customer, $storeId);
        $xml = simplexml_load_string($rawXml);
        return new DeleteXml($xml);
    }
    
    /**
     * Send token inquiry request to WP server and gives back the answer
     *
     * @param Sapient\Worldpay\Model\Token $tokenModel
     * @param \Magento\Customer\Model\Customer $customer
     * @param $storeId
     * @return Sapient\Worldpay\Model\Token\InquiryXml
     */
    public function getTokenInquiry(
        SavedToken $tokenModel,
        \Magento\Customer\Model\Customer $customer,
        $storeId
    ) {
        $rawXml = $this->_paymentServiceRequest->tokenInquiry($tokenModel, $customer, $storeId);
        $xml = simplexml_load_string($rawXml);
        return new InquiryXml($xml);
    }
}
