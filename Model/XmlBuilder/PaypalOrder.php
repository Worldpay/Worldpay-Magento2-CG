<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder;

use Magento\Framework\UrlInterface;
use SimpleXMLElement;

/**
 * Build xml for Direct Order request
 */
class PaypalOrder extends AbstractXmlBuilder
{
    public const TOKEN_SCOPE = 'shopper';

    public UrlInterface $_urlBuilder;
    protected string $merchantCode;
    protected array $orderParameters;
    protected string $installationId;
    protected string $captureDelay;
    public array $paymentDetails;
    public array $cusDetails;

    public function __construct(UrlInterface $urlBuilder)
    {
        $this->_urlBuilder = $urlBuilder;
    }

    public function build(string $merchantCode, array $orderParameters): SimpleXMLElement
    {
        $this->merchantCode = $merchantCode;
        $this->orderParameters = $orderParameters;
        $this->paymentDetails = $orderParameters['paymentDetails'];
        $this->cusDetails = $orderParameters['cusDetails'];

        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['merchantCode'] = $this->merchantCode;
        $xml['version'] = '1.4';

        $submit = $xml->addChild('submit');
        $this->_addOrderElement($submit);

        return $xml;
    }

    private function _addOrderElement(SimpleXMLElement $submit): void
    {
        $order = $submit->addChild('order');
        $order['orderCode'] = $this->orderParameters['orderCode'];

        if ($this->orderParameters['captureDelay'] != "") {
            $order['captureDelay'] = $this->orderParameters['captureDelay'];
        }

        $this->_addDescriptionElement($order);
        $this->_addAmountElement($order);
        $this->_addOrderContentElement($order);
        $this->_addPaymentDetailsElement($order);
        $this->_addShopperElement($order);
        if (!isset($this->paymentDetails['myaccountSave'])) {
            $this->_addShippingElement($order);
        }
        $this->_addBillingElement($order);

        if (isset($this->orderParameters['echoData'])) {
            $order->addChild('echoData', $this->orderParameters['echoData']);
        }

        if (isset($this->orderParameters['primeRoutingData']['advanced_primerouting'])) {
            $this->_addPrimeRoutingElement($order);
        }
        $this->_addCustomerRiskData($order);
        $this->_addFraudSightData($order);
    }

    private function _addDescriptionElement(SimpleXMLElement $order): void
    {
        $description = $order->addChild('description');
        $this->_addCDATA($description, $this->orderParameters['orderDescription'] );
    }

    private function _addAmountElement(SimpleXMLElement $order): void
    {
        $amountElement = $order->addChild('amount');
        $amountElement['currencyCode'] = $this->orderParameters['currencyCode'];
        $amountElement['exponent'] = $this->orderParameters['exponent'];
        $amountElement['value'] = $this->_amountAsInt($this->orderParameters['amount']);
    }

    protected function _addPaymentDetailsElement(SimpleXMLElement $order): void
    {
        $paymentDetailsElement = $order->addChild('paymentDetails');

        $this->addPaypalPaymentDetails($paymentDetailsElement);

        $isSendIpAddress = true;
        if (isset($this->paymentDetails['sendShopperIpAddress'])) {
            $isSendIpAddress = $this->paymentDetails['sendShopperIpAddress'];
        }
        if ($isSendIpAddress) {
            $session = $paymentDetailsElement->addChild('session');
            $session['id'] = $this->paymentDetails['sessionId'];
            $session['shopperIPAddress'] = $this->paymentDetails['shopperIpAddress'];
        }
    }

    protected function addPaypalPaymentDetails(SimpleXMLElement $paymentDetailsElement): void
    {
        $paymentTypeElement = $paymentDetailsElement->addChild('PAYPAL-SSL');
        $paymentTypeElement['intent'] = 'authorise';
        $paymentTypeElement->addChild('successURL', $this->_urlBuilder->getUrl('worldpay/redirectresult/success'));
        $paymentTypeElement->addChild('cancelURL', $this->_urlBuilder->getUrl('worldpay/redirectresult/cancel'));
        $paymentTypeElement->addChild('pendingURL', $this->_urlBuilder->getUrl('worldpay/redirectresult/pending'));
        $paymentTypeElement->addChild('failureURL', $this->_urlBuilder->getUrl('worldpay/redirectresult/failure'));
    }

