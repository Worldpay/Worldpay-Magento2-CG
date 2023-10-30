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
'applePay',
'Magento_Checkout/js/model/url-builder',
'mage/url',
'Magento_Customer/js/model/customer',
'Sapient_Worldpay/js/action/place-multishipping-order',
'Magento_Checkout/js/model/full-screen-loader'
], function ($, ko, Component, _, $t, quote, customerData, checkoutUtils, applePay, urlBuilder, url,customer, placeMultishippingOrder, fullScreenLoader) {
'use strict';
    var paymentService = false;
    var billingAddressCountryId = "";
    var appleResponse = "";
    var paymentToken = "";
    var merchantId = '';
    var response = '';
    var response1 = '';
    var dfReferenceId = "";
    var debug = true;
    var applepayConfig = [];
    var appleMerchantId = window.checkoutConfig.payment.ccform.appleMerchantid;
    if(window.checkoutConfig.payment.general.environmentMode == 'PRODUCTION'){
        merchantId = "merchantId:"+window.checkoutConfig.payment.ccform.googleMerchantid;
    }
    if(window.checkoutConfig.payment.ccform.isMultishipping){
        var msAppleMerchantid = window.checkoutConfig.payment.ccform.msAppleMerchantid;
        if(msAppleMerchantid){
            appleMerchantId = msAppleMerchantid;
        }
    }
return Component.extend({
    defaults: {
        template: 'Sapient_Worldpay/payment/wallets/applepay-checkout',
        applepayOptions:{
        env_mode : window.checkoutConfig.payment.general.environmentMode,
        merchantIdentifier : appleMerchantId,
        countryCode : window.checkoutConfig.defaultCountryId,
        currencyCode : window.checkoutConfig.quoteData.quote_currency_code,
        subTotalDescr : "Cart Subtotal",
        lineItemLabel : "Order Total"
        } 
    },
    applepayConfig: ko.observableArray([]),

    /**
     * @returns {*}
     */
    initialize: function () {
        this._super();
        var self = this;
        this.applepayConfig({
            color : window.checkoutConfig.payment.ccform.applePayButtonColor,
            type : window.checkoutConfig.payment.ccform.applePayButtonType,
            locale : window.checkoutConfig.payment.ccform.applePayButtonLocale
        });
        return this;
    },
    sendPaymentToken : function(paymentToken){
            return new Promise(function(resolve, reject) {
                var appleResponse = paymentToken;
    
                if ( debug == true )
                resolve(true);
                else
                reject;
            });
    },
    performValidation:  function (valURL) {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.onload = function() {      
                var finaldata = this.responseText.slice(1, -1);
                var finaldata = finaldata.replace(/\\/g, '');
                
                var data = JSON.parse(finaldata);
                resolve(data);
            };
            xhr.onerror = reject;
            var linkUrl = url.build('worldpay/applepay/index?u=');

            xhr.open('GET', linkUrl + valURL);
            xhr.send();
        });
    },
    initApplePay: function(){
                
                var self= this;
                var baseGrandTotal   = window.checkoutConfig.totalsData.base_subtotal;
                var runningAmount = (Math.round(baseGrandTotal * 100) / 100).toFixed(2);
                var subTotal = window.checkoutConfig.quoteData.base_grand_total;
                var runningTotal = (Math.round(subTotal * 100) / 100).toFixed(2);
                var subTotalDescr      = "Cart Subtotal";
                var currencyCode = window.checkoutConfig.quoteData.quote_currency_code;
                var countryCode = window.checkoutConfig.defaultCountryId;
                var paymentRequest = {
                    currencyCode: currencyCode,
                    countryCode: countryCode,
                    lineItems: [{label: subTotalDescr, amount: runningAmount }],
                    total: {
                        label: 'Order Total',
                        amount: runningAmount
                    },
                    supportedNetworks: ['amex', 'masterCard', 'visa' ],
                    //merchantCapabilities: [ 'supports3DS', 'supportsEMV', 'supportsCredit', 'supportsDebit' ]
                    merchantCapabilities: [ 'supports3DS'] //production changes
                };

                var session = new ApplePaySession(1, paymentRequest);

                // Merchant Validation
                session.onvalidatemerchant = function (event) {                     
                    var promise = self.performValidation(event.validationURL);
                    promise.then(function (merchantSession) {
                        session.completeMerchantValidation(merchantSession);
                    }); 
                }
                // Payment Method Selection
                session.onpaymentmethodselected = function(event) {   
                    var linkUrl = url.build('worldpay/applepay/index?u=getTotal');                         
                    var xhttp = new XMLHttpRequest();
                    xhttp.open("GET", linkUrl, false);
                     xhttp.setRequestHeader("Content-type", "application/json");
                    xhttp.send();
                    var finalTotal = xhttp.responseText.slice(1, -1); // removing quotes
                    
                    var runningTotal = (Math.round(finalTotal * 100) / 100).toFixed(2);
                    var newTotal = { type: 'final', label: 'Order Total', amount: runningTotal };
                    var newLineItems =[{type: 'final',label: subTotalDescr, amount: runningAmount }];

                    session.completePaymentMethodSelection( newTotal, newLineItems );
                }

                session.onpaymentauthorized = function (event) {
                    var promise = self.sendPaymentToken(event.payment.token);

                    promise.then(function (success) {   
                        var status;
                        if (success){
                            status = ApplePaySession.STATUS_SUCCESS;
                        } else {
                            status = ApplePaySession.STATUS_FAILURE;
                        }
                        session.completePayment(status);
                    });
                    appleResponse = JSON.stringify(event.payment.token);
                   
                    var maskedQuoteId = "";
                    if(!customer.isLoggedIn()){
                        maskedQuoteId = quote.getQuoteId();
                        quote.billingAddress().email=quote.guestEmail;
                    }

                    var shippingrequired = false;
                    if(quote.shippingMethod()){
                        shippingrequired = true;
                    }
                    var paymentData = {
                        'method': "worldpay_wallets",
                        'additional_data': {
                            'cc_type': 'APPLEPAY-SSL',
                            'appleResponse' : appleResponse,
                            'dfReferenceId':   window.checkoutConfig.payment.ccform.sessionId
                        }  
                    }
                    var checkoutData = {
                        billingAddress :quote.billingAddress(),
                        shippingAddress: quote.shippingAddress(),
                        shippingMethod: quote.shippingMethod(),
                        paymentDetails: paymentData,
                        storecode :window.checkoutConfig.storeCode,
                        quote_id : quote.getQuoteId(),
                        guest_masked_quote_id: quote.getQuoteId(),
                        isCustomerLoggedIn : self.isUserLoggedIn(),
                        isRequiredShipping : shippingrequired
                    }
                    if(window.checkoutConfig.payment.ccform.isMultishipping){ 
                        fullScreenLoader.startLoader();                                                          
                        placeMultishippingOrder(paymentData);
                    }
                    else{
                        checkoutUtils.setPaymentInformationAndPlaceOrder(checkoutData);
                    }

                    //self.placeOrder();       
                }

                session.oncancel = function(event) {
                    console.log("Apple Pay session cancelled");
                }

                session.begin();
    },
    isActive: function() {  
        if(!window.checkoutConfig.payment.ccform.isWalletsEnabled){
            return false;
        }
        if(window.checkoutConfig.payment.ccform.isMultishipping){
            if(!window.checkoutConfig.payment.ccform.isMsApplePayEnable){
                return false;
            }
        }
        else {
            if(!window.checkoutConfig.payment.ccform.isApplePayEnable){
                return false;
            }
        }
        if(window.checkoutConfig.payment.ccform.isSubscribed){
            return false;
        }
        if (window.ApplePaySession) {
            $(".payment-logo-wrapper").css({
                "margin": "0 0 0 auto",
                "width": "78%"
            });
            return true; 
        }        

    },
    /* Checking Customer Login */
    isUserLoggedIn: function () {
        var self = this,
            customer = customerData.get('customer')();
        if (customer.fullname && customer.firstname) {
            return true;
        }
        return false;
    }
});
});
