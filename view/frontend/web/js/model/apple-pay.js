/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
 define([
    'jquery',
    'ko',    
    'mage/storage',
    'mage/url',
    'mage/translate',
    'applePay'
], function ($, ko,storage,urlBuilder,$t) {
    'use strict';  

    return {
        onPlaceOrder : function(){
            var method = "worldpay_wallets";
            var cc_type = 'APPLEPAY-SSL';
            console.log("triggered once orderplaced");
        },
        getPaymentRequest : function(){
            var self= this;
            // creating payment request
            var runningAmount = 30;
            var subTotalDescr	= "Cart Subtotal";
            var paymentRequest = {
                currencyCode: 'US',
                countryCode: 'USD',
                lineItems: [{label: subTotalDescr, amount: runningAmount }],
                total: {
                    label: 'Order Total',
                    amount: runningAmount
                },
                supportedNetworks: ['amex', 'masterCard', 'visa' ],
                merchantCapabilities: [ 'supports3DS']
            };
            console.log(paymentRequest);
            return paymentRequest;
        },
        doMerchantValidation : function(){
            var self = this;
            // creating apple Pay Session 
        },
        getPaymentMethodSelection : function(){
            var self = this;
            // sending http request to do payment Method Selection 
            // completion of complete payment method selection
        },
        doPaymentAuthorization : function(){
            var self = this;
            //send token details , validation
            // get tokenization complete and getting success and failure response
        },
        performValidation : function(valURL){
            var self = this;
            // perform ValURL to check request parameters.
        }
    };
});
