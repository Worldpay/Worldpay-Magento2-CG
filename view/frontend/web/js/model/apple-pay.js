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
    'underscore',
    'mage/storage',
    'mage/url',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'Magento_Catalog/js/price-utils',
    'Sapient_Worldpay/js/model/checkout-utils',
    'applePay'
], function ($, ko,_,storage,url,$t,customerData,priceUtils,checkoutUtils) {
    'use strict';

    return {
        versionNumber : 1,
        getCartSubtotal: function(){
            var cart = customerData.get('cart');
            return cart().subtotalAmount;
        },
        isRegionAvailable: function(countryCode){
            var cacheStorage = $.localStorage.get('mage-cache-storage');
            var directoryData = cacheStorage['directory-data'];
            var regionsData = directoryData[countryCode];
            if(regionsData.regions){
                return regionsData.regions;
            }
            return {};
        },
        fetchDynamicShippingRates: function(shippingAddress,applepaySession){
            var self=this;
            var subTotalDescr =  $t("Cart Subtotal");
            var formattedSHippingAdress = {
                'firstname' : '',
                'lastname' : '',
                'street' : [],
                'city' : shippingAddress.administrativeArea,
                'country_id' : shippingAddress.countryCode,
                'postcode' : shippingAddress.postalCode
            }
            var regions = self.isRegionAvailable(shippingAddress.countryCode);
            var shippingStateName =  shippingAddress.administrativeArea;
            _.each(regions,function(value,key){
                if(value.code == shippingStateName){
                    shippingStateName = value.name
                    formattedSHippingAdress.region_id = key;
                }
            });

            window.walletpayObj.fetchRatesByDynamicAddress(formattedSHippingAdress,function(){
                var defaultShippingMethods=  $.localStorage.get('wp-default-shipping-method');

                 var shippingMethodList = [];
                 var shippingMethodListLS = [];
                 var defaultSelectedShipping = {};
                  var i =0;
                 _.each(defaultShippingMethods,function(value,key){
                    var titledesc = priceUtils.formatPrice(value.amount, window.walletpayObj.priceFormat)+' '+value.carrier_title+' - '+ value.method_title;

                        if(key==0){
                            defaultSelectedShipping.identifier = value.carrier_code+'_'+value.method_code;
                            defaultSelectedShipping.label = titledesc;
                            defaultSelectedShipping.detail = value.method_title;
                            defaultSelectedShipping.amount =  value.amount;
                        }

                    shippingMethodList.push({
                        "identifier": value.carrier_code+'_'+value.method_code,
                        "label": titledesc,
                        "detail": value.method_title,
                        "amount": value.amount
                    });
                    i++;
                });
                shippingMethodListLS  =  shippingMethodList;
                 $.localStorage.set('wp-default-shipping-method',shippingMethodListLS); // set in localstorage

                 //var runningTotal = window.walletpayObj.grandtotal();
                // var newTotal = { type: 'final', label: 'Order Total', amount: runningTotal };
                 //var newLineItems =[{type: 'final',label: subTotalDescr, amount: self.getCartSubtotal() }];

                 var explodedShippingMethod = defaultSelectedShipping.identifier.split("_");

                 window.walletpayObj.selectedShippingMethod({
                    carrier_code : explodedShippingMethod[0],
                    method_code : explodedShippingMethod[1],
                });

                var newTotal = self.buildTotal(defaultSelectedShipping);
                var newLineItems = self.buildLineItems(defaultSelectedShipping);

                applepaySession.completeShippingContactSelection(
                    applepaySession.STATUS_SUCCESS,
                    shippingMethodList,
                    newTotal,
                    newLineItems
                );
             });
        },
        formattedApplePayAddress: function(address){
            var self= window.gpayLib;
            var name = address.familyName;
           // var firstname = name.substring(0, name.indexOf(' '));
           // var lastname = name.substring(name.indexOf(' ') + 1);
            var formattedAdress = {
                'firstname' : address.familyName,
                'lastname' : address.givenName,
                'street' : address.addressLines,
                'city' : address.locality,
                'country_id' : address.countryCode,
                'postcode' : address.postalCode,
                'telephone': address.phoneNumber
            }
            var regions = self.isRegionAvailable(address.countryCode);
            var stateName =  address.administrativeArea;
            _.each(regions,function(value,key){
                if(value.code == stateName){
                    stateName = value.name
                    formattedAdress.region_id = key;
                }
            });
            return formattedAdress;
        },
        initApplePaySession: function(paymentRequest){
            var self = this;
            customerData.reload(['customer'], false);
            if(typeof paymentRequest.countryCode == 'undefined'){
                paymentRequest.countryCode = window.walletpayObj.default_country_code;
            }

            if(window.walletpayObj.isRequiredShipping()){
                paymentRequest.shippingMethods = self.getDefaultShippingMethods();
                paymentRequest.requiredShippingContactFields = ["postalAddress","name","phone","email"];
            }else{
                paymentRequest.requiredShippingContactFields = ["phone","email"];
            }
            paymentRequest.requiredBillingContactFields = ["postalAddress","name","phone","email"];
            paymentRequest.ApplePayContactField = ["name","email","phone","postalAddress"];


            var session = new ApplePaySession(self.versionNumber, paymentRequest);
            var subTotalDescr      = $t("Cart Subtotal");
            // Merchant Validation
            session.onvalidatemerchant = function (event) {
                var promise = self.applePayPerformValidation(event.validationURL);
                promise.then(function (merchantSession) {
                    session.completeMerchantValidation(merchantSession);
                });
            }

            // Shipping Contact Selected
            session.onshippingcontactselected = function(event) {
                var newShippingAddress = {
                    "administrativeArea" : event.shippingContact.administrativeArea,
                    "country" : event.shippingContact.country,
                    "countryCode" : event.shippingContact.countryCode,
                    "locality" : event.shippingContact.locality,
                    "postalCode" : event.shippingContact.postalCode,
                    "familyName" : event.shippingContact.familyName,
                    "givenName" : event.shippingContact.givenName,
                }

                self.fetchDynamicShippingRates(newShippingAddress,session);
            }
            // on Shipping method seleced
            session.onshippingmethodselected = function(event){
                const explodedShippingMethod = event.shippingMethod.identifier.split("_");

                    window.walletpayObj.selectedShippingMethod({
                        carrier_code : explodedShippingMethod[0],
                        method_code : explodedShippingMethod[1],
                    });

                session.completeShippingMethodSelection(
                    ApplePaySession.STATUS_SUCCESS,
                    self.buildTotal(event.shippingMethod),
                    self.buildLineItems(event.shippingMethod)
                  )

            }
            // Payment Authorization
            session.onpaymentauthorized = function (event) {
                var promise = self.applePaySendPaymentToken(event.payment.token);
                promise.then(function (success) {
                    var status;
                    if (success){
                        status = ApplePaySession.STATUS_SUCCESS;
                    } else {
                        status = ApplePaySession.STATUS_FAILURE;
                    }
                    session.completePayment(status);
                });
                var appleResponse = JSON.stringify(event.payment.token);

                if(window.walletpayObj.isRequiredShipping()){
                        var selectedShippingaddress = self.formattedApplePayAddress(event.payment.shippingContact);
                        //var selectedBillingaddress = self.formattedApplePayAddress(event.payment.billingContact);

                        window.walletpayObj.selectedShippingAddress(selectedShippingaddress);
                        window.walletpayObj.selectedBillingAddress(selectedShippingaddress); // need to modify the changes later

                        if(window.walletpayObj.isUserLoggedIn()){
                            window.walletpayObj.selectedBillingAddress().email = window.walletpayObj.customerDetails.email;
                        }else{
                            window.walletpayObj.selectedBillingAddress().email = event.payment.shippingContact.emailAddress;
                        }
                }else{
                    var billingContact = event.payment.billingContact;
                    billingContact.phoneNumber = event.payment.shippingContact.phoneNumber;

                    var selectedBillingaddress = self.formattedApplePayAddress(billingContact);
                    window.walletpayObj.selectedBillingAddress(selectedBillingaddress);

                    if(window.walletpayObj.isUserLoggedIn()){
                        window.walletpayObj.selectedBillingAddress().email = window.walletpayObj.customerDetails.email;
                    }else{
                        window.walletpayObj.selectedBillingAddress().email = event.payment.shippingContact.emailAddress;
                    }
                }

                var checkoutData = {
                    billingAddress :window.walletpayObj.selectedBillingAddress(),
                    shippingAddress: window.walletpayObj.selectedShippingAddress(),
                    shippingMethod: window.walletpayObj.selectedShippingMethod(),
                    paymentDetails:{
                        'method': "worldpay_wallets",
                        'additional_data': {
                            'cc_type': 'APPLEPAY-SSL',
                            'appleResponse' : appleResponse,
                            'dfReferenceId':  window.walletpayObj.sessionId,
                            'browser_screenheight': window.screen.height,
                            'browser_screenwidth': window.screen.width,
                            'browser_colordepth': window.screen.colorDepth
                        }
                    },
                    storecode :window.walletpayObj.store_code,
                    quote_id : window.walletpayObj.currentQuoteid,
                    guest_masked_quote_id: window.walletpayObj.currentQuoteMaskedId,
                    isCustomerLoggedIn : window.walletpayObj.isUserLoggedIn(),
                    isRequiredShipping : window.walletpayObj.isRequiredShipping()
                }
                checkoutUtils.placeorder(checkoutData);
            }
            session.oncancel = function(event) {
                console.log("Apple Pay session cancelled",event);
            }
            session.begin();
        },
        buildTotal: function(selectedShippingMethod){
            var cart = customerData.get('cart');
            var totalAmount = cart().subtotalAmount;
            var totalPayableAmount =  parseFloat(selectedShippingMethod.amount) + parseFloat(totalAmount);
            return {
                label: $t('Order Total'),
                amount: totalPayableAmount
              }
        },
        buildLineItems: function(selectedShippingMethod){
            var cart = customerData.get('cart'),
            totalAmount = cart().subtotalAmount,
            subtotal = {
                type: 'final',
                label: $t('Subtotal'),
                amount: totalAmount
            },
            shippingTotal = {
                  type: 'final',
                  label: $t('Shipping'),
                  amount: selectedShippingMethod.amount
            };

            var lineItems = [];

            lineItems.push(subtotal);
            lineItems.push(shippingTotal);

            return lineItems;
        },
        getDefaultShippingMethods: function(){
            var defaultShippingMethods = $.localStorage.get('wp-default-shipping-method');
            var shippingMethodList = [];
            _.each(defaultShippingMethods,function(value){
                if(typeof value.carrier_title!='undefined'){
                    var titledesc = priceUtils.formatPrice(value.amount, window.walletpayObj.priceFormat)+' '+value.carrier_title+' - '+ value.method_title;
                        shippingMethodList.push({
                                    "identifier": value.carrier_code+'_'+value.method_code,
                                    "label": titledesc,
                                    "detail": value.method_title,
                                    "amount": value.amount
                                });
                    }else{

                        shippingMethodList.push({
                                    "identifier": value.identifier,
                                    "label": value.label,
                                    "detail": value.detail,
                                    "amount": value.amount
                                });
                }
            });
            return shippingMethodList;
        },
        applePayPerformValidation:  function (valURL) {
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
        applePaySendPaymentToken : function(paymentToken){
            var debug=true;
            return new Promise(function(resolve, reject) {
                var appleResponse = paymentToken;
                if ( debug == true )
                    resolve(true);
                else
                    reject;
                });
        },
    };
});
