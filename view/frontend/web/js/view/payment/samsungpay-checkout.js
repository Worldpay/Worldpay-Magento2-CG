define(
    [
'jquery',
'ko',
'uiComponent',
'underscore',
'mage/translate',
'Magento_Checkout/js/model/quote',
'Magento_Customer/js/customer-data',
'Sapient_Worldpay/js/model/checkout-utils',
'samsungPay',
'Magento_Checkout/js/model/url-builder',
'mage/url',
'Magento_Customer/js/model/customer'
], function ($, ko, Component, _, $t, quote, customerData, checkoutUtils, samsungPay, urlBuilder, url,customer) {
'use strict';
      
    
    var paymentService = false;
    var billingAddressCountryId = "";    
    var paymentToken = "";
    var merchantId = '';
    var response = '';
    var response1 = '';
    var dfReferenceId = "";
    var debug = true;

return Component.extend({
    defaults: {
        template: 'Sapient_Worldpay/payment/wallets/samsungpay-checkout'
    },

    /**
     * @returns {*}
     */
    initialize: function () {
        this._super();
        var self = this;
        return this;
    },
    performPlaceOrder:  function () {
        var quoteId = window.checkoutConfig.quoteData.entity_id;                    
        var linkUrl = url.build('worldpay/samsungpay/index?quoteId=' + quoteId);                         
        var xhttp = new XMLHttpRequest();
        xhttp.open("GET", linkUrl, false);
        xhttp.send();   
        var response = JSON.parse(xhttp.responseText);                                           
        response1 = JSON.parse(response);
        if(response1.resultMessage == 'SUCCESS') {            
            var maskedQuoteId = "";
            if(!customer.isLoggedIn()){
                maskedQuoteId = quote.getQuoteId();
                quote.billingAddress().email=quote.guestEmail;
            }
            var shippingrequired = false;
            if(quote.shippingMethod()){
                shippingrequired = true;
            }
            var checkoutData = {
                billingAddress : quote.billingAddress(),
                shippingAddress: quote.shippingAddress(),
                shippingMethod: quote.shippingMethod(),
                paymentDetails:{
                    'method': "worldpay_wallets",
                    'additional_data': {
                        'cc_type': 'SAMSUNGPAY-SSL',
                        'dfReferenceId':  window.checkoutConfig.payment.ccform.sessionId
                    }  
                },
                storecode : window.checkoutConfig.storeCode,
                quote_id : quote.getQuoteId(),
                guest_masked_quote_id: maskedQuoteId,
                isCustomerLoggedIn : customer.isLoggedIn(),
                isRequiredShipping : shippingrequired
            }
            checkoutUtils.setPaymentInformationAndPlaceOrder(checkoutData, response1);
        }
    },
    isActive: function() {  
        if(!window.checkoutConfig.payment.ccform.isWalletsEnabled){
            return false;
        }
        if(!window.checkoutConfig.payment.ccform.isSamsungPayEnable){
            return false;
        }
        if(window.checkoutConfig.payment.ccform.isSubscribed){
            return false;
        }
        return true;
    }
});
});
