<?php

namespace Sapient\Worldpay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Sapient\Worldpay\Helper\ProductOnDemand;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Sapient\Worldpay\Helper\Data as WorldpayHelper;
use Sapient\Worldpay\Model\SavedToken;
use Sapient\Worldpay\Model\Recurring\Subscription\Address as SubscriptionAddress;
use Magento\Store\Model\StoreManagerInterface;
use Sapient\Worldpay\Helper\CurlHelper;
use Sapient\Worldpay\Model\ResourceModel\ProductOnDemand\Order as ProductOnDemandOrderResource;
use Sapient\Worldpay\Model\Worldpayment;
use Magento\SalesSequence\Model\Manager as SequenceManager;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Sapient\Worldpay\Model\Request\PaymentServiceRequest;
use Magento\Framework\Session\SessionManagerInterface;
use Sapient\Worldpay\Model\Payment\StateXmlFactory;
use Sapient\Worldpay\Model\XmlBuilder\DirectOrder;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

class InvoiceGenerated implements ObserverInterface
{
    public const LESS_THAN_THIRTY_DAYS = 'lessThanThirtyDays';
    public const THIRTY_TO_SIXTY_DAYS = 'thirtyToSixtyDays';
    public const MORE_THAN_SIXTY_DAYS = 'moreThanSixtyDays';
    public const DURING_TRANSACTION = 'duringTransaction';
    public const CREATED_DURING_TRANSACTION = 'createdDuringTransaction';
    public const CHANGED_DURING_TRANSACTION = 'changedDuringTransaction';
    public const NO_ACCOUNT = 'noAccount';
    public const NO_CHANGE = 'noChange';
    public const THIS_TRANSACTION = 'thisTransaction';

    public function __construct(
        private WorldpayHelper $worldpayhelper,
        private SavedToken $worldpaySavedToken,
        private SubscriptionAddress $addressCollectionFactory,
        private ProductOnDemand $productOnDemandHelper,
        private StoreManagerInterface $_storeManager,
        private CurlHelper $curlHelper,
        private ProductOnDemandOrderResource $transactionCollection,
        private Worldpayment $worldpayPayment,
        private SequenceManager $sequenceManager,
        private CustomerSession $customerSession,
        private CustomerRepositoryInterface $customerRepository,
        private ScopeConfigInterface $scopeConfig,
        private PaymentServiceRequest $paymentservicerequest,
        private SessionManagerInterface $session,
        private StateXmlFactory $stateXmlFactory,
    ) {
    }

    public function getZeroAuthOrder($incrementId)
    {
        return $this->transactionCollection->getZeroAuthOrder($incrementId);
    }

    public function getTokenInfo($tokenId, $customerId)
    {
        $today = date("Y-m-d");
        if ($tokenId) {
            return $this->worldpaySavedToken->getCollection()
                ->addFieldToFilter('id', ['eq' => trim($tokenId)])
                ->addFieldToFilter('customer_id', ['eq' => trim($customerId)])
                ->addFieldToFilter('token_expiry_date', ['gteq' => $today])->getData();
        }
    }

    /**
     * Get AddressInfo
     *
     * @param Int $subscriptionId
     */

    public function getAddressInfo($subscriptionId)
    {
        if ($subscriptionId) {
            $result = $this->addressCollectionFactory->getCollection()
                ->addFieldToFilter('subscription_id', ['eq' => trim($subscriptionId)])->getData();
            return $result;
        }
    }

    public function getReservedOrderId()
    {
        return $this->sequenceManager->getSequence(
            Order::ENTITY,
            Order::ENTITY,
            $this->_storeManager->getStore()->getId()
        )
            ->getNextValue();
    }

