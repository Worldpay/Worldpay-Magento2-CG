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
     * @param \Sapient\Worldpay\Helper\GeneralException $exceptionHelper
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Sapient\Worldpay\Helper\SendErrorReport $emailErrorReportHelper
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Request $request,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Sapient\Worldpay\Helper\GeneralException $exceptionHelper,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Sapient\Worldpay\Helper\SendErrorReport $emailErrorReportHelper
    ) {
        $this->_wplogger = $wplogger;
        $this->_request = $request;
        $this->worldpayhelper = $worldpayhelper;
        $this->exceptionHelper = $exceptionHelper;
        $this->_invoiceService = $invoiceService;
        $this->emailErrorReportHelper = $emailErrorReportHelper;
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
        
        if (isset($directOrderParams['tokenRequestConfig'])) {
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
        
        //$directOrderParams['paymentDetails']['cardType'] ='';
        //Level 23 data validation
        if ($this->worldpayhelper->isLevel23Enabled() && isset($directOrderParams['paymentDetails']['cardType'])
            && ($directOrderParams['paymentDetails']['cardType'] === 'ECMC-SSL'
                || $directOrderParams['paymentDetails']['cardType'] === 'VISA-SSL')
           && ($directOrderParams['billingAddress']['countryCode'] === 'US'
                || $directOrderParams['billingAddress']['countryCode'] === 'CA')) {
            $directOrderParams['paymentDetails']['isLevel23Enabled'] = true;
            $directOrderParams['paymentDetails']['cardAcceptorTaxId'] = $this->worldpayhelper->getCardAcceptorTaxId();
            $directOrderParams['paymentDetails']['dutyAmount'] = $this->worldpayhelper->getDutyAmount();
            $directOrderParams['paymentDetails']['countryCode'] = $directOrderParams['billingAddress']['countryCode'];
        }
        
        $xmlUsername = $this->worldpayhelper->getXmlUsername($directOrderParams['paymentDetails']['paymentType']);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($directOrderParams['paymentDetails']['paymentType']);
        $merchantCode = $directOrderParams['merchantCode'];
        
        if ($directOrderParams['method'] == 'worldpay_moto'
           && $directOrderParams['paymentDetails']['dynamicInteractionType'] == 'MOTO') {
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                    ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                    ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                    ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
        }

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
        if (empty($directOrderParams['orderLineItems'])) {
            $directOrderParams['orderLineItems'] = '';
        }
        
        $orderSimpleXml = $this->xmldirectorder->build(
            $merchantCode,
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
            $directOrderParams['primeRoutingData'],
            $directOrderParams['orderLineItems']
        );
        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
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
        
        if ($this->worldpayhelper->isLevel23Enabled()
           && isset($tokenOrderParams['paymentDetails']['cardType'])
           && ($tokenOrderParams['paymentDetails']['cardType'] === 'ECMC-SSL'
              || $tokenOrderParams['paymentDetails']['cardType'] === 'VISA-SSL')
           && ($tokenOrderParams['billingAddress']['countryCode'] === 'US'
              || $tokenOrderParams['billingAddress']['countryCode'] === 'CA')) {
             $tokenOrderParams['paymentDetails']['isLevel23Enabled'] = true;
             $tokenOrderParams['paymentDetails']['cardAcceptorTaxId'] = $this->worldpayhelper->getCardAcceptorTaxId();
             $tokenOrderParams['paymentDetails']['dutyAmount'] = $this->worldpayhelper->getDutyAmount();
             $tokenOrderParams['paymentDetails']['countryCode'] = $tokenOrderParams['billingAddress']['countryCode'];
        }

        $requestConfiguration = [
            'threeDSecureConfig' => $tokenOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $tokenOrderParams['tokenRequestConfig']
        ];

        $methodCode = $tokenOrderParams['paymentDetails']['brand'];
        if (isset($tokenOrderParams['paymentDetails']['methodCode'])) {
                $methodCode = $tokenOrderParams['paymentDetails']['methodCode'];
        }
        $xmlUsername = $this->worldpayhelper->getXmlUsername($methodCode);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($methodCode);
        $merchantCode = $tokenOrderParams['merchantCode'];
        
        if ($tokenOrderParams['method'] == 'worldpay_moto'
           && $tokenOrderParams['paymentDetails']['dynamicInteractionType'] == 'MOTO') {
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                    ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                    ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                    ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
        }
        
        $this->xmltokenorder = new \Sapient\Worldpay\Model\XmlBuilder\DirectOrder($requestConfiguration);
        if (empty($tokenOrderParams['thirdPartyData']) && empty($tokenOrderParams['shippingfee'])) {
            $tokenOrderParams['thirdPartyData']='';
            $tokenOrderParams['shippingfee']='';
        }
        if (empty($tokenOrderParams['primeRoutingData'])) {
            $tokenOrderParams['primeRoutingData'] = '';
        }
        if (empty($tokenOrderParams['orderLineItems'])) {
            $tokenOrderParams['orderLineItems'] = '';
        }
        $orderSimpleXml = $this->xmltokenorder->build(
            $merchantCode,
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
            $tokenOrderParams['primeRoutingData'],
            $tokenOrderParams['orderLineItems']
        );
        return $this->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
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
        
        //Level 23 data validation
        if ($this->worldpayhelper->isLevel23Enabled()
           && ($redirectOrderParams['paymentType'] === 'ECMC-SSL'
               || $redirectOrderParams['paymentType'] === 'VISA-SSL')
           && ($redirectOrderParams['billingAddress']['countryCode'] === 'US'
               || $redirectOrderParams['billingAddress']['countryCode'] === 'CA')) {
            $redirectOrderParams['paymentDetails']['isLevel23Enabled'] = true;
            $redirectOrderParams['paymentDetails']['cardAcceptorTaxId'] = $this->worldpayhelper->getCardAcceptorTaxId();
            $redirectOrderParams['paymentDetails']['dutyAmount'] = $this->worldpayhelper->getDutyAmount();
            $redirectOrderParams['paymentDetails']['countryCode'] =
                $redirectOrderParams['billingAddress']['countryCode'];
        }
        
        $requestConfiguration = [
            'threeDSecureConfig' => $redirectOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $redirectOrderParams['tokenRequestConfig'],
            'shopperId' => $redirectOrderParams['shopperId']
        ];
       
        $xmlUsername = $this->worldpayhelper->getXmlUsername($redirectOrderParams['paymentDetails']['cardType']);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($redirectOrderParams['paymentDetails']['cardType']);
        $merchantCode = $redirectOrderParams['merchantCode'];
        
        if ($redirectOrderParams['method'] == 'worldpay_moto') {
            $redirectOrderParams['paymentDetails']['PaymentMethod'] = $redirectOrderParams['method'];
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                    ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                    ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                    ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
        }
        
        $this->xmlredirectorder = new \Sapient\Worldpay\Model\XmlBuilder\RedirectOrder($requestConfiguration);
        if (empty($redirectOrderParams['thirdPartyData']) && empty($redirectOrderParams['shippingfee'])) {
            $redirectOrderParams['thirdPartyData']='';
            $redirectOrderParams['shippingfee']='';
        }
        if (empty($redirectOrderParams['statementNarrative'])) {
            $redirectOrderParams['statementNarrative']='';
        }
        if (empty($redirectOrderParams['orderLineItems'])) {
            $redirectOrderParams['orderLineItems'] = '';
        }
        $redirectSimpleXml = $this->xmlredirectorder->build(
            $merchantCode,
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
            $redirectOrderParams['cusDetails'],
            $redirectOrderParams['orderLineItems']
        );
        return $this->_sendRequest(
            dom_import_simplexml($redirectSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }

    /**
     * Send Klarna Order request to Worldpay server
     *
     * @param array $redirectOrderParams
     * @return mixed
     */
    public function redirectKlarnaOrder($redirectOrderParams)
    {
        try {
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
        } catch (Exception $ex) {
            $this->_wplogger->error($ex->getMessage());
            if ($ex->getMessage() == 'Payment Method KLARNA_PAYNOW-SSL is unknown; The Payment Method is not available.'
               || $ex->getMessage() == 'Payment Method KLARNA_SLICEIT-SSL is unknown; '
                    . 'The Payment Method is not available.'
               || $ex->getMessage() == 'Payment Method KLARNA_PAYLATER-SSL is unknown; '
                    . 'The Payment Method is not available.') {
                $codeErrorMessage = 'Klarna payment method is currently not available for this country.';
                $camErrorMessage = $this->getCreditCardSpecificException('AKLR01');
                $errorMessage = $camErrorMessage? $camErrorMessage : $codeErrorMessage;
                throw new \Magento\Framework\Exception\LocalizedException(__($errorMessage));
            }
        }
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
     * @param array|null $capturedItems
     * @return mixed
     */
    public function capture(\Magento\Sales\Model\Order $order, $wp, $paymentMethodCode, $capturedItems = null)
    {
        try {
            $orderCode = $wp->getWorldpayOrderId();
            $loggerMsg = '########## Submitting capture request. Order: ';
            $this->_wplogger->info($loggerMsg . $orderCode . ' Amount:' . $order->getGrandTotal() . ' ##########');
            $this->xmlcapture = new \Sapient\Worldpay\Model\XmlBuilder\Capture();
            $currencyCode = $order->getOrderCurrencyCode();
            $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);

            if (strpos($wp->getPaymentType(), "KLARNA") !== false && !empty($capturedItems)) {
                $invoicedItems = $this->getInvoicedItemsDetails($capturedItems);
            } else {
                $invoicedItems = '';
            }
            $captureType = 'full';

            $xmlUsername = $this->worldpayhelper->getXmlUsername($wp->getPaymentType());
            $xmlPassword = $this->worldpayhelper->getXmlPassword($wp->getPaymentType());
            $merchantCode = $this->worldpayhelper->getMerchantCode($wp->getPaymentType());

            if ($wp->getInteractionType() === 'MOTO') {
                $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                        ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
                $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                        ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
                $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                        ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
            }

            $captureSimpleXml = $this->xmlcapture->build(
                $merchantCode,
                $orderCode,
                $order->getOrderCurrencyCode(),
                $order->getGrandTotal(),
                $exponent,
                $order,
                $captureType,
                $wp->getPaymentType(),
                $invoicedItems
            );

            return $this->_sendRequest(
                dom_import_simplexml($captureSimpleXml)->ownerDocument,
                $xmlUsername,
                $xmlPassword
            );
        } catch (Exception $e) {
            $this->_wplogger->error($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }
       
    /**
     * Send Partial capture XML to Worldpay server
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $wp
     * @param float $grandTotal
     * @param array|null $capturedItems
     * @return mixed
     */
    public function partialCapture(\Magento\Sales\Model\Order $order, $wp, $grandTotal, $capturedItems = null)
    {
        try {
            $orderCode = $wp->getWorldpayOrderId();
            $loggerMsg = '########## Submitting Partial capture request. Order: ';
            $this->_wplogger->info($loggerMsg . $orderCode . ' Amount:' . $grandTotal . ' ##########');
            $this->xmlcapture = new \Sapient\Worldpay\Model\XmlBuilder\Capture();
            $currencyCode = $order->getOrderCurrencyCode();
            $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);

            if (strpos($wp->getPaymentType(), "KLARNA") !== false && !empty($capturedItems)) {
                $invoicedItems = $this->getInvoicedItemsDetails($capturedItems);
            } else {
                $invoicedItems = '';
            }
        
            $captureType = 'partial';
            
            $xmlUsername = $this->worldpayhelper->getXmlUsername($wp->getPaymentType());
            $xmlPassword = $this->worldpayhelper->getXmlPassword($wp->getPaymentType());
            $merchantCode = $this->worldpayhelper->getMerchantCode($wp->getPaymentType());

            if ($wp->getInteractionType() === 'MOTO') {
                $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                        ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
                $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                        ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
                $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                        ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
            }
            
            $captureSimpleXml = $this->xmlcapture->build(
                $merchantCode,
                $orderCode,
                $order->getOrderCurrencyCode(),
                $grandTotal,
                $exponent,
                $order,
                $captureType,
                $wp->getPaymentType(),
                $invoicedItems
            );

            return $this->_sendRequest(
                dom_import_simplexml($captureSimpleXml)->ownerDocument,
                $xmlUsername,
                $xmlPassword
            );
        } catch (Exception $e) {
            $this->_wplogger->error($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }
    
    /**
     * Process the request
     *
     * @param SimpleXmlElement $xml
     * @param string $username
     * @param string $password
     * @return SimpleXmlElement $response
     */
    protected function _sendRequest($xml, $username, $password)
    {
        $response = $this->_request->sendRequest($xml, $username, $password);
        $this->_checkForError($response, $xml);
        return $response;
    }

    /**
     * Check error
     *
     * @param SimpleXmlElement $response
     * @param string|null $xml
     * @throw Exception
     */
    protected function _checkForError($response, $xml = "")
    {
        $paymentService = new \SimpleXmlElement($response);
        $lastEvent = $paymentService->xpath('//lastEvent');
        if ($lastEvent && $lastEvent[0] =='REFUSED') {
            return;
        }
        $error = $paymentService->xpath('//error');

        if ($error) {
            $this->emailErrorReportHelper->sendErrorReport([
                'request'=>$xml->saveXML(),
                'response'=>$response,
                'error_code'=>$error[0]['code'],
                'error_message'=>$error[0]
            ]);

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
     * @param string|array $reference
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
        
        $xmlUsername = $this->worldpayhelper->getXmlUsername($wp->getPaymentType());
        $xmlPassword = $this->worldpayhelper->getXmlPassword($wp->getPaymentType());
        $merchantCode = $this->worldpayhelper->getMerchantCode($wp->getPaymentType());
        if ($wp->getInteractionType() === 'MOTO') {
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                    ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                    ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                    ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
        }
        
        $refundSimpleXml = $this->xmlrefund->build(
            $merchantCode,
            $orderCode,
            $order->getOrderCurrencyCode(),
            $amount,
            $reference,
            $exponent,
            $order,
            $wp->getPaymentType()
        );

        return $this->_sendRequest(
            dom_import_simplexml($refundSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
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
     * @param string $interactionType
     * @return mixed
     */
    public function inquiry($merchantCode, $orderCode, $storeId, $paymentMethodCode, $paymenttype, $interactionType)
    {
        $this->_wplogger->info('########## Submitting order inquiry. OrderCode: (' . $orderCode . ') ##########');
        
        $xmlUsername = $this->worldpayhelper->getXmlUsername($paymenttype);
        $xmlPassword = $this->worldpayhelper->getXmlPassword($paymenttype);
        $merchantcode = $merchantCode;
        
        if ($interactionType === 'MOTO') {
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                    ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                    ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantcode = !empty($this->worldpayhelper->getMotoMerchantCode())
                    ? $this->worldpayhelper->getMotoMerchantCode() : $merchantcode;
        }

        $this->xmlinquiry = new \Sapient\Worldpay\Model\XmlBuilder\Inquiry();
        $inquirySimpleXml = $this->xmlinquiry->build(
            $merchantcode,
            $orderCode
        );

        return $this->_sendRequest(
            dom_import_simplexml($inquirySimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
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

        $this->tokenDeleteXml = new \Sapient\Worldpay\Model\XmlBuilder\TokenDelete($requestParameters);
        $tokenDeleteSimpleXml = $this->tokenDeleteXml->build();

        return $this->_sendRequest(
            dom_import_simplexml($tokenDeleteSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($tokenModel->getMethod()),
            $this->worldpayhelper->getXmlPassword($tokenModel->getMethod())
        );
    }

    /**
     * Get Payment options based on country
     *
     * @param array $paymentOptionsParams
     * @return mixed
     */
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
      * @param array $applePayOrderParams
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
     * @param array $samsungPayOrderParams
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
    }
    
    /**
     * Send chromepay order XML to Worldpay server
     *
     * @param array $chromeOrderParams
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
        $this->_wplogger->info($loggerMsg . ' ##########');
        if (isset($directOrderParams['tokenRequestConfig'])) {
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
        $this->tokenInquiryXml = new \Sapient\Worldpay\Model\XmlBuilder\TokenInquiry($requestParameters);
        $tokenInquirySimpleXml = $this->tokenInquiryXml->build();

        return $this->_sendRequest(
            dom_import_simplexml($tokenInquirySimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($tokenModel->getMethod()),
            $this->worldpayhelper->getXmlPassword($tokenModel->getMethod())
        );
    }
    
    /**
     * Get country code
     *
     * @param string $cntrs
     * @param int $cntryId
     * @return mixed
     */
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
    
    /**
     * Get credit card specific exception
     *
     * @param string $exceptioncode
     * @return mixed
     */
    public function getCreditCardSpecificException($exceptioncode)
    {
        return $this->worldpayhelper->getCreditCardSpecificexception($exceptioncode);
    }

    /**
     * Void Sale
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $wp
     * @param string $paymentMethodCode
     * @return mixed
     */
    public function voidSale(\Magento\Sales\Model\Order $order, $wp, $paymentMethodCode)
    {
        $orderCode = $wp->getWorldpayOrderId();
        $this->_wplogger->info('########## Submitting void sale request. Order: '
        . $orderCode . ' Amount:' . $order->getGrandTotal() . ' ##########');
        $this->xmlvoidsale = new \Sapient\Worldpay\Model\XmlBuilder\VoidSale();
        $currencyCode = $order->getOrderCurrencyCode();
        $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);
        
        $xmlUsername = $this->worldpayhelper->getXmlUsername($wp->getPaymentType());
        $xmlPassword = $this->worldpayhelper->getXmlPassword($wp->getPaymentType());
        $merchantCode = $this->worldpayhelper->getMerchantCode($wp->getPaymentType());
        if ($wp->getInteractionType() === 'MOTO') {
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                    ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                    ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                    ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
        }
        
        $voidSaleSimpleXml = $this->xmlvoidsale->build(
            $merchantCode,
            $orderCode,
            $order->getOrderCurrencyCode(),
            $order->getGrandTotal(),
            $exponent,
            $wp->getPaymentType()
        );

        return $this->_sendRequest(
            dom_import_simplexml($voidSaleSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }
    
    /**
     * Cancel the order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $wp
     * @param string $paymentMethodCode
     * @return mixed
     */
    public function cancelOrder(\Magento\Sales\Model\Order $order, $wp, $paymentMethodCode)
    {
        $orderCode = $wp->getWorldpayOrderId();
        $this->_wplogger->info('########## Submitting cancel order request. Order: '
        . $orderCode . ' Amount:' . $order->getGrandTotal() . ' ##########');
        $this->xmlcancel = new \Sapient\Worldpay\Model\XmlBuilder\CancelOrder();
        $currencyCode = $order->getOrderCurrencyCode();
        $exponent = $this->worldpayhelper->getCurrencyExponent($currencyCode);
        
        $xmlUsername = $this->worldpayhelper->getXmlUsername($wp->getPaymentType());
        $xmlPassword = $this->worldpayhelper->getXmlPassword($wp->getPaymentType());
        $merchantCode = $this->worldpayhelper->getMerchantCode($wp->getPaymentType());
        if ($wp->getInteractionType() === 'MOTO') {
            $xmlUsername = !empty($this->worldpayhelper->getMotoUsername())
                    ? $this->worldpayhelper->getMotoUsername() : $xmlUsername;
            $xmlPassword = !empty($this->worldpayhelper->getMotoPassword())
                    ? $this->worldpayhelper->getMotoPassword() : $xmlPassword;
            $merchantCode = !empty($this->worldpayhelper->getMotoMerchantCode())
                    ? $this->worldpayhelper->getMotoMerchantCode() : $merchantCode;
        }
        
        $cancelSimpleXml = $this->xmlcancel->build(
            $merchantCode,
            $orderCode,
            $order->getOrderCurrencyCode(),
            $order->getGrandTotal(),
            $exponent,
            $wp->getPaymentType(),
            $order
        );

        return $this->_sendRequest(
            dom_import_simplexml($cancelSimpleXml)->ownerDocument,
            $xmlUsername,
            $xmlPassword
        );
    }
    
    /**
     * Get invoice cart item details
     *
     * @param array $capturedItems
     * @return mixed
     */
    public function getInvoicedItemsDetails($capturedItems)
    {
        $items = $this->getItemDetails($capturedItems);

        if ($items['is_bundle_item_present'] > 0 ||
           (count($items['invoicedItems']) == 1 &&
                (in_array("downloadable", $items['invoicedItems']['0']) ||
                 in_array("giftcard", $items['invoicedItems']['0'])))) {
            $items['trackingId'] = '';
            return $items;
        } else {
            if (array_key_exists('tracking', $capturedItems)
                && count($capturedItems['tracking']) < 2
                && !empty($capturedItems['tracking']['1']['number'])) {
                $items['trackingId'] = $capturedItems['tracking']['1']['number'];
                return $items;
            } else {
                $codeErrorMessage = 'Please create Shippment with single tracking number.';
                $camErrorMessage = $this->exceptionHelper->getConfigValue('AAKL01');
                if (array_key_exists('tracking', $capturedItems) && count($capturedItems['tracking']) > 1) {
                    $codeErrorMessage = 'Multi shipping is currently not available, please add single tracking number.';
                    $camErrorMessage = $this->exceptionHelper->getConfigValue('AAKL02');
                } elseif (array_key_exists('tracking', $capturedItems)
                        && empty($capturedItems['tracking']['1']['number'])) {
                    $codeErrorMessage = 'Tracking number can not be blank, please add.';
                    $camErrorMessage = $this->exceptionHelper->getConfigValue('AAKL03');
                }
                $errorMessage = $camErrorMessage ? $camErrorMessage : $codeErrorMessage;
                throw new \Magento\Framework\Exception\LocalizedException(__($errorMessage));
            }
        }
    }

    /**
     * Get cart item details
     *
     * @param array $capturedItems
     * @return mixed
     */
    public function getItemDetails($capturedItems)
    {
        $items = [];
        $count = 0;
        $bundleCount = 0;
        $filteredItems = array_filter($capturedItems['invoice']['items']);
        foreach ($filteredItems as $key => $val) {
            $items['invoicedItems'][] = $this->worldpayhelper->getInvoicedItemsData($key);
            $items['invoicedItems'][$count]['qty_invoiced'] = $val;
            if ($items['invoicedItems'][$count]['product_type'] == 'bundle') {
                $bundleCount++;
            }
            $count++;
        }
        $items['is_bundle_item_present'] = $bundleCount;
        return $items;
    }
}
