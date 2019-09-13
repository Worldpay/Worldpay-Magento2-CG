/*browser:true*/
/*global define*/
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/redirect-on-success',
         'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/full-screen-loader',
        'ko'
    ],
    function (Component, $, quote, customer,validator, url, placeOrderAction, redirectOnSuccessAction,errorProcessor, urlBuilder, storage, fullScreenLoader, ko) {
        'use strict';
        var ccTypesArr = ko.observableArray([]);
        var paymentService = false;
        var billingAddressCountryId = "";
        if (quote.billingAddress()) {
            billingAddressCountryId = quote.billingAddress._latestValue.countryId;
        }
        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                redirectTemplate: 'Sapient_Worldpay/payment/apm',
                idealBankType:null
            },

            initialize: function () {
                this._super();
                this.selectedCCType(null);
                if(paymentService == false){
                    this.filterajax(1);
                }
            },

            initObservable: function () {
                var that = this;
                this._super();
                quote.billingAddress.subscribe(function (newAddress) {
                    that.checkPaymentTypes();
                    if (quote.billingAddress._latestValue != null && quote.billingAddress._latestValue.countryId != billingAddressCountryId) {
                        billingAddressCountryId = quote.billingAddress._latestValue.countryId;
                        that.filterajax(1);
                        paymentService = true;                 
                    }
               });
            return this;
            },

            filterajax: function(statusCheck){
                if(!statusCheck){
                    return;
                }
                if (quote.billingAddress._latestValue == null) {
                    return;
                }
                var ccavailabletypes = this.getCcAvailableTypes();
                var filtercclist = {};
                var cckey,ccvalue;
                var serviceUrl = urlBuilder.createUrl('/worldpay/payment/types', {});
                 var payload = {
                    countryId: quote.billingAddress._latestValue.countryId
                };

                 fullScreenLoader.startLoader();

                 storage.post(
                    serviceUrl, JSON.stringify(payload)
                ).done(
                    function (apiresponse) {
                        var response = JSON.parse(apiresponse);
                        if(response.length){
                            $.each(response, function(responsekey, value){
                                var found = false;
                                $.each(ccavailabletypes, function(key, value){
                                    if(response[responsekey] == key.toUpperCase()){
                                        found = true;
                                        cckey = key;
                                        ccvalue = ccavailabletypes[key];
                                        return false;
                                    }
                                });
                                if(found){
                                    filtercclist[cckey] = ccvalue;
                                }
                            });
                        }else{
                            filtercclist = ccavailabletypes;
                        }

                        var ccTypesArr1 = _.map(filtercclist, function (value, key) {
                            return {
                             'ccValue': key,
                             'ccLabel': value
                            };
                        });

                        fullScreenLoader.stopLoader();
                        ccTypesArr(ccTypesArr1);
                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response);
                        fullScreenLoader.stopLoader();
                    }
                );
            },

            getCcAvailableTypesValues : function(){
                 return ccTypesArr;
            },

             availableCCTypes : function(){
               return ccTypesArr;
            },
            selectedCCType : ko.observable(),
            selectedIdealBank:ko.observable(),
            getTemplate: function(){
                    return this.redirectTemplate;
            },

            getCode: function() {
                return 'worldpay_apm';
            },
            getTitle: function() {
               return window.checkoutConfig.payment.ccform.apmtitle ;
            },

            isActive: function() {
                return true;
            },
            /**
             * @override
             */
            getData: function () {
                return {
                    'method': "worldpay_apm",
                    'additional_data': {
                        'cc_type': this.getselectedCCType(),
                        'cc_bank': this.idealBankType
                    }
                };
            },
             getselectedCCType : function(){
                if(this.paymentMethodSelection()=='radio'){
                     return $("input[name='apm_type']:checked").val();
                    } else{
                      return  this.selectedCCType();
                }
            },
            getIdealBankList: function() {
                 var bankList = _.map(window.checkoutConfig.payment.ccform.apmIdealBanks, function (value, key) {
                                       return {
                                        'bankCode': key,
                                        'bankText': value
                                    };
                                });
                return ko.observableArray(bankList);
            },
            paymentMethodSelection: function() {
                return window.checkoutConfig.payment.ccform.paymentMethodSelection;
            },
            preparePayment:function() {
                var self = this;
                var $form = $('#' + this.getCode() + '-form');
                if($form.validation() && $form.validation('isValid')){
                    if (this.getselectedCCType() =='IDEAL-SSL') {
                        this.idealBankType = this.selectedIdealBank();
                    }
                    self.placeOrder();
                } else {
                    return $form.validation() && $form.validation('isValid');
                }
            },
            getIcons: function (type) {
                return window.checkoutConfig.payment.ccform.wpicons.hasOwnProperty(type) ?
                    window.checkoutConfig.payment.ccform.wpicons[type]
                    : false;
            },

            afterPlaceOrder: function (data, event) {
                window.location.replace(url.build('worldpay/redirectresult/redirect'));
            },
            checkPaymentTypes: function (data, event){
                if (data && data.ccValue) {
                    if (data.ccValue=='IDEAL-SSL') {
                        $(".ideal-block").show();
                        $("#ideal_bank").prop('disabled',false);
                    }else{
                        $("#ideal_bank").prop('disabled',true);
                        $(".ideal-block").hide();
                    }
                }else if(data){
                    if (data.selectedCCType() && data.selectedCCType() == 'IDEAL-SSL') {
                        $(".ideal-block").show();
                        $("#ideal_bank").prop('disabled',false);
                    }else{
                        $("#ideal_bank").prop('disabled',true);
                        $(".ideal-block").hide();
                    }
                } else {
                    $("#ideal_bank").prop('disabled',true);
                    $(".ideal-block").hide();
                }
            }
        });
    }
);