    public function execute(Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $worldpayTokenId = $this->getZeroAuthOrder($invoice->getOrder()->getIncrementId());
        if (isset($worldpayTokenId['worldpay_token_id'])) {
            $order = $invoice->getOrder();
            $worldPayPayment = $this->worldpayPayment->loadByPaymentId($order->getIncrementId());
            $newWorldpayOrderId = $order->getIncrementId() . '-' . time();
            $oldWorldpayPaymentId = $worldPayPayment->getWorldpayOrderId();
            $worldPayPayment->unsetData('id');
            $worldPayPayment->setData('worldpay_order_id', $newWorldpayOrderId);
            $worldPayPayment->setHasDataChanges(true);
            $worldPayPayment->save();

            $this->productOnDemandHelper->_createWorldpayPayOnDemand($order->getIncrementId(), $newWorldpayOrderId, $worldpayTokenId['worldpay_token_id'], false);

            $this->buildProductOnDemandOrder(
                $invoice,
                $worldpayTokenId['worldpay_token_id'],
                $worldPayPayment,
                $newWorldpayOrderId,
                $oldWorldpayPaymentId,
            );
        }

        return $this;
    }

    public function getShopperAccountAgeIndicator($fromDate, $toDate, $differenceFormat = '%a')
    {
        $days = $this->getDaysBetween($fromDate, $toDate, $differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            return !empty($this->customerSession->getCustomer()->getId())
                ? self::CREATED_DURING_TRANSACTION : self::NO_ACCOUNT;
        }
    }

    public function getShopperAccountChangeIndicator($fromDate, $toDate, $differenceFormat = '%a')
    {
        $days = $this->getDaysBetween($fromDate, $toDate, $differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            return self::CHANGED_DURING_TRANSACTION;
        }
    }

    public function getShopperAccountPasswordChangeIndicator($fromDate, $toDate, $differenceFormat = '%a')
    {
        $days = $this->getDaysBetween($fromDate, $toDate, $differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            return !empty($this->customerSession->getCustomer()->getId())
                ? self::CHANGED_DURING_TRANSACTION : self::NO_CHANGE;
        }
    }

    public function getShopperAccountShippingAddressUsageIndicator(
        $fromDate,
        $toDate,
        $differenceFormat = '%a'
    ) {
        $days = $this->getDaysBetween($fromDate, $toDate, $differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            return self::THIS_TRANSACTION;
        }
    }

    public function getShopperAccountPaymentAccountIndicator($fromDate, $toDate, $differenceFormat = '%a')
    {
        $days = $this->getDaysBetween($fromDate, $toDate, $differenceFormat);
        if ($days > 0 && $days < 30) {
            return self::LESS_THAN_THIRTY_DAYS;
        } elseif ($days > 30 && $days < 60) {
            return self::THIRTY_TO_SIXTY_DAYS;
        } elseif ($days > 60) {
            return self::MORE_THAN_SIXTY_DAYS;
        } else {
            return !empty($this->customerSession->getCustomer()->getId())
                ? self::DURING_TRANSACTION : self::NO_ACCOUNT;
        }
    }

    private function getDaysBetween(string $fromDate, string $toDate, string $differenceFormat = '%a'): string
    {
        $datetime1 = date_create($fromDate);
        $datetime2 = date_create($toDate);
        return date_diff($datetime1, $datetime2)->format($differenceFormat);
    }

    private function _getAddress($address)
    {
        return [
            'firstName' => $address->getFirstname(),
            'lastName' => $address->getLastname(),
            'street' => $address->getData('street'),
            'postalCode' => $address->getPostcode(),
            'city' => $address->getCity(),
            'countryCode' => $address->getCountryId(),
        ];
    }

    public function orderPayment($tokenKey, $paymentData)
    {
        $token = 'Bearer '.$tokenKey;
        $apiUrl = $this->_storeManager->getStore()->getUrl('rest/default/V1/carts/mine/');
        $response = $this->curlHelper->sendCurlRequest(
            $apiUrl,
            [
                CURLOPT_URL => $apiUrl.'payment-information',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS =>$paymentData,
                CURLOPT_HTTPHEADER => [
                    "Authorization: $token",
                    "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 "
                    . "(KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36",
                    "Content-Type: application/json"
                ],
            ]
        );
        return json_decode($response ?? '', true);
    }

