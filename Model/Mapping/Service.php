<?php

namespace Sapient\Worldpay\Model\Mapping;
use Sapient\Worldpay\Model\SavedTokenFactory;
class Service {

    protected $_logger;
    protected $savedTokenFactory;

    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        SavedTokenFactory $savedTokenFactory,
        \Sapient\Worldpay\Model\SavedToken $savedtoken,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->wplogger = $wplogger;
        $this->savedTokenFactory = $savedTokenFactory;
        $this->worldpayHelper = $worldpayHelper;
        $this->customerSession = $customerSession;
        $this->savedtoken = $savedtoken;
        $this->_urlBuilder = $urlBuilder;
    }

    public function collectDirectOrderParameters(
        $orderCode,
        $quote,
        $orderStoreId,
        $paymentDetails
    )
    {
        $reservedOrderId = $quote->getReservedOrderId();
        return array(
            'orderCode'        => $orderCode,
            'merchantCode'     => $this->worldpayHelper->getMerchantCode($paymentDetails['additional_data']['cc_type']),
            'orderDescription' => $this->_getOrderDescription($reservedOrderId),
            'currencyCode'     => $quote->getQuoteCurrencyCode(),
            'amount'           => $quote->getGrandTotal(),
            'paymentDetails'   => $this->_getPaymentDetails($paymentDetails),
            'cardAddress'      => $this->_getCardAddress($quote),
            'shopperEmail'     => $quote->getCustomerEmail(),
            'threeDSecureConfig' => $this->_getThreeDSecureConfig($paymentDetails['method']),
            'tokenRequestConfig' => $this->_getTokenRequestConfig($paymentDetails),
            'acceptHeader'     => php_sapi_name() !== "cli" ? $_SERVER['HTTP_ACCEPT'] : '',
            'userAgentHeader'  => php_sapi_name() !== "cli" ? $_SERVER['HTTP_USER_AGENT'] : '',
            'shippingAddress'  => $this->_getShippingAddress($quote),
            'billingAddress'   => $this->_getBillingAddress($quote),
            'method'           => $paymentDetails['method'],
            'orderStoreId'     => $orderStoreId,
            'shopperId'     => $quote->getCustomerId()
        );
    }

    public function collectRedirectOrderParameters(
        $orderCode,
        $quote,
        $orderStoreId,
        $paymentDetails
    )
    {
        $reservedOrderId = $quote->getReservedOrderId();
        return array(
            'orderCode'           => $orderCode,
            'merchantCode'        => $this->worldpayHelper->getMerchantCode($paymentDetails['additional_data']['cc_type']),
            'orderDescription'    => $this->_getOrderDescription($reservedOrderId),
            'currencyCode'        => $quote->getQuoteCurrencyCode(),
            'amount'              => $quote->getGrandTotal(),
            'paymentType'         => $this->_getRedirectPaymentType($paymentDetails),
            'shopperEmail'        => $quote->getCustomerEmail(),
            'threeDSecureConfig'  => $this->_getThreeDSecureConfig(),
            'tokenRequestConfig'  => $this->_getTokenRequestConfig($paymentDetails),
            'acceptHeader'        => php_sapi_name() !== "cli" ? $_SERVER['HTTP_ACCEPT'] : '',
            'userAgentHeader'     => php_sapi_name() !== "cli" ? $_SERVER['HTTP_USER_AGENT'] : '',
            'shippingAddress'     => $this->_getShippingAddress($quote),
            'billingAddress'      => $this->_getBillingAddress($quote),
            'method'              => $paymentDetails['method'],
            'paymentPagesEnabled' => $this->worldpayHelper->getCustomPaymentEnabled(),
            'installationId'      => $this->worldpayHelper->getInstallationId(),
            'hideAddress'         => $this->worldpayHelper->getHideAddress(),
            'shopperId'           => $quote->getCustomerId(),
            'orderStoreId'        => $orderStoreId
        );
    }

       public function collectKlarnaOrderParameters(
        $orderCode,
        $quote,
        $orderStoreId,
        $paymentDetails
    )
    {
        $reservedOrderId = $quote->getReservedOrderId();
        return array(
            'orderCode'           => $orderCode,
            'merchantCode'        => $this->worldpayHelper->getMerchantCode($paymentDetails['additional_data']['cc_type']),
            'orderDescription'    => $this->_getOrderDescription($reservedOrderId),
            'currencyCode'        => $quote->getQuoteCurrencyCode(),
            'amount'              => $quote->getGrandTotal(),
            'paymentType'         => $this->_getRedirectPaymentType($paymentDetails),
            'shopperEmail'        => $quote->getCustomerEmail(),
            'threeDSecureConfig'  => $this->_getThreeDSecureConfig(),
            'tokenRequestConfig'  => $this->_getTokenRequestConfig($paymentDetails),
            'acceptHeader'        => php_sapi_name() !== "cli" ? $_SERVER['HTTP_ACCEPT'] : '',
            'userAgentHeader'     => php_sapi_name() !== "cli" ? $_SERVER['HTTP_USER_AGENT'] : '',
            'shippingAddress'     => $this->_getShippingAddress($quote),
            'billingAddress'      => $this->_getBillingAddress($quote),
            'method'              => $paymentDetails['method'],
            'paymentPagesEnabled' => $this->worldpayHelper->getCustomPaymentEnabled(),
            'installationId'      => $this->worldpayHelper->getInstallationId(),
            'hideAddress'         => $this->worldpayHelper->getHideAddress(),
            'orderLineItems'      => $this->_getOrderLineItems($quote),
            'orderStoreId'        => $orderStoreId
        );
    }

    public function collectTokenOrderParameters(
        $orderCode,
        $quote,
        $orderStoreId,
        $paymentDetails
    )
    {
        $reservedOrderId = $quote->getReservedOrderId();
        $updatedPaymentDetails = $this->_getPaymentDetailsUsingToken($paymentDetails, $quote);
        return array(
            'orderCode'        => $orderCode,
            'merchantCode'       => $this->worldpayHelper->getMerchantCode($updatedPaymentDetails['brand']),
            'orderDescription'   => $this->_getOrderDescription($reservedOrderId),
            'currencyCode'       => $quote->getQuoteCurrencyCode(),
            'amount'             => $quote->getGrandTotal(),
            'paymentDetails'     => $updatedPaymentDetails,
            'cardAddress'        => $this->_getCardAddress($quote),
            'shopperEmail'       => $quote->getCustomerEmail(),
            'threeDSecureConfig' => $this->_getThreeDSecureConfig($paymentDetails['method']),
            'tokenRequestConfig' =>  $this->_getTokenRequestConfig($paymentDetails),
            'acceptHeader'       => $_SERVER['HTTP_ACCEPT'],
            'userAgentHeader'    => $_SERVER['HTTP_USER_AGENT'],
            'shippingAddress'    => $this->_getShippingAddress($quote),
            'billingAddress'     => $this->_getBillingAddress($quote),
            'method'             => $paymentDetails['method'],
            'orderStoreId'       => $orderStoreId,
            'shopperId'          => $quote->getCustomerId()
        );
    }

    public function collectPaymentOptionsParameters(
        $countryId,
        $paymenttype
        ){
         return array(
                'merchantCode'  => $this->worldpayHelper->getMerchantCode($paymenttype),
                'countryCode'   => $countryId,
                'paymentType'   => $paymenttype
            );
    }

    private function _getTokenRequestConfig($paymentDetails)
    {
        if(isset($paymentDetails['additional_data']['save_my_card'])){
            return $paymentDetails['additional_data']['save_my_card'];
        }
    }

    private function _getThreeDSecureConfig($method = null)
    {
        $threedarray =  array(
            'isDynamic3D'=> (bool)$this->worldpayHelper->isDynamic3DEnabled(),
            'is3DSecure' => (bool)$this->worldpayHelper->is3DSecureEnabled()
        );

        if ($method == 'worldpay_moto') {
             $threedarray =  array(
                'isDynamic3D'=> false,
                'is3DSecure' => false
            );
        }
        return $threedarray;
    }

    private function _getShippingAddress($quote)
    {
        $shippingaddress = $this->_getAddress($quote->getShippingAddress());
        if(!array_filter($shippingaddress)){
            $shippingaddress = $this->_getAddress($quote->getBillingAddress());
        }
        return $shippingaddress;
    }

    private function _getBillingAddress($quote)
    {
        return $this->_getAddress($quote->getBillingAddress());
    }

    private function _getOrderLineItems($quote)
    {
         $orderitems = array();
         $orderitems['orderTaxAmount'] = $quote->getShippingAddress()->getData('tax_amount');
         $orderitems['termsURL'] = $this->_urlBuilder->getUrl();
         $lineitem = array();
           $orderItems = $quote->getItemsCollection();
          foreach ($orderItems as $_item) {
            $lineitem = array();
            if ($_item->getParentItem()){
                continue;
            }else{
                $rowtotal = $_item->getRowTotal();
                $totalamount = $rowtotal - $_item->getDiscountAmount();
                $totaltax = $_item->getTaxAmount() + $_item->getHiddenTaxAmount() + $_item->getWeeeTaxAppliedRowAmount();
                $discountamount = $_item->getDiscountAmount();

                $lineitem['reference'] = $_item->getProductId();
                $lineitem['name'] = $_item->getName();
                $lineitem['quantity'] = (int)$_item->getQty();
                $lineitem['quantityUnit'] = $this->worldpayHelper->getQuantityUnit($_item->getProduct());
                $lineitem['unitPrice'] = $rowtotal / $_item->getQty();
                $lineitem['taxRate'] =  (int)$_item->getTaxPercent();
                $lineitem['totalAmount'] = $totalamount;
                $lineitem['totalTaxAmount'] =$totaltax;
                if($discountamount > 0){
                     $lineitem['totalDiscountAmount'] = $discountamount;
                }
                $orderitems['lineItem'][] = $lineitem;
            }
          }

          $lineitem = array();
          $address = $quote->getShippingAddress();
           if($address->getShippingAmount() > 0){
                $lineitem['reference'] = 'Shipid';
                $lineitem['name'] = 'Shipping amount';
                $lineitem['quantity'] = 1;
                $lineitem['quantityUnit'] = 'shipping';
                $lineitem['unitPrice'] = $address->getShippingAmount();
                $lineitem['totalAmount'] = $address->getShippingAmount() - $address->getShippingDiscountAmount();
                $totaltax = $address->getShippingTaxAmount() + $address->getShippingHiddenTaxAmount();
                $lineitem['totalTaxAmount'] = $totaltax;
                $lineitem['taxRate'] =  (int)(($totaltax * 100)/$address->getShippingAmount());
                if($address->getShippingDiscountAmount() > 0){
                    $lineitem['totalDiscountAmount'] = $address->getShippingDiscountAmount();
                }
                $orderitems['lineItem'][] = $lineitem;
           }
          return $orderitems;
    }


    private function _getAddress($address)
    {
        return array(
            'firstName'   => $address->getFirstname(),
            'lastName'    => $address->getLastname(),
            'street'      => $address->getData('street'),
            'postalCode'  => $address->getPostcode(),
            'city'        => $address->getCity(),
            'countryCode' => $address->getCountryId(),
        );
    }

    private function _getCardAddress($quote)
    {
        return $this->_getAddress($quote->getBillingAddress());
    }

     private function _getPaymentDetails($paymentDetails)
    {
        $method = $paymentDetails['method'];

        if ($paymentDetails['additional_data']['cse_enabled']) {
            $details = array(
                'cseEnabled' => $paymentDetails['additional_data']['cse_enabled'],
                'encryptedData' => $paymentDetails['additional_data']['encryptedData'],
                'paymentType' => $paymentDetails['additional_data']['cc_type'],
            );
        } else {
            $details = array(
                'paymentType' => $paymentDetails['additional_data']['cc_type'],
                'cardNumber' => $paymentDetails['additional_data']['cc_number'],
                'expiryMonth' => $paymentDetails['additional_data']['cc_exp_month'],
                'expiryYear' => $paymentDetails['additional_data']['cc_exp_year'],
                'cardHolderName' => $paymentDetails['additional_data']['cc_name'],
                'cseEnabled' => $paymentDetails['additional_data']['cse_enabled'],
            );

            if (isset($paymentDetails['additional_data']['cc_cid'])) {
                $details['cvc'] = $paymentDetails['additional_data']['cc_cid'];
            }
        }
        $this->customerSession->setIsSavedCardRequested(false);
        if (isset($paymentDetails['additional_data']['save_my_card']) && $paymentDetails['additional_data']['save_my_card']) {
            $this->customerSession->setIsSavedCardRequested(true);
        }
        $details['sessionId'] = session_id();
        $details['shopperIpAddress'] = $this->_getClientIPAddress();
        $details['dynamicInteractionType'] = $this->worldpayHelper->getDynamicIntegrationType($method);

        return $details;
    }
    private function _getRedirectPaymentType($paymentDetails)
    {
        if ('CARTEBLEUE-SSL' == $paymentDetails['additional_data']['cc_type']) {
            return 'ECMC-SSL';
        }
        return $paymentDetails['additional_data']['cc_type'];
    }

    private function _getOrderDescription($reservedOrderId)
    {
        return 'Magento 2 Order for ' . $reservedOrderId;
    }

    private function _getPaymentDetailsUsingToken($paymentDetails,$quote)
    {
        if (isset($paymentDetails['encryptedData'])) {
            $details = array(
                'encryptedData' => $paymentDetails['encryptedData']
            );
        } else {
            $savedCardData = $this->savedtoken->loadByTokenCode($paymentDetails['additional_data']['tokenCode']);
            $details = array(
                'brand' => $savedCardData->getCardBrand(),
                'paymentType' => 'TOKEN-SSL',
                'customerId' => $quote->getCustomerId(),
                'tokenCode' => $savedCardData->getTokenCode(),
            );

            if (isset($paymentDetails['additional_data']['saved_cc_cid']) && !empty($paymentDetails['additional_data']['saved_cc_cid'])) {
                $details['cvc'] = $paymentDetails['additional_data']['saved_cc_cid'];
            }
        }

        $details['sessionId'] = session_id();
        $details['shopperIpAddress'] = $this->_getClientIPAddress();
        $details['dynamicInteractionType'] = $this->worldpayHelper->getDynamicIntegrationType($paymentDetails['method']);
        return $details;
    }

    private function _getClientIPAddress()
    {
        $remoteAddresses = explode(',', $_SERVER['REMOTE_ADDR']);
        return trim($remoteAddresses[0]);
    }

}
