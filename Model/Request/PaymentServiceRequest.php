<?php
namespace Sapient\Worldpay\Model\Request;
/**
 * @copyright 2017 Sapient
 */
use Exception;
use Sapient\Worldpay\Model\SavedToken;
/**
 * Prepare the request and process them
 */
class PaymentServiceRequest  extends \Magento\Framework\DataObject
{
    /**
     * @var \Sapient\Worldpay\Model\Request $request
     */
    protected $_request;

    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Request $request
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Request $request,
        \Sapient\Worldpay\Helper\Data $worldpayhelper
    ) {
        $this->_wplogger = $wplogger;
        $this->_request = $request;
        $this->worldpayhelper = $worldpayhelper;
    }

    /**
     * Send 3d direct order XML to Worldpay server
     *
     * @param array $directOrderParams
     * @return mixed
     */
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
            $directOrderParams['paymentDetails'],
            $directOrderParams['paResponse'],
            $directOrderParams['echoData']
        );

        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($paymentType),
            $this->worldpayhelper->getXmlPassword($paymentType)
        );
    }

    /**
     * Send direct order XML to Worldpay server
     *
     * @param array $directOrderParams
     * @return mixed
     */
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


    /**
     * Send a payment request using tokenised saved card to the WorldPay server based on the order parameters.
     *
     * @param array $tokenOrderParams
     * @return mixed
     */
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

    /**
     * Send redirect order XML to Worldpay server
     *
     * @param array $redirectOrderParams
     * @return mixed
     */
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

    /**
     * Send Klarna Order request to Worldpay server
     *
     * @param array redirectOrderParams
     * @return mixed
     */
    public function redirectKlarnaOrder($redirectOrderParams)
    {
        $this->_wplogger->info('########## Submitting klarna redirect order request. OrderCode: ' . $redirectOrderParams['orderCode'] . ' ##########');

        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\RedirectKlarnaOrder();
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
            $redirectOrderParams['hideAddress'],
            $redirectOrderParams['orderLineItems']
        );

        return $this->_sendRequest(
            dom_import_simplexml($redirectSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($redirectOrderParams['paymentType']),
            $this->worldpayhelper->getXmlPassword($redirectOrderParams['paymentType'])
        );
    }

    /**
     * Send direct ideal order XML to Worldpay server
     *
     * @param array $redirectOrderParams
     * @return mixed
     */
    public function DirectIdealOrder($redirectOrderParams)
    {
        $this->_wplogger->info('########## Submitting direct Ideal order request. OrderCode: ' . $redirectOrderParams['orderCode'] . ' ##########');

        $requestConfiguration = array(
            'threeDSecureConfig' => $redirectOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $redirectOrderParams['tokenRequestConfig'],
            'shopperId' => $redirectOrderParams['shopperId']
        );
        $this->xmldirectidealorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectIdealOrder($requestConfiguration);
        $redirectSimpleXml = $this->xmldirectidealorder->build(
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
            $redirectOrderParams['hideAddress'],
            $redirectOrderParams['callbackurl'],
            $redirectOrderParams['cc_bank']
        );

        return $this->_sendRequest(
            dom_import_simplexml($redirectSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($redirectOrderParams['paymentType']),
            $this->worldpayhelper->getXmlPassword($redirectOrderParams['paymentType'])
        );
    }


    /**
     * Send capture XML to Worldpay server
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $wp
     * @param string $paymentMethodCode
     * @return mixed
     */
    public function capture(\Magento\Sales\Model\Order $order, $wp, $paymentMethodCode)
    {
        $orderCode = $wp->getWorldpayOrderId();
        $this->_wplogger->info('########## Submitting capture request. Order: ' . $orderCode . ' Amount:' . $order->getGrandTotal() . ' ##########');
        $this->xmlcapture = new \Sapient\Worldpay\Model\XmlBuilder\Capture();
        $captureSimpleXml = $this->xmlcapture->build(
            $this->worldpayhelper->getMerchantCode($wp->getPaymentType()),
            $orderCode,
            $order->getOrderCurrencyCode(),
            $order->getGrandTotal(),
            $wp->getPaymentType()
        );

        return $this->_sendRequest(
            dom_import_simplexml($captureSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($wp->getPaymentType()),
            $this->worldpayhelper->getXmlPassword($wp->getPaymentType())
        );
    }

    /**
     * process the request
     *
     * @param SimpleXmlElement $xml
     * @param string $username
     * @param string $password
     * @return SimpleXmlElement $response
     */
    protected function _sendRequest($xml, $username, $password)
    {
        $response = $this->_request->sendRequest($xml, $username, $password);

        $this->_checkForError($response);
        return $response;
    }

    /**
     * check error
     *
     * @param SimpleXmlElement $response
     * @throw Exception
     */
    protected function _checkForError($response)
    {
        $paymentService = new \SimpleXmlElement($response);
        $lastEvent = $paymentService->xpath('//lastEvent');
        if ($lastEvent && $lastEvent[0] =='REFUSED') {
            return;
        }
        $error = $paymentService->xpath('//error');

        if ($error) {
            $this->_wplogger->error('An error occurred while sending the request');
            $this->_wplogger->error('Error (code ' . $error[0]['code'] . '): ' . $error[0]);
            throw new Exception($error[0]);
        }
    }

    /**
     * Send refund XML to Worldpay server
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $wp
     * @param string $paymentMethodCode
     * @param float $amount
     * @param  $reference
     * @return mixed
     */
    public function refund(
        \Magento\Sales\Model\Order $order,
        $wp,
        $paymentMethodCode,
        $amount,
        $reference
    ) {
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

    /**
     * Send order inquery XML to Worldpay server
     *
     * @param string $merchantCode
     * @param string $orderCode
     * @param int $storeId
     * @param string $paymentMethodCode
     * @param string $paymenttype
     * @return mixed
     */
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

    /**
     * Send token update XML to Worldpay server
     *
     * @param SavedToken $tokenModel
     * @param \Magento\Customer\Model\Customer $customer
     * @param int $storeId
     * @return mixed
     */
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

    /**
     * Send token delete XML to Worldpay server
     *
     * @param SavedToken $tokenModel
     * @param \Magento\Customer\Model\Customer $customer
     * @param int $storeId
     * @return mixed
     */
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

    public function paymentOptionsByCountry($paymentOptionsParams)
    {
         $this->_wplogger->info('########## Submitting payment otions request ##########');
         $this->xmlpaymentoptions = new \Sapient\Worldpay\Model\XmlBuilder\PaymentOptions();
        $paymentOptionsXml = $this->xmlpaymentoptions->build(
            $paymentOptionsParams['merchantCode'],
            $paymentOptionsParams['countryCode']
        );

        return $this->_sendRequest(
            dom_import_simplexml($paymentOptionsXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($paymentOptionsParams['paymentType']),
            $this->worldpayhelper->getXmlPassword($paymentOptionsParams['paymentType'])
        );
    }
}
