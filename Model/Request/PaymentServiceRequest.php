<?php
namespace Sapient\Worldpay\Model\Request;
use Exception;
use Sapient\Worldpay\Model\SavedToken;
class PaymentServiceRequest  extends \Magento\Framework\DataObject {

    protected $_request;
    protected $_xml;
    protected $_logger;
    protected $_model;

    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Psr\Log\LoggerInterface $logger,
        \Sapient\Worldpay\Model\Request $request,
        \Sapient\Worldpay\Helper\Data $worldpayhelper
    ) {
        $this->_wplogger = $wplogger;
        $this->logger = $logger;
        $this->_request = $request;
        $this->worldpayhelper = $worldpayhelper;
    }

    public function order3DSecure($directOrderParams)
    {
        $this->_wplogger->info('########## Submitting direct 3DSecure order request. OrderCode: ' . $directOrderParams['orderCode'] . ' ##########');
        $requestConfiguration = array(
            'threeDSecureConfig' => $directOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $directOrderParams['tokenRequestConfig']
        );

        $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectOrder($requestConfiguration);
        $paymentType = isset($directOrderParams['paymentDetails']['brand']) ? $directOrderParams['paymentDetails']['brand']: $directOrderParams['paymentDetails']['paymentType'];
        $orderSimpleXml = $this->xmldirectorder->build3DSecure(
            $directOrderParams['merchantCode'],
            $directOrderParams['orderCode'],
            $directOrderParams['orderDescription'],
            $directOrderParams['currencyCode'],
            $directOrderParams['amount'],
            $directOrderParams['paymentDetails'],
            $directOrderParams['cardAddress'],
            $directOrderParams['shopperEmail'],
            $directOrderParams['acceptHeader'],
            $directOrderParams['userAgentHeader'],
            $directOrderParams['shippingAddress'],
            $directOrderParams['billingAddress'],
            $directOrderParams['shopperId'],
            $directOrderParams['paResponse'],
            $directOrderParams['echoData']
        );

        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($paymentType),
            $this->worldpayhelper->getXmlPassword($paymentType)
        );
    }

    public function order($directOrderParams)
    {
        $this->_wplogger->info('########## Submitting direct order request. OrderCode: ' . $directOrderParams['orderCode'] . ' ##########');
        $requestConfiguration = array(
            'threeDSecureConfig' => $directOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $directOrderParams['tokenRequestConfig']
        );
        $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectOrder($requestConfiguration);
        $orderSimpleXml = $this->xmldirectorder->build(
            $directOrderParams['merchantCode'],
            $directOrderParams['orderCode'],
            $directOrderParams['orderDescription'],
            $directOrderParams['currencyCode'],
            $directOrderParams['amount'],
            $directOrderParams['paymentDetails'],
            $directOrderParams['cardAddress'],
            $directOrderParams['shopperEmail'],
            $directOrderParams['acceptHeader'],
            $directOrderParams['userAgentHeader'],
            $directOrderParams['shippingAddress'],
            $directOrderParams['billingAddress'],
            $directOrderParams['shopperId']
        );
        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($directOrderParams['paymentDetails']['paymentType']),
            $this->worldpayhelper->getXmlPassword($directOrderParams['paymentDetails']['paymentType'])
        );
    }

    public function orderToken($tokenOrderParams)
    {
        $this->_wplogger->info('########## Submitting direct token order request. OrderCode: ' . $tokenOrderParams['orderCode'] . ' ##########');

        $requestConfiguration = array(
            'threeDSecureConfig' => $tokenOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $tokenOrderParams['tokenRequestConfig']
        );
        $this->xmltokenorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectOrder($requestConfiguration);
        $orderSimpleXml = $this->xmltokenorder->build(
            $tokenOrderParams['merchantCode'],
            $tokenOrderParams['orderCode'],
            $tokenOrderParams['orderDescription'],
            $tokenOrderParams['currencyCode'],
            $tokenOrderParams['amount'],
            $tokenOrderParams['paymentDetails'],
            $tokenOrderParams['cardAddress'],
            $tokenOrderParams['shopperEmail'],
            $tokenOrderParams['acceptHeader'],
            $tokenOrderParams['userAgentHeader'],
            $tokenOrderParams['shippingAddress'],
            $tokenOrderParams['billingAddress'],
            $tokenOrderParams['shopperId']
        );
        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($tokenOrderParams['paymentDetails']['brand']),
            $this->worldpayhelper->getXmlPassword($tokenOrderParams['paymentDetails']['brand'])
        );
    }

    public function redirectOrder($redirectOrderParams)
    {
        $this->_wplogger->info('########## Submitting redirect order request. OrderCode: ' . $redirectOrderParams['orderCode'] . ' ##########');

        $requestConfiguration = array(
            'threeDSecureConfig' => $redirectOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $redirectOrderParams['tokenRequestConfig'],
            'shopperId' => $redirectOrderParams['shopperId']
        );
        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\RedirectOrder($requestConfiguration);
        $redirectSimpleXml = $this->xmlredirectorder->build(
            $redirectOrderParams['merchantCode'],
            $redirectOrderParams['orderCode'],
            $redirectOrderParams['orderDescription'],
            $redirectOrderParams['currencyCode'],
            $redirectOrderParams['amount'],
            $redirectOrderParams['paymentType'],
            $redirectOrderParams['shopperEmail'],
            $redirectOrderParams['acceptHeader'],
            $redirectOrderParams['userAgentHeader'],
            $redirectOrderParams['shippingAddress'],
            $redirectOrderParams['billingAddress'],
            $redirectOrderParams['paymentPagesEnabled'],
            $redirectOrderParams['installationId'],
            $redirectOrderParams['hideAddress']
        );

        return $this->_sendRequest(
            dom_import_simplexml($redirectSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($redirectOrderParams['paymentType']),
            $this->worldpayhelper->getXmlPassword($redirectOrderParams['paymentType'])
        );
    }

    public function capture(\Magento\Sales\Model\Order $order, $wp, $paymentMethodCode)
    {
        $orderCode = $wp->getWorldpayOrderId();
        $this->_wplogger->info('########## Submitting capture request. Order: ' . $orderCode . ' Amount:' . $order->getGrandTotal() . ' ##########');
        $this->xmlcapture = new \Sapient\Worldpay\Model\XmlBuilder\Capture();
        $captureSimpleXml = $this->xmlcapture->build(
            $this->worldpayhelper->getMerchantCode($wp->getPaymentType()),
            $orderCode,
            $order->getOrderCurrencyCode(),
            $order->getGrandTotal()
        );

        return $this->_sendRequest(
            dom_import_simplexml($captureSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($wp->getPaymentType()),
            $this->worldpayhelper->getXmlPassword($wp->getPaymentType())
        );
    }



    protected function _sendRequest($xml, $username, $password)
    {
        $response = $this->_request->sendRequest($xml, $username, $password);

        $this->_checkForError($response);
        return $response;
    }

    protected function _checkForError($response)
    {
        $paymentService = new \SimpleXmlElement($response);
        $error = $paymentService->xpath('//error');

        if ($error) {
            $this->_wplogger->error('An error occurred while sending the request');
            $this->_wplogger->error('Error (code ' . $error[0]['code'] . '): ' . $error[0]);
            throw new Exception($error[0]);
        }
    }

    public function refund(
        \Magento\Sales\Model\Order $order,
        $wp,
        $paymentMethodCode,
        $amount,
        $reference
    )
    {
        $orderCode = $wp->getWorldpayOrderId();
        $this->_wplogger->info('########## Submitting refund request. OrderCode: ' . $orderCode . ' ##########');
        $this->xmlrefund = new \Sapient\Worldpay\Model\XmlBuilder\Refund();
        $refundSimpleXml = $this->xmlrefund->build(
            $this->worldpayhelper->getMerchantCode($wp->getPaymentType()),
            $orderCode,
            $order->getOrderCurrencyCode(),
            $amount,
            $reference
        );

        return $this->_sendRequest(
            dom_import_simplexml($refundSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($wp->getPaymentType()),
            $this->worldpayhelper->getXmlPassword($wp->getPaymentType())
        );
    }

    public function inquiry($merchantCode, $orderCode, $storeId, $paymentMethodCode, $paymenttype)
    {
        $this->_wplogger->info('########## Submitting order inquiry. OrderCode: (' . $orderCode . ') ##########');
        $this->xmlinquiry = new \Sapient\Worldpay\Model\XmlBuilder\Inquiry();
        $inquirySimpleXml = $this->xmlinquiry->build(
            $merchantCode,
            $orderCode
        );

        return $this->_sendRequest(
            dom_import_simplexml($inquirySimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($paymenttype),
            $this->worldpayhelper->getXmlPassword($paymenttype)
        );
    }
    public function tokenUpdate(
        SavedToken $tokenModel,
        \Magento\Customer\Model\Customer $customer,
        $storeId
    ) {
        $this->_wplogger->info('########## Submitting token update. TokenId: ' . $tokenModel->getId() . ' ##########');
        $requestParameters = array(
            'tokenModel'   => $tokenModel,
            'customer'     => $customer,
            'merchantCode' => $this->worldpayhelper->getMerchantCode($tokenModel->getMethod()),
        );
        /** @var SimpleXMLElement $simpleXml */
        $this->tokenUpdateXml = new \Sapient\Worldpay\Model\XmlBuilder\TokenUpdate($requestParameters);
        $tokenUpdateSimpleXml = $this->tokenUpdateXml->build();

        return $this->_sendRequest(
            dom_import_simplexml($tokenUpdateSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($tokenModel->getMethod()),
            $this->worldpayhelper->getXmlPassword($tokenModel->getMethod())
        );
    }
    public function tokenDelete(
        SavedToken $tokenModel,
        \Magento\Customer\Model\Customer $customer,
        $storeId
    ) {
        $this->_wplogger->info('########## Submitting token Delete. TokenId: ' . $tokenModel->getId() . ' ##########');

        $requestParameters = array(
            'tokenModel'   => $tokenModel,
            'customer'     => $customer,
            'merchantCode' => $this->worldpayhelper->getMerchantCode($tokenModel->getMethod()),
        );

        /** @var SimpleXMLElement $simpleXml */
        $this->tokenDeleteXml = new \Sapient\Worldpay\Model\XmlBuilder\TokenDelete($requestParameters);
        $tokenDeleteSimpleXml = $this->tokenDeleteXml->build();

        return $this->_sendRequest(
            dom_import_simplexml($tokenDeleteSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($tokenModel->getMethod()),
            $this->worldpayhelper->getXmlPassword($tokenModel->getMethod())
        );
    }
}
