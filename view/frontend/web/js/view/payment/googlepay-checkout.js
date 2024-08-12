/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'uiComponent',
    'ko',
    'mage/translate',
    'Sapient_Worldpay/js/model/google-pay',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Sapient_Worldpay/js/model/checkout-utils',
    'Sapient_Worldpay/js/action/place-multishipping-order',
    'Magento_Checkout/js/model/full-screen-loader'
], function (
    $,
    _,
    Component,
    ko,    
    $t,
    GooglePayModel,
    stepNavigator,
    quote,
    customer,
    checkoutUtils,
    placeMultishippingOrder,
    fullScreenLoader
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Sapient_Worldpay/payment/wallets/googlepay-checkout',
            googlepayOptions:{
                container : 'wp-google-pay-btn',
                baseRequest : {
                     apiVersion: 2,
                     apiVersionMinor: 0
                 },
                 currencyCode : window.checkoutConfig.totalsData.base_currency_code,
                 allowedCardAuthMethods: window.checkoutConfig.payment.ccform.googleAuthMethods.split(","),
                 allowedCardNetworks : window.checkoutConfig.payment.ccform.googlePaymentMethods.split(","),
                 tokenizationSpecification : {
                    type: 'PAYMENT_GATEWAY',
                    parameters: {
                        'gateway': window.checkoutConfig.payment.ccform.googleGatewayMerchantname,
                        'gatewayMerchantId': window.checkoutConfig.payment.ccform.googleGatewayMerchantid
                    }
                },
                env_mode : window.checkoutConfig.payment.general.environmentMode
            },
        },
        initGpayCheckout: function () {
            var self= this;
            if(self.isActive() && ($('.gpay-card-info-container').length == 0)){
                    self.addGooglePayButton();
             }   
        },
        initialize: function () { 
            this._super();
            var self=this;
            window.googleCheckout = this;
            
            $(document).on('ajaxComplete',function(event, xhr, settings) {                
                // load once payment types ajax completes
                if(settings.url.indexOf("worldpay/latam/types") != -1)
                {
                    if(self.isActive() && ($('.gpay-card-info-container').length == 0)){
                        //self.addGooglePayButton();
                    }
                }                
            });
        },
        isActive: function(){            
            if(window.checkoutConfig.payment.ccform.isMultishipping){ 
                return (window.checkoutConfig.payment.ccform.isMsGooglePayEnable && window.checkoutConfig.payment.ccform.isWalletsEnabled && !window.checkoutConfig.payment.ccform.isSubscribed);
            }          
            return (window.checkoutConfig.payment.ccform.isGooglePayEnable && window.checkoutConfig.payment.ccform.isWalletsEnabled && !window.checkoutConfig.payment.ccform.isSubscribed);
        },

        gPayCardAuthMethods:function(){
            if(window.checkoutConfig.payment.ccform.isMultishipping){ 
              if(window.checkoutConfig.payment.ccform.msGoogleAuthMethods !='undefined' 
                && window.checkoutConfig.payment.ccform.msGoogleAuthMethods !=null){
                    return window.checkoutConfig.payment.ccform.msGoogleAuthMethods.split(",");
              }
                return window.checkoutConfig.payment.ccform.googleAuthMethods.split(",");
            }  
            return window.checkoutConfig.payment.ccform.googleAuthMethods.split(",");
        },

        gPayPaymentMethods:function(){
            if(window.checkoutConfig.payment.ccform.isMultishipping){ 
              if(window.checkoutConfig.payment.ccform.msGooglePaymentMethods !='undefined' 
                && window.checkoutConfig.payment.ccform.msGooglePaymentMethods !=null){
                    return window.checkoutConfig.payment.ccform.msGooglePaymentMethods.split(",");
              }
                return window.checkoutConfig.payment.ccform.googlePaymentMethods.split(",");
            }  
            return window.checkoutConfig.payment.ccform.googlePaymentMethods.split(",");
        },
        gatewayName:function(){
            if(window.checkoutConfig.payment.ccform.isMultishipping){ 
              if(window.checkoutConfig.payment.ccform.msGoogleGatewayMerchantname !='undefined' 
                && window.checkoutConfig.payment.ccform.msGoogleGatewayMerchantname !=null){
                    return window.checkoutConfig.payment.ccform.msGoogleGatewayMerchantname;
              }
                return window.checkoutConfig.payment.ccform.googleGatewayMerchantname;
            }  
            return window.checkoutConfig.payment.ccform.googleGatewayMerchantname;
        },
        gatewayMerchantId:function(){
            if(window.checkoutConfig.payment.ccform.isMultishipping){ 
              if(window.checkoutConfig.payment.ccform.msGoogleGatewayMerchantid !='undefined' 
                && window.checkoutConfig.payment.ccform.msGoogleGatewayMerchantid !=null){
                    return window.checkoutConfig.payment.ccform.msGoogleGatewayMerchantid;
              }
                return window.checkoutConfig.payment.ccform.googleGatewayMerchantid;
            }  
            return window.checkoutConfig.payment.ccform.googleGatewayMerchantid;
        },
        gpayMerchantName:function(){
            if(window.checkoutConfig.payment.ccform.isMultishipping){ 
              if(window.checkoutConfig.payment.ccform.msGoogleMerchantname !='undefined' 
                && window.checkoutConfig.payment.ccform.msGoogleMerchantname !=null){
                    return window.checkoutConfig.payment.ccform.msGoogleMerchantname;
              }
                return window.checkoutConfig.payment.ccform.googleMerchantname;
            }  
            return window.checkoutConfig.payment.ccform.googleMerchantname;
        },
        gpayMerchantId:function(){
            if(window.checkoutConfig.payment.ccform.isMultishipping){ 
              if(window.checkoutConfig.payment.ccform.msGoogleMerchantid !='undefined' 
                && window.checkoutConfig.payment.ccform.msGoogleMerchantid !=null){
                    return window.checkoutConfig.payment.ccform.msGoogleMerchantid;
              }
                return window.checkoutConfig.payment.ccform.googleMerchantid;
            }  
            return window.checkoutConfig.payment.ccform.googleMerchantid;         
        },
        gpayTokenizationSpecification:function(){
            var self = this;
            var tokenizationSpecification ={
                "type": "PAYMENT_GATEWAY",
                "parameters": {
                  "gateway": self.gatewayName(),
                  "gatewayMerchantId": self.gatewayMerchantId()
                }  
            }    
            return tokenizationSpecification;
        },
        addGooglePayButton: function(){
            var self = this;
            var additionalData = {
                "env_mode": self.googlepayOptions.env_mode,
                "currencyCode":  self.googlepayOptions.currencyCode,
                "baseRequest": self.googlepayOptions.baseRequest,
                "allowedCardAuthMethods": self.gPayCardAuthMethods(),
                "allowedCardNetworks": self.gPayPaymentMethods(),
                "tokenizationSpecification": self.gpayTokenizationSpecification(),
                "google_btn_customisation" : {
                    "buttonColor" : window.checkoutConfig.payment.ccform.gpayButtonColor,
                    "buttonType" : window.checkoutConfig.payment.ccform.gpayButtonType,
                    "buttonLocale" : window.checkoutConfig.payment.ccform.gpayButtonLocale,
                    "buttonSizeMode" : 'fill'
                }
            }            
            GooglePayModel.addGooglePayButton(
                self.googlepayOptions.container,
                additionalData,
                self.initCheckout
            );

        },
        initCheckout: function(){
            var self = this;
            var ginitData = {
                "env_mode": window.googleCheckout.googlepayOptions.env_mode,
                "currencyCode": window.googleCheckout.googlepayOptions.currencyCode,
                "baseRequest": window.googleCheckout.googlepayOptions.baseRequest,
                "allowedCardAuthMethods": window.googleCheckout.gPayCardAuthMethods(),
                "allowedCardNetworks": window.googleCheckout.gPayPaymentMethods(),
                "tokenizationSpecification": window.googleCheckout.gpayTokenizationSpecification(),
                "totalPrice": window.googleCheckout.getGrandTotal()
            }
            GooglePayModel.initGooglePay(ginitData).then(function(paymentData){   
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
                            'cc_type': 'PAYWITHGOOGLE-SSL',
                            'walletResponse' : JSON.stringify(paymentData),
                            'dfReferenceId':  window.checkoutConfig.payment.ccform.sessionId,
                            'browser_screenheight': window.screen.height,
                            'browser_screenwidth': window.screen.width,
                            'browser_colordepth': window.screen.colorDepth
                        }
                    }   
                var checkoutData = {
                    billingAddress : quote.billingAddress(),
                    shippingAddress: quote.shippingAddress(),
                    shippingMethod: quote.shippingMethod(),
                    paymentDetails: paymentData,
                    storecode : window.checkoutConfig.storeCode,
                    quote_id : quote.getQuoteId(),
                    guest_masked_quote_id: maskedQuoteId,
                    isCustomerLoggedIn : customer.isLoggedIn(),
                    isRequiredShipping : shippingrequired
                }
                if(window.checkoutConfig.payment.ccform.isMultishipping){                       
                    fullScreenLoader.startLoader();                                                     
                    placeMultishippingOrder(paymentData);
                }
                else{
                    checkoutUtils.setPaymentInformationAndPlaceOrder(checkoutData);
                }
            }).catch(function(err) {
                // show error in developer console for debugging
                console.error("Gpay Init Error:",err);
                return false;
            });
        },
        getGrandTotal : function () {
            return quote.totals()['grand_total'];
        }
    });
});