    private function getExemptionEngineDetails(): array
    {
        $exemptionEngine = [];
        $exemptionEngine['enabled'] = $this->scopeConfig->
        getValue(
            'worldpay/exemption_config/exemption_engine',
            ScopeInterface::SCOPE_STORE
        );
        $exemptionEngine['placement'] = $this->scopeConfig->
        getValue(
            'worldpay/exemption_config/exemption_engine_placement',
            ScopeInterface::SCOPE_STORE
        );
        $exemptionEngine['type'] = $this->scopeConfig->
        getValue(
            'worldpay/exemption_config/exemption_engine_type',
            ScopeInterface::SCOPE_STORE
        );
        return $exemptionEngine;
    }

    private function getPrimeRoutingDetails($countryCode)
    {
        if ($countryCode==='US') {
            if ($this->worldpayhelper->isPrimeRoutingEnabled()
                && $this->worldpayhelper->isAdvancedPrimeRoutingEnabled()) {
                $details['primerouting'] = $this->worldpayhelper->isPrimeRoutingEnabled();
                $details['advanced_primerouting'] = $this->worldpayhelper->isAdvancedPrimeRoutingEnabled();
                $details['routing_preference'] = $this->worldpayhelper->getRoutingPreference();
                $details['debit_networks'] = $this->worldpayhelper->getDebitNetworks();
                return $details;
            } elseif ($this->worldpayhelper->isPrimeRoutingEnabled()) {
                $details['primerouting'] = $this->worldpayhelper->isPrimeRoutingEnabled();

                return $details;
            }
        }
    }

    private function _getThreeDSecureConfig()
    {
        return [
            'isDynamic3D' => (bool) $this->worldpayhelper->isDynamic3DEnabled(),
            'is3DSecure' => (bool) $this->worldpayhelper->is3DSecureEnabled()
        ];
    }

    public function buildProductOnDemandOrder(
        $invoice,
        $worldpayTokenId,
        Worldpayment $worldPayPayment,
        string $newWorldpayOrderId, $oldWorldpayPaymentId): void
    {
        $order = $invoice->getOrder();
        $data = $this->getTokenInfo($worldpayTokenId, $invoice->getOrder()->getCustomerId());
        $REMOTE_ADDR = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP) ?? '';

        $remoteAddresses = explode(',', $REMOTE_ADDR);

        $shopperIdAddress = trim($remoteAddresses[0]);
        $paymentDetails = [
            'tokenCode' => $data[0]['token_code'],
            'paymentType' => 'TOKEN-SSL',
            'token_type' => $this->worldpayhelper->getMerchantTokenization(),
            'sessionId' => $this->session->getSessionId(),
            'shopperIpAddress'=> $shopperIdAddress,
            'isEnabledEFTPOS' => $this->worldpayhelper->isEnabledEFTPOS(),
            'dynamicInteractionType' => $this->worldpayhelper->getDynamicIntegrationType($worldPayPayment->getPaymentType()),
            'transactionIdentifier' => $worldPayPayment->getTlid() ?? $data[0]['transaction_identifier'],
            'customerId' => $order->getCustomerId(),
            'isPaymentForProductOnDemand' => true,
            'zero_auth_payment' => false,
            'zero_auth_order' => false,
        ];

        $customer = $this->customerRepository->getById($order->getCustomerId());

        $orderDetails = $this->worldpayhelper->getOrderDetailsByEmailId($customer->getEmail());
        $orderDetails['created_at'] = $order->getCreatedAt();
        $orderDetails['updated_at'] = $order->getUpdatedAt();
        $orderDetails['previous_purchase'] = !empty($order->getUpdatedAt()) ? 'true' : 'false';
        $now = new \DateTime();

