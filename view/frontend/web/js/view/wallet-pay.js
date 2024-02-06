/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'ko',
    'jquery',
    'underscore',
    'uiComponent',
    'Magento_Ui/js/modal/confirm',
    'Magento_Customer/js/customer-data',
    'mage/url',
    'mage/template',
    'mage/translate',
    'Magento_Catalog/js/product/view/product-ids-resolver',
    'Magento_Catalog/js/product/view/product-info-resolver',
    'Magento_Customer/js/model/customer',
    'mage/storage',
    'Magento_Ui/js/modal/modal',
    'Magento_Customer/js/model/address-list',
    'Magento_Catalog/js/price-utils',
    'text!Sapient_Worldpay/template/wallets/total-segments.html',
    'text!Sapient_Worldpay/template/wallets/pdp-cart-info.html',
    'Sapient_Worldpay/js/model/google-pay',
    'Sapient_Worldpay/js/model/checkout-utils',
    'Sapient_Worldpay/js/model/region-updator',
    'Sapient_Worldpay/js/model/apple-pay',
    'mage/validation'
], function (ko, $, _, Component, confirm, customerData, url, mageTemplate, $t,idsResolver, productInfoResolver,customer,storage,modal,addressList,priceUtils,totalsegmentsTemplate,pdpCartTemplate,GooglePayModel,checkoutUtils,RegionUpdater,ApplePayModel) {
    'use strict';

    var appleResponse = "";
    var debug = true;

     return Component.extend({
        defaults: {
            buttonText: $t('Google Pay'),
            googlepayOptions:{
                container : 'wp-google-pay-btn',
                baseRequest : {
                     apiVersion: 2,
                     apiVersionMinor: 0
                 }
            },

            productFormSelector: '#product_addtocart_form'

        },
        priceFormat :{
            decimalSymbol: ".",
            groupLength: 3,
            groupSymbol: ",",
            integerRequired: false,
            pattern: "$%s",
            precision: 2,
            requiredPrecision: 2
        },

        storeCode :'default',
        gpayLoaded: false,
        messagesSelector: '[data-placeholder="messages"]',
        minicartSelector: '[data-block="minicart"]',
        processStart: null,
        processStop: null,
        paymentsClient : null,
        customerData : null,
        currentQuoteid : null,
        currentQuoteMaskedId : null,
        selectedShippingAddress : ko.observableArray([]),
        selectedBillingAddress : ko.observableArray([]),
        selectedShippingMethod : ko.observableArray([]),
        availableShippingMethods : ko.observableArray([]),
        customeraddresses : ko.observableArray([]),
        isLoadingShippingMethod : ko.observable(false),
        grandtotal : ko.observable(0),
        grandtotalFormatted : ko.observable(0),
        currencyCode : ko.observable('USD'),
        isLoadingCheckoutActions: ko.observable(false),
        totalsegments : ko.observable(),
        getPDPcartInfo: ko.observable(),
        isApplied :ko.observable(null),
        couponCode :ko.observable(null),
        countriesDropDown :ko.observable(null),
        BillingCountriesDropDown : ko.observable(null),
        isBillingAddressSameAsShipping : ko.observable(false),
        isRequiredShipping : ko.observable(true),
        isApplePayPurchase : ko.observable(false),
        showNewBillingAddress : ko.observable(false),
        isAppleDevice : ko.observable(false),
        isUserActiveSession: ko.observable(false),
        /** @inheritdoc */
        initialize: function () {
            this._super();
            window.walletpayObj = this;
            var self = this;

            customerData.getInitCustomerData().done(function(){
                self.customerData = customerData.get('customer')();
                if(self.customerData.firstname){
                    self.addGooglePayPurchaseBtn();
                    self.addApplePayButton();
                    self.isApplePayPurchase(false);
                    self.isUserActiveSession(true);
                }else{
                    self.addGooglePayPurchaseBtn();
                    self.addApplePayButton();
                    self.isApplePayPurchase(false);
                    self.isUserActiveSession(false);
                }
            });

            if(this.isUserLoggedIn()){
                var existingCustomerDetails = customerData.get('customer')();
                this.customeraddresses(existingCustomerDetails.customer_address);
            }
            mageTemplate(totalsegmentsTemplate);
            mageTemplate(pdpCartTemplate);
            this.isBillingAddressSameAsShipping(true);
            this.isRequiredShipping.subscribe(function(isrequired){
                if(isrequired == false){
                    // do not need shipping and billing address checkbox
                    self.isBillingAddressSameAsShipping(false);
                }
            })


            $("#worldpay-add-plan").on('click',function(){
                if($('#worldpay-add-plan').is(":checked")){
                    $("#wp-wallet-pay").hide();
                }else{
                    $("#wp-wallet-pay").show();
                }
            })
        },
        countriesHtml : function(){
            var self = this;
            return self.countriesHtml;
        },
        billingCountriesHtml : function(){
            var self = this;
            return self.billingCountryHtml;
        },
        updateTotalSegments : function(segments){
            var self = this;
            var allsegments = {},totalsTemplate = "",confirmData=null;
            _.each(segments,function(val , key){
                allsegments[key] = {
                    "title" : val.title,
                    "value" : priceUtils.formatPrice(val.value, self.priceFormat),
                };
            });
            totalsTemplate = mageTemplate(totalsegmentsTemplate),
            confirmData = {
                segment :allsegments
            }
            this.totalsegments(totalsTemplate({
                data: confirmData
            }));
        },
        updatePdpCartSegments: function(segments){
            var self = this;
            var allsegments = {},pdpCart = "",confirmData=null;
            _.each(segments,function(val , key){
                allsegments[key] = {
                    "name" : val.name,
                    "image" : val.image,
                    "options": val.options,
                    "subtotal": val.subtotal
                };
            });
            pdpCart = mageTemplate(pdpCartTemplate),

            confirmData = {
                segment :allsegments
            }
            self.getPDPcartInfo(pdpCart({
                data: confirmData
            }));
        },
        applyCoupon : function(){
            var self = this;
            var discountForm = $("#discount-form");
            if (!(discountForm.validation() && discountForm.validation('isValid'))) {
                return;
            }
            if(!self.currentQuoteid){
                return false;
            }

            if(!self.isUserLoggedIn()){
                if(!self.currentQuoteMaskedId){
                    return false;
                }
            }

            var quoteObj = {
                isCustomerLoggedIn : window.walletpayObj.isUserLoggedIn(),
                storecode : window.walletpayObj.store_code,
                quoteId : window.walletpayObj.currentQuoteid
            }

            if(!window.walletpayObj.isUserLoggedIn()){
                quoteObj.quoteId = window.walletpayObj.currentQuoteMaskedId;
            }

            $("body").trigger('processStart');
            checkoutUtils.applyCoupon(
                quoteObj,
                self.couponCode()
            ).done(function(apiresponse){
                var response = (apiresponse);
                self.isApplied(true);
                self.reloadSections();
                $("body").trigger('processStop');
            }).fail(function(apiresponse){
                var response = (apiresponse);
                self.isApplied(false);
                $("body").trigger('processStop');
            });
        },
        cancelCoupon: function(){
            var self = this;
            if(!self.currentQuoteid){
                return false;
            }

            if(!self.isUserLoggedIn()){
                if(!self.currentQuoteMaskedId){
                    return false;
                }

            }

            var quoteObj = {
                isCustomerLoggedIn : window.walletpayObj.isUserLoggedIn(),
                storecode : window.walletpayObj.store_code,
                quoteId : window.walletpayObj.currentQuoteid
            }

            if(!window.walletpayObj.isUserLoggedIn()){
                quoteObj.quoteId = window.walletpayObj.currentQuoteMaskedId;
            }
            $("body").trigger('processStart');
            return checkoutUtils.cancelCoupon(
                quoteObj
            ).done(function(apiresponse){
                var response = (apiresponse);
                self.isApplied(false);
                self.reloadSections();
                $("body").trigger('processStop');
            }).fail(function(apiresponse){
                var response = (apiresponse);
                self.isApplied(false);
                $("body").trigger('processStop');
            });
        },
        showNewAddressForm : function(){
            var self = this;
                $("#new-address-form").toggle();
        },
        showNewBillingAddressForm : function (){
            var self= this;
            if(self.showNewBillingAddress() == false){
                self.showNewBillingAddress(true);
            }else if(self.showNewBillingAddress() == true){
                self.showNewBillingAddress(false);
            }
        },
        sameAsShippingAddress : function(){
            var self = window.walletpayObj;
            if(self.isBillingAddressSameAsShipping() == false){
                self.isBillingAddressSameAsShipping(true);
            }else{
                self.isBillingAddressSameAsShipping(false);
            }
            return true;
        },
        preparePayment : function(isApplePay){
            if(window.walletpayObj.isApplePayPurchase()){
                if (window.ApplePaySession) {
                    window.walletpayObj.initApplePaySession();
                }
            }else{
                var ginitData = {
                    "env_mode": window.walletpayObj.env_mode,
                    "currencyCode": window.walletpayObj.currencyCode(),
                    "baseRequest": window.walletpayObj.googlepayOptions.baseRequest,
                    "allowedCardAuthMethods": window.walletpayObj.googlepayOptions.allowedCardAuthMethods,
                    "allowedCardNetworks": window.walletpayObj.googlepayOptions.allowedCardNetworks,
                    "tokenizationSpecification": window.walletpayObj.googlepayOptions.tokenizationSpecification,
                    "totalPrice": window.walletpayObj.grandtotal()
                }
                GooglePayModel.initGooglePay(ginitData).then(function(paymentData){
                    var checkoutData = {
                        billingAddress :window.walletpayObj.selectedBillingAddress(),
                        shippingAddress: window.walletpayObj.selectedShippingAddress(),
                        shippingMethod: window.walletpayObj.selectedShippingMethod(),
                        paymentDetails:{
                            'method': "worldpay_wallets",
                            'additional_data': {
                                'cc_type': 'PAYWITHGOOGLE-SSL',
                                'walletResponse' : JSON.stringify(paymentData),
                                'dfReferenceId':  window.walletpayObj.sessionId
                            }
                        },
                        storecode :window.walletpayObj.store_code,
                        quote_id : window.walletpayObj.currentQuoteid,
                        guest_masked_quote_id: window.walletpayObj.currentQuoteMaskedId,
                        isCustomerLoggedIn : window.walletpayObj.isUserLoggedIn(),
                        isRequiredShipping : window.walletpayObj.isRequiredShipping()
                    }



                    if(window.walletpayObj.isUserLoggedIn()){
                        window.walletpayObj.selectedBillingAddress().email = window.walletpayObj.customerDetails.email;
                    }else{
                        window.walletpayObj.selectedBillingAddress().email = paymentData.email;
                    }
                    checkoutUtils.placeorder(checkoutData);
                }).catch(function(err) {
                    // show error in developer console for debugging
                    console.error("Gpay Init Error:",err);
                    return false;
                });
            }
        },
        getBillingAddress : function(){
            var self = this,selectedAddress = {},customerDataAddress;
            if(self.isUserLoggedIn()){
                customerDataAddress = customerData.get('customer')();
                window.walletpayObj.customerData = customerDataAddress;
                window.walletpayObj.customeraddresses(customerDataAddress.customer_address);
                if(typeof window.walletpayObj.customerData.customer_address !=='undefined'){
                    addressList = window.walletpayObj.customerData.customer_address;
                    _.each(addressList,function(address){
                        if(address.default_billing){
                            selectedAddress.firstname = address.firstname;
                            selectedAddress.lastname = address.lastname;
                            selectedAddress.street = address.street;
                            selectedAddress.city = address.city;
                            selectedAddress.region_id = address.region_id;
                            selectedAddress.country_id = address.country_id;
                            selectedAddress.postcode = address.postcode;
                            selectedAddress.telephone = address.telephone;
                            selectedAddress.save_in_address_book = address.save_in_address_book;
                            if(typeof address.region.region!='undefined'){
                                selectedAddress.region= address.region.region;
                            }
                        }
                    });
                }
            }
            return selectedAddress;
        },
        getShippingAddress : function(){
            var self = this,selectedAddress = {},customerDataAddress;
            if(self.isUserLoggedIn()){
                customerDataAddress = customerData.get('customer')();
                window.walletpayObj.customerData = customerDataAddress;
                window.walletpayObj.customeraddresses(customerDataAddress.customer_address);
                if(typeof window.walletpayObj.customerData.customer_address !=='undefined'){
                    addressList = window.walletpayObj.customerData.customer_address;
                    _.each(addressList,function(address){
                        if(address.default_shipping){
                            selectedAddress.firstname = address.firstname;
                            selectedAddress.lastname = address.lastname;
                            selectedAddress.street = address.street;
                            selectedAddress.city = address.city;
                            selectedAddress.region_id = address.region_id;
                            selectedAddress.country_id = address.country_id;
                            selectedAddress.postcode = address.postcode;
                            selectedAddress.telephone = address.telephone;
                            selectedAddress.save_in_address_book = address.save_in_address_book;
                            if(typeof address.region.region!='undefined'){
                                selectedAddress.region= address.region.region;
                            }
                        }
                    });
                }
            }
            return selectedAddress;
        },
        reloadSections : function(){
            var self = this;
            var shippingAddress = window.walletpayObj.selectedShippingAddress();
            var shippingMethod = window.walletpayObj.selectedShippingMethod();

            if(shippingAddress.length == 0 || (typeof shippingAddress.city =='undefined')){
                if($("#new-address-form").length){
                    self.fetchShippingNewAddress();
                }
            }
            self.updateTotals(shippingMethod);
            self.fetchShippingByAddress(shippingAddress);
        },
        setBillingAddressFromExistingAddress : function(address){
            var self = this,selectedAddress={};
            if(typeof address.firstname!='undefined'){
                selectedAddress.firstname = address.firstname;
            }
            if(typeof address.lastname!='undefined'){
                selectedAddress.lastname = address.lastname;
            }
            if(typeof address.street!='undefined'){
                selectedAddress.street = address.street;
            }
            if(typeof address.city!='undefined'){
                selectedAddress.city = address.city;
            }
            if(typeof address.region_id!='undefined'){
                selectedAddress.region_id = address.region_id;
            }
            if(typeof address.country_id!='undefined'){
                selectedAddress.country_id = address.country_id;
            }
            if(typeof address.postcode!='undefined'){
                selectedAddress.postcode = address.postcode;
            }
            if(typeof address.telephone!='undefined'){
                selectedAddress.telephone = address.telephone;
            }
            if(typeof address.save_in_address_book!='undefined'){
                selectedAddress.save_in_address_book = address.save_in_address_book;
            }
            if(typeof address.region!=='undefined' && address.region!==null){
                if(typeof address.region.region!='undefined'){
                    selectedAddress.region= address.region.region;
                }
            }
            window.walletpayObj.selectedBillingAddress(selectedAddress);
            if(!window.walletpayObj.isRequiredShipping()){
                window.walletpayObj.fetchTotals(selectedAddress,{});
            }
        },
        fetchDefaultShippingRates: function(){
            var selectedAddress = {
                'firstname': '',
                'lastname': '',
                'street': [],
                'city': '',
                'region_id': 0,
                'postcode': '',
                'country_id': window.walletpayObj.default_country_code,
            }
            var payload = {
                address : selectedAddress
            };
            if(!window.walletpayObj.currentQuoteid){
                return false;
            }
            if(!window.walletpayObj.isUserActiveSession()){
                if(!window.walletpayObj.currentQuoteMaskedId){
                    return false;
                }
            }
            var checkoutObj = {
                isCustomerLoggedIn : window.walletpayObj.isUserLoggedIn(),
                store_code : window.walletpayObj.storeCode,
                guest_masked_quote_id : window.walletpayObj.currentQuoteMaskedId,
                payload : payload
            }
            window.walletpayObj.isLoadingShippingMethod(true);
            checkoutUtils.fetchShippingRates(
                checkoutObj
            ).done(
                function (apiresponse) {
                    var response = (apiresponse);
                    var shippingMethodList = [];
                    if(response.length){
                        _.each(response,function(value){
                            var titledesc = priceUtils.formatPrice(value.amount, window.walletpayObj.priceFormat)+' '+value.carrier_title+' - '+ value.method_title;
                            shippingMethodList.push({
                                "id": value.carrier_code+'_'+value.method_code,
                                "description": titledesc,
                                "amount": value.amount,
                                "carrier_code": value.carrier_code,
                                "carrier_title": value.carrier_title,
                                "method_code": value.method_code,
                                "method_title": value.method_title,
                            });
                        })
                        if(typeof selectedAddress.region_id=='undefined'){
                            selectedAddress.region_id = 0;
                        }
                        if(selectedAddress.region_id==''){
                            selectedAddress.region_id = 0;
                        }

                        $.localStorage.set('wp-default-shipping-method', shippingMethodList);
                        $.localStorage.set('wp-default-shipping-address', selectedAddress);

                    }
                }
            ).fail(
                function (response) {
                    console.log("Error:", response);
                    window.walletpayObj.isLoadingShippingMethod(false);
                }
            )
        },
        fetchRatesByDynamicAddress : function(address,callback){
            var self = this,apiUrl=null,selectedAddress={};
            if(typeof address.firstname!='undefined'){
                selectedAddress.firstname = address.firstname;
            }
            if(typeof address.lastname!='undefined'){
                selectedAddress.lastname = address.lastname;
            }
            if(typeof address.street!='undefined'){
                selectedAddress.street = address.street;
            }
            if(typeof address.city!='undefined'){
                selectedAddress.city = address.city;
            }
            if(typeof address.region_id!='undefined'){
                selectedAddress.region_id = address.region_id;
            }
            if(typeof address.country_id!='undefined'){
                selectedAddress.country_id = address.country_id;
            }
            if(typeof address.postcode!='undefined'){
                selectedAddress.postcode = address.postcode;
            }
            if(typeof address.telephone!='undefined'){
                selectedAddress.telephone = address.telephone;
            }
            if(typeof address.save_in_address_book!='undefined'){
                selectedAddress.save_in_address_book = address.save_in_address_book;
            }
            if(typeof address.region!='undefined'  && address.region!==null){
                if(typeof address.region.region!='undefined'){
                    selectedAddress.region= address.region.region;
                }
            }
            var payload = {
                address : selectedAddress
            };
            if(!window.walletpayObj.currentQuoteid){
                return false;
            }
            if(!window.walletpayObj.isUserLoggedIn()){
                if(!window.walletpayObj.currentQuoteMaskedId){
                    return false;
                }
            }
            var checkoutObj = {
                isCustomerLoggedIn : window.walletpayObj.isUserLoggedIn(),
                store_code : window.walletpayObj.storeCode,
                guest_masked_quote_id : window.walletpayObj.currentQuoteMaskedId,
                payload : payload
            }
            window.walletpayObj.isLoadingShippingMethod(true);
            checkoutUtils.fetchShippingRates(
                checkoutObj
            ).done(
                function (apiresponse) {
                    var response = (apiresponse);
                    var shippingMethodList = [];
                    if(response.length){
                        _.each(response,function(value){
                            var titledesc = priceUtils.formatPrice(value.amount, window.walletpayObj.priceFormat)+' '+value.carrier_title+' - '+ value.method_title;
                            shippingMethodList.push({
                                "id": value.carrier_code+'_'+value.method_code,
                                "description": titledesc,
                                "amount": value.amount,
                                "carrier_code": value.carrier_code,
                                "carrier_title": value.carrier_title,
                                "method_code": value.method_code,
                                "method_title": value.method_title,
                            });
                        });
                        $.localStorage.set('wp-default-shipping-method', shippingMethodList);
                        $.localStorage.set('wp-default-shipping-address', selectedAddress);
                        callback();
                    }
                    window.walletpayObj.isLoadingShippingMethod(false);
                }
            ).fail(
                function (response) {
                    console.log("Error:", response);
                    window.walletpayObj.isLoadingShippingMethod(false);
                }
            )
        },
        fetchShippingByAddress : function(address){
            var self = this,apiUrl=null,selectedAddress={};
            if(typeof address.firstname!='undefined'){
                selectedAddress.firstname = address.firstname;
            }
            if(typeof address.lastname!='undefined'){
                selectedAddress.lastname = address.lastname;
            }
            if(typeof address.street!='undefined'){
                selectedAddress.street = address.street;
            }
            if(typeof address.city!='undefined'){
                selectedAddress.city = address.city;
            }
            if(typeof address.region_id!='undefined'){
                selectedAddress.region_id = address.region_id;
            }
            if(typeof address.country_id!='undefined'){
                selectedAddress.country_id = address.country_id;
            }
            if(typeof address.postcode!='undefined'){
                selectedAddress.postcode = address.postcode;
            }
            if(typeof address.telephone!='undefined'){
                selectedAddress.telephone = address.telephone;
            }
            if(typeof address.save_in_address_book!='undefined'){
                selectedAddress.save_in_address_book = address.save_in_address_book;
            }
            if(typeof address.region!='undefined'  && address.region!==null){
                if(typeof address.region.region!='undefined'){
                    selectedAddress.region= address.region.region;
                }
            }
            var payload = {
                address : selectedAddress
            };
            if(!window.walletpayObj.currentQuoteid){
                return false;
            }
            if(!window.walletpayObj.isUserLoggedIn()){
                if(!window.walletpayObj.currentQuoteMaskedId){
                    return false;
                }
            }
            var checkoutObj = {
                isCustomerLoggedIn : window.walletpayObj.isUserLoggedIn(),
                store_code : window.walletpayObj.storeCode,
                guest_masked_quote_id : window.walletpayObj.currentQuoteMaskedId,
                payload : payload
            }
            window.walletpayObj.isLoadingShippingMethod(true);
            checkoutUtils.fetchShippingRates(
                checkoutObj
            ).done(
                function (apiresponse) {
                    var response = (apiresponse);
                    var shippingMethodList = [];
                    if(response.length){
                        _.each(response,function(value){
                            var titledesc = priceUtils.formatPrice(value.amount, window.walletpayObj.priceFormat)+' '+value.carrier_title+' - '+ value.method_title;
                            shippingMethodList.push({
                                "id": value.carrier_code+'_'+value.method_code,
                                "description": titledesc,
                                "amount": value.amount,
                                "carrier_code": value.carrier_code,
                                "carrier_title": value.carrier_title,
                                "method_code": value.method_code,
                                "method_title": value.method_title,
                            });
                        })
                        if(typeof selectedAddress.region_id=='undefined'){
                            selectedAddress.region_id = 0;
                        }
                        if(selectedAddress.region_id==''){
                            selectedAddress.region_id = 0;
                        }

                        window.walletpayObj.availableShippingMethods(shippingMethodList);
                        window.walletpayObj.selectedShippingAddress(selectedAddress);
                        window.walletpayObj.selectedBillingAddress(selectedAddress);

                        if(window.walletpayObj.isUserLoggedIn()){
                            window.walletpayObj.selectedBillingAddress().email = window.walletpayObj.customerDetails.email;
                        }
                    }
                    window.walletpayObj.isLoadingShippingMethod(false);
                }
            ).fail(
                function (response) {
                    console.log("Error:", response);
                    window.walletpayObj.isLoadingShippingMethod(false);
                }
            )
        },
        fetchShippingNewAddress : function(){
            var self = this;
            var shippingFormData = checkoutUtils.fetchValuesFromAddressForm('#new-address-form');

            if(typeof shippingFormData.region_id!='undefined'){
                if(shippingFormData.region_id == ""){
                    shippingFormData.region_id = 0;
                }
            }
            window.walletpayObj.selectedShippingAddress(shippingFormData);
            self.fetchShippingByAddress(shippingFormData);
        },
        updateTotals : function(shippingOption){
            var self = this;
            window.walletpayObj.selectedShippingMethod(shippingOption);
            window.walletpayObj.fetchTotals(
                window.walletpayObj.selectedShippingAddress(),
                shippingOption
            );
            return true;
        },
        fetchTotals : function(shippingAddress,shippingOption){
            var self = this;
            var countryCode = shippingAddress.country_id;
            var checkoutObj = {
                'addressInformation' :{
                    'address' :{
                        'countryId' : countryCode,
                        'region': shippingAddress.region,
                        'postcode': shippingAddress.postcode
                    }
                },
                store_code : window.walletpayObj.storeCode,
                quote_masked_id : window.walletpayObj.currentQuoteMaskedId,
                isLoggedin : window.walletpayObj.isUserLoggedIn()
            }

            if(Object.keys(shippingOption).length >0){
                checkoutObj.addressInformation.shipping_method_code = shippingOption.method_code;
                checkoutObj.addressInformation.shipping_carrier_code = shippingOption.carrier_code;
            }

            if(typeof shippingAddress.region_id !='undefined'){
                checkoutObj.addressInformation.address.regionId = shippingAddress.region_id;
            }

            window.walletpayObj.isLoadingCheckoutActions(true);
            checkoutUtils.fetchTotals(
                checkoutObj
            ).done(
                function (apiresponse) {
                    var response = (apiresponse);
                    window.walletpayObj.grandtotal(response.base_grand_total);
                    window.walletpayObj.grandtotalFormatted(response.base_grand_total);
                    window.walletpayObj.currencyCode(response.quote_currency_code);
                    window.walletpayObj.isLoadingCheckoutActions(false);
                    self.updateTotalSegments(response.total_segments);
                }
            ).fail(
                function (response) {
                    window.walletpayObj.isLoadingCheckoutActions(false);
                }
            )
        },
        initCheckout : function(){
            var self = this;
            var gpayButtontext = self.googlepayOptions.gpay_button_popup_text;
            var applepayButtontext = self.applepayOptions.applePayPopUpButtonText;
            if(gpayButtontext == ''){
                gpayButtontext = $.mage.__('Place Order with GooglePay');
            }
            if(applepayButtontext == ''){
                applepayButtontext = $.mage.__('Place Order with ApplePay');
            }

            var popupButtons = [];
            // googlepay button if enabled
            if(self.googlepayOptions.isgooglepayenabledonpdp && self.isApplePayPurchase() == false){
                popupButtons.push({
                    text: gpayButtontext,
                    class: 'place-order-gpay',
                    click: function () {
                        self.preparePayment(false);
                    }
                });
            }
            // applepay button if enabled
            if(self.applepayOptions.isApplePayEnableonPdp && (self.isApplePayPurchase() == true)){
                popupButtons.push({
                    text: applepayButtontext,
                    class: 'place-order-applepay',
                    click: function () {
                        self.preparePayment(true);
                    }
                });
            }
            // cancel button on popup
            popupButtons.push({
                text: $.mage.__('Cancel'),
                class: 'cancel-gpay',
                click: function () {
                    var that = this;
                    var apiUrl = url.build('worldpay/wallets/cancelCheckout');
                    var payload = {};
                    $("body").trigger('processStart');
                    storage.post(
                        apiUrl,
                        JSON.stringify(payload)
                    ).done(function(response){
                        $("body").trigger('processStop');
                        location.reload();
                        that.closeModal();
                    }).fail(function(response){
                        $("body").trigger('processStop');
                        console.log('Failure ',response);
                    });
                }
            })
            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                clickableOverlay : false,
                buttons: popupButtons,
                modalClass: 'wp-wallets-pay-modal',
                opened : function(){
                    $(".action-close").hide();
                    if(self.isUserLoggedIn()){
                        var shippingAddress = self.getShippingAddress();
                        //var BillingAddress = self.getShippingAddress();
                        if(shippingAddress){
                            var shippingMethod = self.fetchShippingByAddress(shippingAddress);
                        }
                    }else{
                        var guestShippingAddress = {
                            country_id: window.walletpayObj.default_country_code,
                        }
                        var shippingMethod = self.fetchShippingByAddress(guestShippingAddress);
                    }
                }
            };
                let gpayCheckoutPopup = $('.gpay-checkout-popup');
                let gpayPopup = modal(options, gpayCheckoutPopup);
                gpayCheckoutPopup.modal('openModal');
                self.countriesDropDown(self.countriesHtml);
                self.BillingCountriesDropDown(self.billingCountriesHtml);
        },
        isUserLoggedIn : function(){

            return this.isUserActiveSession();
           /* var self=this,customer = customerData.get('customer')();
            if (customer.fullname && customer.firstname)
            {
                return true;
            }
            if(typeof self.customerDetails.email !='undefined'){
                return true;
            }
            return false;*/
        },
        isValidJson : function(jsonString){
            var json = null;
            try {
                var o = JSON.parse(jsonString);
                if (o && typeof o === "object") {
                    json =  o;
                }
            }catch(e){
                console.log("JSON not valid",e);
            }
            return json;
        },
        addGooglePayPurchaseBtn : function(){
            var self = this;
            self.googlepayOptions.gpay_button_customisation = {
                "buttonColor": self.googlepayOptions.gpaybutton_color,
                "buttonType": self.googlepayOptions.gpaybutton_type,
                "buttonLocale": self.googlepayOptions.gpaybutton_locale
            }
            var additionalData = {
                "env_mode": self.env_mode,
                "currencyCode": window.walletpayObj.currencyCode(),
                "baseRequest": self.googlepayOptions.baseRequest,
                "allowedCardAuthMethods": self.googlepayOptions.allowedCardAuthMethods,
                "allowedCardNetworks": self.googlepayOptions.allowedCardNetworks,
                "tokenizationSpecification": self.googlepayOptions.tokenizationSpecification,
                "google_btn_customisation": self.googlepayOptions.gpay_button_customisation
            }
            GooglePayModel.addGooglePayButton(self.googlepayOptions.container,additionalData,self.addtoCartAndInitCheckout);
        },
        addtocartFormData : function(){
            var self = this,
            form = $(window.walletpayObj.productFormSelector),
            productIds = idsResolver(form),
            productInfo = productInfoResolver(form);
            return{
                'form' : form,
                'productIds': productIds,
                'productInfo': productInfo
            }
        },
        isEnableRestoreCart : function(){
            return false;
        },
        addtoCartAndInitCheckout : function(){
            var self = this,
                addTocartFormData = window.walletpayObj.addtocartFormData(),
                triggerCheckout = false;

            if (!(addTocartFormData.form.validation() && addTocartFormData.form.validation('isValid'))) {
                return;
            }
            var cart = customerData.get('cart');

            $( window.walletpayObj.minicartSelector).trigger('contentLoading');
            var formData = new FormData(addTocartFormData.form[0]);
            if(typeof cart().quote_id!='undefined'){
                formData.set("existing_quote_id",cart().quote_id);
            }
            $.ajax({
                url: addTocartFormData.form.attr('action'),
                data: formData,
                type: 'post',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,

                /** @inheritdoc */
                beforeSend: function () {
                    $("body").trigger('processStart');
                },

                /** @inheritdoc */
                success: function (res) {
                    var eventData, parameters;
                    $(document).trigger('ajax:addToCart', {
                        'sku': addTocartFormData.form.data().productSku,
                        'productIds': addTocartFormData.productIds,
                        'productInfo': addTocartFormData.productInfo,
                        'form': addTocartFormData.form,
                        'response': res
                    });
                    if(res.backUrl){
                        if (res.backUrl.indexOf("checkout") > -1) {
                        }else{
                            location.reload(); // out of stock
                            return false;
                        }
                    }

                    if (res.messages) {
                        $( window.walletpayObj.messagesSelector).html(res.messages);
                    }

                    if (res.minicart) {
                        $( window.walletpayObj.minicartSelector).replaceWith(res.minicart);
                        $( window.walletpayObj.minicartSelector).trigger('contentUpdated');
                    }

                    triggerCheckout = true;
                    /** Init Google Pay popup after cart refresh  */
                    var cart = customerData.get('cart');
                    var count = cart().summary_count;
                    var quoteId = null;
                    if(typeof cart().quote_id !='undefined'){
                        quoteId = cart().quote_id;
                    }

                    var showMiniCheckout = false;
                    var currentItemId = formData.get('item');
                        cart.subscribe(function (changedCart) {
                            if(triggerCheckout == true){
                                 $("body").trigger('processStop');
                                count = changedCart.summary_count;
                                if(count > 0){
                                    window.walletpayObj.isCartContainVirtualProduct(changedCart.items,currentItemId);
                                    window.walletpayObj.currentQuoteid =changedCart.quote_id;
                                    window.walletpayObj.currentQuoteMaskedId =changedCart.quote_masked_id;
                                    window.walletpayObj.initCheckout();
                                    window.walletpayObj.grandtotal(changedCart.subtotalAmount);
                                    window.walletpayObj.grandtotalFormatted(priceUtils.formatPrice(changedCart.subtotalAmount, window.walletpayObj.priceFormat));
                                    window.walletpayObj.updateTotalSegments( {

                                        subtotal : {
                                            "title" : 'Subtotal',
                                            "value" : changedCart.subtotalAmount
                                        },
                                        grandtotal:{
                                            "title" : 'Grand Total',
                                            "value" : changedCart.subtotalAmount
                                        }
                                    });
                                    window.walletpayObj.fetchDefaultShippingRates();

                                    var productInfo ={};
                                    _.each(changedCart.items,function(value,key){
                                        productInfo.name = value.product_name;
                                        productInfo.options = value.options;
                                        productInfo.image = value.product_image.src;
                                        productInfo.subtotal = changedCart.subtotal;

                                    });


                                     /** PDP segments **/
                                     window.walletpayObj.updatePdpCartSegments(
                                        {
                                            productInfo : productInfo
                                        }
                                    )
                                }
                                triggerCheckout = false;
                             }
                        });

                },
                /** @inheritdoc */
                error: function (res) {
                    console.log("Error: ", res);
                    $(document).trigger('ajax:addToCart:error', {
                        'sku': form.data().productSku,
                        'productIds': productIds,
                        'productInfo': productInfo,
                        'form': form,
                        'response': res
                    });
                    location.reload();
                },

                /** @inheritdoc */
                complete: function (res) {
                    if (res.state() === 'rejected') {
                        location.reload();
                    }
                }
            });
        },
        isLoaderEnabled: function () {
            return this.processStart && this.processStop;
        },
        isCartContainVirtualProduct : function(items,currentItemId){
            var self = this;
            _.each(items,function(value,key){
                if(value.product_id == currentItemId){
                    if(typeof value.product_type!= 'undefined'){
                        if(value.product_type == 'virtual' || value.product_type == 'downloadable' ){
                            self.isRequiredShipping(false);
                            return true;
                        }
                    }
                }
            });
            return false;
        },
        addtoCartandInitApplePay : function(){
            var self = this;
            self.isApplePayPurchase(true);
            self.addtoCartAndInitCheckout();
        },
        initApplePaySession : function() {
            var self= this;
            var cartData = customerData.get('cart');
            //var baseGrandTotal   = cartData().subtotalAmount;
            var baseGrandTotal = window.walletpayObj.grandtotal();
            var runningAmount = (Math.round(baseGrandTotal * 100) / 100).toFixed(2);
            var subTotalDescr      = "Cart Subtotal";
            var currencyCode = window.walletpayObj.currencyCode();
            var countryCode = window.walletpayObj.selectedBillingAddress().country_id
            if(window.walletpayObj.isRequiredShipping()) {
            var countryCode = window.walletpayObj.selectedShippingAddress().country_id;
            }
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
            ApplePayModel.initApplePaySession(paymentRequest);
        },
        // Send Payment token
        applePaySendPaymentToken : function(paymentToken){
            return new Promise(function(resolve, reject) {
                var appleResponse = paymentToken;
                if ( debug == true )
                resolve(true);
                else
                reject;
                });
        },
        // Perform Validation
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
        addApplePayButton : function(){
            var self= this;
            if (window.ApplePaySession) {
                var merchantIdentifier = window.walletpayObj.applepayOptions.appleMerchantId;
                var promise = ApplePaySession.canMakePaymentsWithActiveCard(merchantIdentifier);
                promise.then(function (canMakePayments) {
                    if (canMakePayments) {
                        self.isAppleDevice(true);
                    }
                });
                self.isAppleDevice(true);


            }
        }
    });
});