    protected function _addShopperElement(SimpleXMLElement $order): void
    {
        $shopper = $order->addChild(self::TOKEN_SCOPE);

        $shopper->addChild('shopperEmailAddress', $this->orderParameters['shopperEmail']);

        $browser = $shopper->addChild('browser');

        $acceptHeader = $browser->addChild('acceptHeader');
        $this->_addCDATA($acceptHeader, $this->orderParameters['acceptHeader']);

        $userAgentHeader = $browser->addChild('userAgentHeader');
        $this->_addCDATA($userAgentHeader, $this->orderParameters['userAgentHeader']);

        $browserFields = $this->orderParameters['browserFields'];
        $browser->addChild('browserColourDepth', $browserFields['browser_colorDepth']);
        $browser->addChild('browserScreenHeight', $browserFields['browser_screenHeight']);
        $browser->addChild('browserScreenWidth', $browserFields['browser_screenWidth']);
    }

    protected function _addCustomerRiskData(SimpleXMLElement $order): void
    {
        $riskData = $order->addChild('riskData');
        $accountCreatedDate = strtotime($this->cusDetails['created_at']);
        $accountUpdatedDate = strtotime($this->cusDetails['updated_at']);

        $orderCreateDate = strtotime($this->cusDetails['order_details']['created_at']);
        $orderUpdateDate = strtotime($this->cusDetails['order_details']['updated_at']);

        $shippingNameMatchesAccountName = (isset($this->orderParameters['shippingAddress']) &&
            $this->orderParameters['billingAddress']['firstName'] == $this->orderParameters['shippingAddress']['firstName'])
            ? 'true' : 'false';

        //Authentication risk data
        $authenticationRiskData = $riskData->addChild('authenticationRiskData');
        $authenticationRiskData['authenticationMethod'] = !empty($this->orderParameters['shopperId'])? 'localAccount' : 'guestCheckout';
        $authenticationTimestampElement = $authenticationRiskData->addChild('authenticationTimestamp');
        $dateElement = $authenticationTimestampElement->addChild('date');
        $dateElement['second'] = date("s");
        $dateElement['minute'] = date("i");
        $dateElement['hour'] = date("H");
        $dateElement['dayOfMonth'] = date("d");
        $dateElement['month'] = date("m");
        $dateElement['year'] = date("Y");

        //shoppper account risk data
        $shopperAccountRiskData = $riskData->addChild('shopperAccountRiskData');
        $shopperAccountRiskData['transactionsAttemptedLastDay'] = $this->cusDetails['order_count']['last_day_count'];
        $shopperAccountRiskData['transactionsAttemptedLastYear'] = $this->cusDetails['order_count']['last_year_count'];
        $shopperAccountRiskData['purchasesCompletedLastSixMonths'] = $this->
        cusDetails['order_count']['last_six_months_count'];
        $shopperAccountRiskData['addCardAttemptsLastDay'] = $this->cusDetails['card_count'];
        $shopperAccountRiskData['previousSuspiciousActivity'] = 'false';
        $shopperAccountRiskData['shippingNameMatchesAccountName'] = $shippingNameMatchesAccountName;
        $shopperAccountRiskData['shopperAccountAgeIndicator'] = $this->cusDetails['shopperAccountAgeIndicator'];
        $shopperAccountRiskData['shopperAccountChangeIndicator'] = $this->cusDetails['shopperAccountChangeIndicator'];
        $shopperAccountRiskData['shopperAccountPasswordChangeIndicator'] = $this->
        cusDetails['shopperAccountPasswordChangeIndicator'];
        $shopperAccountRiskData['shopperAccountShippingAddressUsageIndicator'] = $this->
        cusDetails['shopperAccountShippingAddressUsageIndicator'];
        $shopperAccountRiskData['shopperAccountPaymentAccountIndicator'] = $this->
        cusDetails['shopperAccountPaymentAccountIndicator'];

        $shopperAccountRiskDataElement = $shopperAccountRiskData->addChild('shopperAccountCreationDate');
        $shopperAccountRiskDataElementChild = $shopperAccountRiskDataElement->addChild('date');
        $shopperAccountRiskDataElementChild['dayOfMonth'] = date("d", $accountCreatedDate);
        $shopperAccountRiskDataElementChild['month'] = date("m", $accountCreatedDate);
        $shopperAccountRiskDataElementChild['year'] = date("Y", $accountCreatedDate);

        $shopperAccountRiskDataElement1 = $shopperAccountRiskData->addChild('shopperAccountModificationDate');
        $shopperAccountRiskDataElementChild1 = $shopperAccountRiskDataElement1->addChild('date');
        $shopperAccountRiskDataElementChild1['dayOfMonth'] = date("d", $accountUpdatedDate);
        $shopperAccountRiskDataElementChild1['month'] = date("m", $accountUpdatedDate);
        $shopperAccountRiskDataElementChild1['year'] = date("Y", $accountUpdatedDate);

        $shopperAccountPasswordChangeAttribute = $shopperAccountRiskData->addChild('shopperAccountPasswordChangeDate');
        $shopperAccountPasswordChangeElement = $shopperAccountPasswordChangeAttribute->addChild('date');
        $shopperAccountPasswordChangeElement['dayOfMonth'] = date("d", $accountUpdatedDate);
        $shopperAccountPasswordChangeElement['month'] = date("m", $accountUpdatedDate);
        $shopperAccountPasswordChangeElement['year'] = date("Y", $accountUpdatedDate);

        $shopperAccountShippingAddressAttribute = $shopperAccountRiskData->
        addChild('shopperAccountShippingAddressFirstUseDate');
        $shopperAccountShippingAddressElement = $shopperAccountShippingAddressAttribute->addChild('date');
        $shopperAccountShippingAddressElement['dayOfMonth'] = date("d", $orderCreateDate);
        $shopperAccountShippingAddressElement['month'] = date("m", $orderCreateDate);
        $shopperAccountShippingAddressElement['year'] = date("Y", $orderCreateDate);

        $shopperAccountPaymentAccountFirstUseDateAttribute = $shopperAccountRiskData->
        addChild('shopperAccountPaymentAccountFirstUseDate');
        $shopperAccountPaymentAccountFirstUseDateElement = $shopperAccountPaymentAccountFirstUseDateAttribute->
        addChild('date');
        $shopperAccountPaymentAccountFirstUseDateElement['dayOfMonth'] = date("d", $orderUpdateDate);
        $shopperAccountPaymentAccountFirstUseDateElement['month'] = date("m", $orderUpdateDate);
        $shopperAccountPaymentAccountFirstUseDateElement['year'] = date("Y", $orderUpdateDate);

        // Transaction Risk Data
        $transactionRiskData = $riskData->addChild('transactionRiskData');
        $transactionRiskData['shippingMethod'] = 'other';
        /* Set Delivery time if exists */
        //$transactionRiskData['deliveryTimeframe'] = '';
        $transactionRiskData['deliveryEmailAddress'] = $this->orderParameters['shopperEmail'];
        $transactionRiskData['reorderingPreviousPurchases'] = $this->cusDetails['order_details']['previous_purchase'];
        $transactionRiskData['preOrderPurchase'] = 'false';
        $transactionRiskData['giftCardCount'] = 0;
    }

    private function _addPrimeRoutingElement(SimpleXMLElement $order): void
    {
        $primeRouting = $order->addChild('primeRoutingRequest');
        $routingPreference = $this->orderParameters['primeRoutingData']['routing_preference'];
        $primeRouting->addChild('routingPreference', $routingPreference);
        $debitNetworks = $this->orderParameters['primeRoutingData']['debit_networks'];
        if (!empty($debitNetworks)) {
            $preferredNetworks = $primeRouting->addChild('preferredNetworks');
            foreach ($debitNetworks as $key => $network) {
                $preferredNetworks->addChild('networkName', $network);
            }

        }
    }
}