        $cusDetails = [
            'created_at' => $customer->getCreatedAt(),
            'updated_at' => $customer->getUpdatedAt(),
            'shipping_amount' => $order->getShippingAmount(),
            'discount_amount' => $order->getBaseDiscountAmount(),
            'order_details' => $orderDetails,
            'order_count' => $this->worldpayhelper->getOrdersCountByEmailId($customer->getEmail()),
            'card_count' => $this->worldpayhelper->getSavedCardsCount($order->getCustomerId()),
            'shipping_method' => $order->getShippingMethod(),
            'shopperName' => $order->getBillingAddress()->getFirstname(),
            'shopperId' => $order->getCustomerId(),
            'birthDate' => $customer->getDob(),
            'shopperAccountAgeIndicator' =>
                $this->getshopperAccountAgeIndicator($customer->getCreatedAt(), $now->format('Y-m-d H:i:s')),
            'shopperAccountChangeIndicator' =>
                $this->getShopperAccountChangeIndicator($customer->getUpdatedAt(), $now->format('Y-m-d H:i:s')),
            'shopperAccountPasswordChangeIndicator' =>
                $this->getShopperAccountPasswordChangeIndicator($customer->getUpdatedAt(), $now->format('Y-m-d H:i:s')),
            'shopperAccountShippingAddressUsageIndicator' =>
                $this->getShopperAccountShippingAddressUsageIndicator($orderDetails['created_at'], $now->format('Y-m-d H:i:s')),
            'shopperAccountPaymentAccountIndicator' =>
                $this->getShopperAccountPaymentAccountIndicator($orderDetails['created_at'], $now->format('Y-m-d H:i:s')),
        ];

        $requestConfiguration = [
            'threeDSecureConfig' =>  $this->_getThreeDSecureConfig(),
            'tokenRequestConfig' => 0,
            'shopperId' => $order->getCustomerId(),
        ];
        $thirdPartyData = '';
        $pluginTrackerDetails = $this->worldpayhelper->getPluginTrackerdetails();
        $pluginTrackerDetails['additional_details']['transaction_method'] = $worldPayPayment->getPaymentType();
        $orderContent = json_encode($pluginTrackerDetails);

        $xmlDirectOrder = new DirectOrder(
            $this->customerSession,
            $this->worldpayhelper,
            $requestConfiguration
        );

        $orderSimpleXml = $xmlDirectOrder->build(
            $worldPayPayment->getMerchantId(),
            $newWorldpayOrderId,
            'Payment for order ' . $oldWorldpayPaymentId,
            $order->getOrderCurrencyCode(),
            $invoice->getGrandTotal(),
            $orderContent,
            $paymentDetails,
            $this->_getAddress($order->getBillingAddress()),
            $order->getCustomerEmail(),
            php_sapi_name() !== "cli" ? filter_input(INPUT_SERVER, 'HTTP_ACCEPT') : '',
            php_sapi_name() !== "cli" ?
                filter_input(
                    INPUT_SERVER,
                    'HTTP_USER_AGENT',
                    FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                    FILTER_FLAG_STRIP_LOW
                ) : '',

            $this->_getAddress($order->getShippingAddress()),
            $this->_getAddress($order->getBillingAddress()),
            $order->getCustomerId(),
            1,
            '',
            1,
            $cusDetails,
            $this->getExemptionEngineDetails(),
            $thirdPartyData,
            $order->getShippingAmount(),
            $this->worldpayhelper->getCurrencyExponent($order->getOrderCurrencyCode()),
            $this->getPrimeRoutingDetails($order->getBillingAddress()->getCountryId()),
            '',
            $this->worldpayhelper->getCaptureDelayValues(),
            [
                'browser_screenHeight' => "",
                'browser_screenWidth' => "",
                'browser_colorDepth' => ""
            ],
            $order->getBillingAddress()->getTelephone()
        );

        $response = $this->paymentservicerequest->_sendRequest(
            dom_import_simplexml($orderSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($worldPayPayment->getPaymentType()),
            $this->worldpayhelper->getXmlPassword($worldPayPayment->getPaymentType()),
        );

        $this->updatePaymentTlid($worldPayPayment, $response);
    }

    private function updatePaymentTlid(Worldpayment $worldPayPayment, $response): void
    {
        if (empty($response)) {
            return;
        }

        $stateXml = $this->stateXmlFactory->create(['xml' => new \SimpleXMLElement($response)]);
        $tlid = $stateXml->getTlid();
        if (!empty($tlid)) {
            $worldPayPayment->setData('tlid', $tlid);
            $worldPayPayment->save();
        }
    }
}
