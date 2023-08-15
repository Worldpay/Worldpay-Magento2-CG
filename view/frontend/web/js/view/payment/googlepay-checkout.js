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
            return (window.checkoutConfig.payment.ccform.isGooglePayEnable && window.checkoutConfig.payment.ccform.isWalletsEnabled && !window.checkoutConfig.payment.ccform.isSubscribed);
        },
        addGooglePayButton: function(){
            var self = this;
            var additionalData = {
                "env_mode": self.googlepayOptions.env_mode,
                "currencyCode":  self.googlepayOptions.currencyCode,
                "baseRequest": self.googlepayOptions.baseRequest,
                "allowedCardAuthMethods": self.googlepayOptions.allowedCardAuthMethods,
                "allowedCardNetworks": self.googlepayOptions.allowedCardNetworks,
                "tokenizationSpecification": self.googlepayOptions.tokenizationSpecification,
                "google_btn_customisation" : {
                    "buttonColor" : 'black',
                    "buttonType" : 'buy',
                    "buttonLocale" : 'en',
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
                "allowedCardAuthMethods": window.googleCheckout.googlepayOptions.allowedCardAuthMethods,
                "allowedCardNetworks": window.googleCheckout.googlepayOptions.allowedCardNetworks,
                "tokenizationSpecification": window.googleCheckout.googlepayOptions.tokenizationSpecification,
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
                            'dfReferenceId':  window.checkoutConfig.payment.ccform.sessionId
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
