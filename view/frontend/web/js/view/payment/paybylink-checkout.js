define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'underscore',
        'mage/translate',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Customer/js/customer-data',
        'Sapient_Worldpay/js/model/checkout-utils',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment/additional-validators'
    ], function ($, ko, Component, _, $t, quote, fullScreenLoader, customerData, checkoutUtils, urlBuilder, url, customer,additionalValidators) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Sapient_Worldpay/payment/paybylink'
            },            
            isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),
            /**
             * @returns {*}
             */
            initialize: function () {
                this._super();
                var self = this;
                quote.billingAddress.subscribe(function (address) {
                    this.isPlaceOrderActionAllowed(address !== null);
                }, this);
                return this;
            },
            performPlaceOrder: function () {
                var maskedQuoteId = "";
                if(!additionalValidators.validate()){
                    console.log("Validation Failed");
                    return false;
                }
                if (!customer.isLoggedIn()) {
                    maskedQuoteId = quote.getQuoteId();
                    quote.billingAddress().email = quote.guestEmail;
                }
                var shippingrequired = false;
                if (quote.shippingMethod()) {
                    shippingrequired = true;
                }
                var checkoutData = {
                    billingAddress: quote.billingAddress(),
                    shippingAddress: quote.shippingAddress(),
                    shippingMethod: quote.shippingMethod(),
                    paymentDetails: {
                        'method': "worldpay_paybylink",
                        'additional_data': {
                            'cc_type': 'ALL',
                            'dfReferenceId': window.checkoutConfig.payment.ccform.sessionId
                        }
                    },
                    storecode: window.checkoutConfig.storeCode,
                    quote_id: quote.getQuoteId(),
                    guest_masked_quote_id: maskedQuoteId,
                    isCustomerLoggedIn: customer.isLoggedIn(),
                    isRequiredShipping: shippingrequired
                }                
                checkoutUtils.setPaymentInformationAndPlaceOrder(checkoutData);
            },            
            getpaybylinkText: function(){
                return window.checkoutConfig.payment.ccform.payByLinkButtonName;
            },
            isActive: function () {
                if(!window.checkoutConfig.payment.ccform.isPayByLinkEnable){
                    return false;
                }
                if(window.checkoutConfig.payment.ccform.isSubscribed){
                    return false;
                }
                if(window.checkoutConfig.payment.ccform.isEnabledEFTPOS){
                    return false;
                }
                return true;
            }
        });
    });