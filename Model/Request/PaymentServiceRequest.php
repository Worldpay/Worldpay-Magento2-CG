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
class PaymentServiceRequest extends \Magento\Framework\DataObject
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
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService
    ) {
        $this->_wplogger = $wplogger;
        $this->_request = $request;
        $this->worldpayhelper = $worldpayhelper;
        $this->_invoiceService = $invoiceService;
    }

    /**
     * Send 3d direct order XML to Worldpay server
     *
     * @param array $directOrderParams
     * @return mixed
     */
    public function order3DSecure($directOrderParams)
    {
        $loggerMsg = '########## Submitting direct 3DSecure order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $directOrderParams['orderCode'] . ' ##########');
        if(isset($directOrderParams['tokenRequestConfig'])) {
        $requestConfiguration = [
            'threeDSecureConfig' => $directOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $directOrderParams['tokenRequestConfig']
        ];

        $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectOrder($requestConfiguration);
        $paymentType = isset($directOrderParams['paymentDetails']['brand']) ?
                $directOrderParams['paymentDetails']['brand']: $directOrderParams['paymentDetails']['paymentType'];
        $orderSimpleXml = $this->xmldirectorder->build3DSecure(
            $directOrderParams['merchantCode'],
            $directOrderParams['orderCode'],
            $directOrderParams['paymentDetails'],
            $directOrderParams['paResponse'],
            $directOrderParams['echoData']
        );
        } else {
            $requestConfiguration = [
            'threeDSecureConfig' => $directOrderParams['threeDSecureConfig']
        ];
         $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\WalletOrder($requestConfiguration);
        $paymentType = $directOrderParams['paymentType'];
        $orderSimpleXml = $this->xmldirectorder->build3DSecure(
            $directOrderParams['merchantCode'],
            $directOrderParams['orderCode'],
            $directOrderParams['paymentDetails'],
            $directOrderParams['paResponse'],
            $directOrderParams['echoData']
        );
        }

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
        $loggerMsg = '########## Submitting direct order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $directOrderParams['orderCode'] . ' ##########');
        $requestConfiguration = [
            'threeDSecureConfig' => $directOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $directOrderParams['tokenRequestConfig']
        ];
        $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectOrder($requestConfiguration);
        
        if (empty($directOrderParams['thirdPartyData']) && empty($directOrderParams['shippingfee'])) {
            $directOrderParams['thirdPartyData']='';
            $directOrderParams['shippingfee']='';
        }
        if (empty($directOrderParams['shippingAddress'])) {
            $directOrderParams['shippingAddress']='';
        }
        if (empty($directOrderParams['saveCardEnabled'])) {
            $directOrderParams['saveCardEnabled']='';
        }
        if (empty($directOrderParams['tokenizationEnabled'])) {
            $directOrderParams['tokenizationEnabled']='';
        }
        if (empty($directOrderParams['storedCredentialsEnabled'])) {
            $directOrderParams['storedCredentialsEnabled']='';
        }
        if (empty($directOrderParams['exemptionEngine'])) {
            $directOrderParams['exemptionEngine']='';
        }
        if (empty($directOrderParams['cusDetails'])) {
            $directOrderParams['cusDetails']='';
        }
        if (empty($directOrderParams['primeRoutingData'])) {
            $directOrderParams['primeRoutingData'] = '';
        }
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
            $directOrderParams['shopperId'],
            $directOrderParams['saveCardEnabled'],
            $directOrderParams['tokenizationEnabled'],
            $directOrderParams['storedCredentialsEnabled'],
            $directOrderParams['cusDetails'],
            $directOrderParams['exemptionEngine'],
            $directOrderParams['thirdPartyData'],
            $directOrderParams['shippingfee'],
            $directOrderParams['exponent'],
            $directOrderParams['primeRoutingData']
        );
        
        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($directOrderParams['paymentDetails']['paymentType']),
            $this->worldpayhelper->getXmlPassword($directOrderParams['paymentDetails']['paymentType'])
        );
    }
    
    /**
     * Send ACH order XML to Worldpay server
     *
     * @param array $directOrderParams
     * @return mixed
     */
    public function achOrder($directOrderParams)
    {
        $this->_wplogger->info('########## Submitting ACH order request. OrderCode: ' .
        $directOrderParams['orderCode'] . ' ##########');
        $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\ACHOrder();
        $orderSimpleXml = $this->xmldirectorder->build(
            $directOrderParams['merchantCode'],
            $directOrderParams['orderCode'],
            $directOrderParams['orderDescription'],
            $directOrderParams['currencyCode'],
            $directOrderParams['amount'],
            $directOrderParams['paymentDetails'],
            $directOrderParams['shopperEmail'],
            $directOrderParams['acceptHeader'],
            $directOrderParams['userAgentHeader'],
            $directOrderParams['shippingAddress'],
            $directOrderParams['billingAddress'],
            $directOrderParams['shopperId'],
            $directOrderParams['statementNarrative'],
            $directOrderParams['exponent']
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
        $loggerMsg = '########## Submitting direct token order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $tokenOrderParams['orderCode'] . ' ##########');

        $requestConfiguration = [
            'threeDSecureConfig' => $tokenOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $tokenOrderParams['tokenRequestConfig']
        ];
        $this->xmltokenorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectOrder($requestConfiguration);
        if (empty($tokenOrderParams['thirdPartyData']) && empty($tokenOrderParams['shippingfee'])) {
            $tokenOrderParams['thirdPartyData']='';
            $tokenOrderParams['shippingfee']='';
        }
        if (empty($tokenOrderParams['primeRoutingData'])) {
            $tokenOrderParams['primeRoutingData'] = '';
        }
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
            $tokenOrderParams['shopperId'],
            $tokenOrderParams['saveCardEnabled'],
            $tokenOrderParams['tokenizationEnabled'],
            $tokenOrderParams['storedCredentialsEnabled'],
            $tokenOrderParams['cusDetails'],
            $tokenOrderParams['exemptionEngine'],
            $tokenOrderParams['thirdPartyData'],
            $tokenOrderParams['shippingfee'],
            $tokenOrderParams['exponent'],
            $tokenOrderParams['primeRoutingData']
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
        $loggerMsg = '########## Submitting redirect order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $redirectOrderParams['orderCode'] . ' ##########');

        $requestConfiguration = [
            'threeDSecureConfig' => $redirectOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $redirectOrderParams['tokenRequestConfig'],
            'shopperId' => $redirectOrderParams['shopperId']
        ];
        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\RedirectOrder($requestConfiguration);
        if (empty($redirectOrderParams['thirdPartyData']) && empty($redirectOrderParams['shippingfee'])) {
            $redirectOrderParams['thirdPartyData']='';
            $redirectOrderParams['shippingfee']='';
        }
        if (empty($redirectOrderParams['statementNarrative'])) {
            $redirectOrderParams['statementNarrative']='';
        }
        $redirectSimpleXml = $this->xmlredirectorder->build(
            $redirectOrderParams['merchantCode'],
            $redirectOrderParams['orderCode'],
            $redirectOrderParams['orderDescription'],
            $redirectOrderParams['currencyCode'],
            $redirectOrderParams['amount'],
            $redirectOrderParams['paymentType'],
            $redirectOrderParams['shopperEmail'],
            $redirectOrderParams['statementNarrative'],
            $redirectOrderParams['acceptHeader'],
            $redirectOrderParams['userAgentHeader'],
            $redirectOrderParams['shippingAddress'],
            $redirectOrderParams['billingAddress'],
            $redirectOrderParams['paymentPagesEnabled'],
            $redirectOrderParams['installationId'],
            $redirectOrderParams['hideAddress'],
            $redirectOrderParams['paymentDetails'],
            $redirectOrderParams['thirdPartyData'],
            $redirectOrderParams['shippingfee'],
            $redirectOrderParams['exponent'],
            $redirectOrderParams['cusDetails']
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
        $loggerMsg = '########## Submitting klarna redirect order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $redirectOrderParams['orderCode'] . ' ##########');
        if (empty($redirectOrderParams['statementNarrative'])) {
            $redirectOrderParams['statementNarrative']='';
        }
        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\RedirectKlarnaOrder();
        $redirectSimpleXml = $this->xmlredirectorder->build(
            $redirectOrderParams['merchantCode'],
            $redirectOrderParams['orderCode'],
            $redirectOrderParams['orderDescription'],
            $redirectOrderParams['currencyCode'],
            $redirectOrderParams['amount'],
            $redirectOrderParams['paymentType'],
            $redirectOrderParams['shopperEmail'],
            $redirectOrderParams['statementNarrative'],
            $redirectOrderParams['acceptHeader'],
            $redirectOrderParams['userAgentHeader'],
            $redirectOrderParams['shippingAddress'],
            $redirectOrderParams['billingAddress'],
            $redirectOrderParams['paymentPagesEnabled'],
            $redirectOrderParams['installationId'],
            $redirectOrderParams['hideAddress'],
            $redirectOrderParams['orderLineItems'],
            $redirectOrderParams['exponent'],
            $redirectOrderParams['sessionData'],
            $redirectOrderParams['orderContent']
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
    public function directIdealOrder($redirectOrderParams)
    {
        $loggerMsg = '########## Submitting direct Ideal order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $redirectOrderParams['orderCode'] . ' ##########');

        $requestConfiguration = [
            'threeDSecureConfig' => $redirectOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $redirectOrderParams['tokenRequestConfig'],
            'shopperId' => $redirectOrderParams['shopperId']
        ];
        if (empty($redirectOrderParams['statementNarrative'])) {
            $redirectOrderParams['statementNarrative']='';
        }
        $this->xmldirectidealorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectIdealOrder($requestConfiguration);
        $redirectSimpleXml = $this->xmldirectidealorder->build(
            $redirectOrderParams['merchantCode'],
            $redirectOrderParams['orderCode'],
            $redirectOrderParams['orderDescription'],
            $redirectOrderParams['currencyCode'],
            $redirectOrderParams['amount'],
            $redirectOrderParams['paymentType'],
            $redirectOrderParams['shopperEmail'],
            $redirectOrderParams['statementNarrative'],
            $redirectOrderParams['acceptHeader'],
            $redirectOrderParams['userAgentHeader'],
            $redirectOrderParams['shippingAddress'],
            $redirectOrderParams['billingAddress'],
            $redirectOrderParams['paymentPagesEnabled'],
            $redirectOrderParams['installationId'],
            $redirectOrderParams['hideAddress'],
            $redirectOrderParams['callbackurl'],
            $redirectOrderParams['cc_bank'],
            $redirectOrderParams['exponent']
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
        $loggerMsg = '########## Submitting capture request. Order: ';
        $this->_wplogger->info($loggerMsg . $orderCode . ' Amount:' . $order->getGrandTotal() . ' ##########');
        $this->xmlcapture = new \Sapient\Worldpay\Model\XmlBuilder\Capture();
        $currencyCode = $order->getOrderCurrencyCode();
        $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);
         
        $captureSimpleXml = $this->xmlcapture->build(
            $this->worldpayhelper->getMerchantCode($wp->getPaymentType()),
            $orderCode,
            $order->getOrderCurrencyCode(),
            $order->getGrandTotal(),
            $exponent,
            $wp->getPaymentType()
        );

        return $this->_sendRequest(
            dom_import_simplexml($captureSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($wp->getPaymentType()),
            $this->worldpayhelper->getXmlPassword($wp->getPaymentType())
        );
    }
       
    /**
     * Send Partial capture XML to Worldpay server
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $wp
     * @param string $paymentMethodCode
     * @return mixed
     */
    public function partialCapture(\Magento\Sales\Model\Order $order, $wp, $grandTotal)
    {
        $orderCode = $wp->getWorldpayOrderId();
        $loggerMsg = '########## Submitting Partial capture request. Order: ';
        $this->_wplogger->info($loggerMsg . $orderCode . ' Amount:' . $grandTotal . ' ##########');
        $this->xmlcapture = new \Sapient\Worldpay\Model\XmlBuilder\Capture();
        $currencyCode = $order->getOrderCurrencyCode();
        $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);
        
        $captureSimpleXml = $this->xmlcapture->build(
            $this->worldpayhelper->getMerchantCode($wp->getPaymentType()),
            $orderCode,
            $order->getOrderCurrencyCode(),
            $grandTotal,
            $exponent,
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
       
        /*
        $response = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE paymentService PUBLIC "-//WorldPay//DTD WorldPay PaymentService v1//EN"
                                "http://dtd.worldpay.com/paymentService_v1.dtd">
<paymentService version="1.4" merchantCode="SAPIENTNITROECOMMERCEV1"><reply><orderStatus orderCode="sp-2"><payment><paymentMethod>ECMC-SSL</paymentMethod><paymentMethodDetail><card number="5445********6985" type="creditcard"><expiryDate><date month="10" year="2023"/></expiryDate></card></paymentMethodDetail><amount value="100" currencyCode="USD" exponent="2" debitCreditIndicator="credit"/><lastEvent>AUTHORISED</lastEvent><AuthorisationId id="164818"/><CVCResultCode description="NOT SUPPLIED BY SHOPPER"/><AVSResultCode description="NOT SUPPLIED BY SHOPPER"/><AAVAddressResultCode description="UNKNOWN"/><AAVPostcodeResultCode description="UNKNOWN"/><AAVCardholderNameResultCode description="UNKNOWN"/><AAVTelephoneResultCode description="UNKNOWN"/><AAVEmailResultCode description="UNKNOWN"/><issuerCountryCode>IT</issuerCountryCode><balance accountType="IN_PROCESS_AUTHORISED"><amount value="100" currencyCode="USD" exponent="2" debitCreditIndicator="credit"/></balance><riskScore value="20"/><instalments>1</instalments></payment></orderStatus></reply></paymentService>
';

       */
       
       
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/worldpay.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info('after req sent.........');
            
            
        
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
            if ($error[0]['code'] == 6) {
                $error[0] = $this->getCreditCardSpecificException('CCAM12');
            }
            throw new \Magento\Framework\Exception\ValidatorException(
                __($error[0])
            );
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
        $loggerMsg = '########## Submitting refund request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $orderCode . ' ##########');
        $this->xmlrefund = new \Sapient\Worldpay\Model\XmlBuilder\Refund();
        $currencyCode = $order->getOrderCurrencyCode();
        $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);
        $refundSimpleXml = $this->xmlrefund->build(
            $this->worldpayhelper->getMerchantCode($wp->getPaymentType()),
            $orderCode,
            $order->getOrderCurrencyCode(),
            $amount,
            $reference,
            $exponent
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
        $requestParameters = [
            'tokenModel'   => $tokenModel,
            'customer'     => $customer,
            'merchantCode' => $this->worldpayhelper->getMerchantCode($tokenModel->getMethod()),
        ];
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

        $requestParameters = [
            'tokenModel'   => $tokenModel,
            'customer'     => $customer,
            'merchantCode' => $this->worldpayhelper->getMerchantCode($tokenModel->getMethod()),
        ];

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
        $spoofCountryId = '';
        $countryCodeSpoofs = $this->worldpayhelper->getCountryCodeSpoofs();
        if ($countryCodeSpoofs) {
            $spoofCountryId = $this->getCountryCodeSpoof($countryCodeSpoofs, $paymentOptionsParams['countryCode']);
        }
        $countryId = ($spoofCountryId)? $spoofCountryId : $paymentOptionsParams['countryCode'];
        $this->_wplogger->info('########## Submitting payment otions request ##########');
        $this->xmlpaymentoptions = new \Sapient\Worldpay\Model\XmlBuilder\PaymentOptions();
        $paymentOptionsXml = $this->xmlpaymentoptions->build(
            $paymentOptionsParams['merchantCode'],
            $countryId
        );

        return $this->_sendRequest(
            dom_import_simplexml($paymentOptionsXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($paymentOptionsParams['paymentType']),
            $this->worldpayhelper->getXmlPassword($paymentOptionsParams['paymentType'])
        );
    }

    /**
     * Send wallet order XML to Worldpay server
     *
     * @param array $walletOrderParams
     * @return mixed
     */
    public function walletsOrder($walletOrderParams)
    {
        $loggerMsg = '########## Submitting wallet order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $walletOrderParams['orderCode'] . ' ##########');

        $requestConfiguration = [
            'threeDSecureConfig' => $walletOrderParams['threeDSecureConfig'],
        ];
        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\WalletOrder($requestConfiguration);
            $walletSimpleXml = $this->xmlredirectorder->build(
                $walletOrderParams['merchantCode'],
                $walletOrderParams['orderCode'],
                $walletOrderParams['orderDescription'],
                $walletOrderParams['currencyCode'],
                $walletOrderParams['amount'],
                $walletOrderParams['paymentType'],
                $walletOrderParams['shopperEmail'],
                $walletOrderParams['acceptHeader'],
                $walletOrderParams['userAgentHeader'],
                $walletOrderParams['protocolVersion'],
                $walletOrderParams['signature'],
                $walletOrderParams['signedMessage'],
                $walletOrderParams['shippingAddress'],
                $walletOrderParams['billingAddress'],
                $walletOrderParams['cusDetails'],
                $walletOrderParams['shopperIpAddress'],
                $walletOrderParams['paymentDetails'],
                $walletOrderParams['exponent']
            );
            
        return $this->_sendRequest(
            dom_import_simplexml($walletSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($walletOrderParams['paymentType']),
            $this->worldpayhelper->getXmlPassword($walletOrderParams['paymentType'])
        );
    }
    
     /**
      * Send Apple Pay order XML to Worldpay server
      *
      * @param array $walletOrderParams
      * @return mixed
      */
    public function applePayOrder($applePayOrderParams)
    {
        $loggerMsg = '########## Submitting apple pay order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $applePayOrderParams['orderCode'] . ' ##########');

        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\ApplePayOrder();
        
        $appleSimpleXml = $this->xmlredirectorder->build(
            $applePayOrderParams['merchantCode'],
            $applePayOrderParams['orderCode'],
            $applePayOrderParams['orderDescription'],
            $applePayOrderParams['currencyCode'],
            $applePayOrderParams['amount'],
            $applePayOrderParams['paymentType'],
            $applePayOrderParams['shopperEmail'],
            $applePayOrderParams['protocolVersion'],
            $applePayOrderParams['signature'],
            $applePayOrderParams['data'],
            $applePayOrderParams['ephemeralPublicKey'],
            $applePayOrderParams['publicKeyHash'],
            $applePayOrderParams['transactionId'],
            $applePayOrderParams['exponent']
        );
        
        return $this->_sendRequest(
            dom_import_simplexml($appleSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($applePayOrderParams['paymentType']),
            $this->worldpayhelper->getXmlPassword($applePayOrderParams['paymentType'])
        );
    }
    
    /**
     * Send Samsung Pay order XML to Worldpay server
     *
     * @param array $walletOrderParams
     * @return mixed
     */
    public function samsungPayOrder($samsungPayOrderParams)
    {
        $loggerMsg = '########## Submitting samsung pay order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $samsungPayOrderParams['orderCode'] . ' ##########');
   
        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\SamsungPayOrder();
  
        $appleSimpleXml = $this->xmlredirectorder->build(
            $samsungPayOrderParams['merchantCode'],
            $samsungPayOrderParams['orderCode'],
            $samsungPayOrderParams['orderDescription'],
            $samsungPayOrderParams['currencyCode'],
            $samsungPayOrderParams['amount'],
            $samsungPayOrderParams['paymentType'],
            $samsungPayOrderParams['shopperEmail'],
            $samsungPayOrderParams['data'],
            $samsungPayOrderParams['exponent']
        );
        
        return $response =  $this->_sendRequest(
            dom_import_simplexml($appleSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($samsungPayOrderParams['paymentType']),
            $this->worldpayhelper->getXmlPassword($samsungPayOrderParams['paymentType'])
        );
        
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/worldpay.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('response ....');

        $logger->info(print_r($response, true));

        $logger->info('response got it....');
    }
    
    /**
     * Send chromepay order XML to Worldpay server
     *
     * @param array $chromepayOrderParams
     * @return mixed
     */
    public function chromepayOrder($chromeOrderParams)
    {
        $loggerMsg = '########## Submitting chromepay order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $chromeOrderParams['orderCode'] . ' ##########');
        $paymentType = 'worldpay_cc';
        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\ChromePayOrder();
        $chromepaySimpleXml = $this->xmlredirectorder->build(
            $chromeOrderParams['merchantCode'],
            $chromeOrderParams['orderCode'],
            $chromeOrderParams['orderDescription'],
            $chromeOrderParams['currencyCode'],
            $chromeOrderParams['amount'],
            $chromeOrderParams['paymentType'],
            $chromeOrderParams['paymentDetails'],
            $chromeOrderParams['shippingAddress'],
            $chromeOrderParams['billingAddress'],
            $chromeOrderParams['shopperEmail'],
            $chromeOrderParams['exponent']
        );
        //echo $this->worldpayhelper->getXmlUsername($paymentType);exit;
        return $this->_sendRequest(
            dom_import_simplexml($chromepaySimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($paymentType),
            $this->worldpayhelper->getXmlPassword($paymentType)
        );
    }
    
    /**
     * Send 3d direct order XML to Worldpay server
     *
     * @param array $directOrderParams
     * @return mixed
     */
    public function order3Ds2Secure($directOrderParams)
    {
        $loggerMsg = '########## Submitting direct 3Ds2Secure order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $directOrderParams['orderCode'] . ' ##########');
        if(isset($directOrderParams['tokenRequestConfig'])) {
        $requestConfiguration = [
            'threeDSecureConfig' => $directOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $directOrderParams['tokenRequestConfig']
        ];
         $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectOrder($requestConfiguration);
        $paymentType = isset($directOrderParams['paymentDetails']['brand']) ?
                $directOrderParams['paymentDetails']['brand']: $directOrderParams['paymentDetails']['paymentType'];
        $orderSimpleXml = $this->xmldirectorder->build3Ds2Secure(
            $directOrderParams['merchantCode'],
            $directOrderParams['orderCode'],
            $directOrderParams['paymentDetails'],
            $directOrderParams['paymentDetails']['dfReferenceId']
        );
        } else {
          $requestConfiguration = [
            'threeDSecureConfig' => $directOrderParams['threeDSecureConfig']
        ];
         $this->xmldirectorder = new \Sapient\Worldpay\Model\XmlBuilder\WalletOrder($requestConfiguration);
        $paymentType = $directOrderParams['paymentType'];
        $orderSimpleXml = $this->xmldirectorder->build3Ds2Secure(
            $directOrderParams['merchantCode'],
            $directOrderParams['orderCode'],
            $directOrderParams['paymentDetails'],
            $directOrderParams['paymentDetails']['dfReferenceId']
        );
        }
        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($paymentType),
            $this->worldpayhelper->getXmlPassword($paymentType)
        );
    }
    
    /**
     * Send token inquiry XML to Worldpay server
     *
     * @param SavedToken $tokenModel
     * @param \Magento\Customer\Model\Customer $customer
     * @param int $storeId
     * @return mixed
     */
    public function tokenInquiry(
        SavedToken $tokenModel,
        \Magento\Customer\Model\Customer $customer,
        $storeId
    ) {
        $this->_wplogger->info('########## Submitting token inquiry. TokenId: ' . $tokenModel->getId() . ' ##########');
        $requestParameters = [
            'tokenModel'   => $tokenModel,
            'customer'     => $customer,
            'merchantCode' => $this->worldpayhelper->getMerchantCode($tokenModel->getMethod()),
        ];
        /** @var SimpleXMLElement $simpleXml */
        $this->tokenInquiryXml = new \Sapient\Worldpay\Model\XmlBuilder\TokenInquiry($requestParameters);
        $tokenInquirySimpleXml = $this->tokenInquiryXml->build();

        return $this->_sendRequest(
            dom_import_simplexml($tokenInquirySimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($tokenModel->getMethod()),
            $this->worldpayhelper->getXmlPassword($tokenModel->getMethod())
        );
    }
    
    private function getCountryCodeSpoof($cntrs, $cntryId)
    {
        if ($cntrs) {
            $countryList = explode(',', $cntrs);
            foreach ($countryList as $contry) {
                list($k, $v) = explode('-', $contry);
                if ($k === $cntryId) {
                    return $v;
                }
            }
        }
        return false;
    }
    
    public function getCreditCardSpecificException($exceptioncode)
    {
        return $this->worldpayhelper->getCreditCardSpecificexception($exceptioncode);
    }
    public function voidSale(\Magento\Sales\Model\Order $order, $wp, $paymentMethodCode)
    {
        $orderCode = $wp->getWorldpayOrderId();
        $this->_wplogger->info('########## Submitting void sale request. Order: '
        . $orderCode . ' Amount:' . $order->getGrandTotal() . ' ##########');
        $this->xmlvoidsale = new \Sapient\Worldpay\Model\XmlBuilder\VoidSale();
        $currencyCode = $order->getOrderCurrencyCode();
        $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);
        
        $voidSaleSimpleXml = $this->xmlvoidsale->build(
            $this->worldpayhelper->getMerchantCode($wp->getPaymentType()),
            $orderCode,
            $order->getOrderCurrencyCode(),
            $order->getGrandTotal(),
            $exponent,
            $wp->getPaymentType()
        );

        return $this->_sendRequest(
            dom_import_simplexml($voidSaleSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($wp->getPaymentType()),
            $this->worldpayhelper->getXmlPassword($wp->getPaymentType())
        );
    }
    
    public function cancelOrder(\Magento\Sales\Model\Order $order, $wp, $paymentMethodCode)
    {
        $orderCode = $wp->getWorldpayOrderId();
        $this->_wplogger->info('########## Submitting cancel order request. Order: '
        . $orderCode . ' Amount:' . $order->getGrandTotal() . ' ##########');
        $this->xmlcancel = new \Sapient\Worldpay\Model\XmlBuilder\CancelOrder();
        $currencyCode = $order->getOrderCurrencyCode();
        $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);
        
        $cancelSimpleXml = $this->xmlcancel->build(
            $this->worldpayhelper->getMerchantCode($wp->getPaymentType()),
            $orderCode,
            $order->getOrderCurrencyCode(),
            $order->getGrandTotal(),
            $exponent,
            $wp->getPaymentType()
        );

        return $this->_sendRequest(
            dom_import_simplexml($cancelSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($wp->getPaymentType()),
            $this->worldpayhelper->getXmlPassword($wp->getPaymentType())
        );
    }
}
