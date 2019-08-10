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
        'ko',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/full-screen-loader',
        'hmacSha256',
        'encBase64'
    ],
    function (Component, $, quote, customer,validator, url, placeOrderAction, redirectOnSuccessAction,ko, setPaymentInformationAction, errorProcessor, urlBuilder, storage, fullScreenLoader, hmacSha256, encBase64) {
        'use strict';
        //Valid card number or not.
        var ccTypesArr = ko.observableArray([]);
        var filtersavedcardLists = ko.observableArray([]);
        var paymentService = false;
        var billingAddressCountryId = "";
        var dfReferenceId = "";
        if (quote.billingAddress()) {
            billingAddressCountryId = quote.billingAddress._latestValue.countryId;
        }
        $.validator.addMethod('worldpay-validate-number', function (value) {
            if (value) {
                return evaluateRegex(value, "^[0-9]{12,20}$");
            }
        }, $.mage.__('Card number should contain between 12 and 20 numeric characters.'));

        //Valid Card or not.
        $.validator.addMethod('worldpay-cardnumber-valid', function (value) {
            return doLuhnCheck(value);
        }, $.mage.__('The card number entered is invalid.'));

        //Regex for valid card number.
        function evaluateRegex(data, re) {
            var patt = new RegExp(re);
            return patt.test(data);
        }

        function doLuhnCheck(value) {
            var nCheck = 0;
            var nDigit = 0;
            var bEven = false;
            value = value.replace(/\D/g, "");

            for (var n = value.length - 1; n >= 0; n--) {
                var cDigit = value.charAt(n);
                nDigit = parseInt(cDigit, 10);

                if (bEven) {
                    if ((nDigit *= 2) > 9) {
                        nDigit -= 9;
                    }
                }

                nCheck += nDigit;
                bEven = !bEven;
            }

            return (nCheck % 10) === 0;
        }
        
        // 3DS2 part Start
        
        var jwtUrl = url.build('worldpay/hostedpaymentpage/jwt');
        
        function createJwt(cardNumber){
            var bin = cardNumber;
            $('body').append('<iframe src="'+jwtUrl+'?cardNumber='+bin+'" name="jwt_frm" id="jwt_frm" style="display: none"></iframe>');
        }
        
        // 3DS2 part End
        
        return Component.extend({
            defaults: {
                intigrationmode: window.checkoutConfig.payment.ccform.intigrationmode,
                redirectAfterPlaceOrder: (window.checkoutConfig.payment.ccform.intigrationmode == 'direct') ? true : false,
                direcTemplate: 'Sapient_Worldpay/payment/direct-cc',
                redirectTemplate: 'Sapient_Worldpay/payment/redirect-cc',
                cardHolderName:'',
                SavedcreditCardVerificationNumber:'',
                saveMyCard:false,
                cseData:null
            },

            initialize: function () {
                this._super();
                this.selectedCCType(null);
                if(paymentService == false){
                    this.filtercardajax(1);
                }
            },
            initObservable: function () {
                var that = this;
                this._super();
                quote.billingAddress.subscribe(function (newAddress) {
                    if (quote.billingAddress._latestValue != null  && quote.billingAddress._latestValue.countryId != billingAddressCountryId) {
                        billingAddressCountryId = quote.billingAddress._latestValue.countryId;
                        that.filtercardajax(1);
                        paymentService = true;
                    }
                });
            return this;
            },
            filtercardajax: function(statusCheck = null){
                if(!statusCheck){
                    return;
                }
                if (quote.billingAddress._latestValue == null) {
                    return;
                }
                var ccavailabletypes = this.getCcAvailableTypes();
                var savedcardlists = window.checkoutConfig.payment.ccform.savedCardList;
                var filtercclist = {};
                var filtercards = [];
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
                                if (savedcardlists.length) {
                                    $.each(savedcardlists, function(key, value){
                                        var method = savedcardlists[key]['method'];
                                        if (typeof method == 'undefined') {
                                            return true;
                                        }
                                        // commented for saved debit card access
//                                        var found = false;
//                                        $.each(response, function(responsekey, value){
//                                            if(method.toUpperCase() == response[responsekey]){
//                                                found = true;
//                                                return false;
//                                            }
//                                        });
//                                        if(found){
                                            filtercards.push(savedcardlists[key]);
                                        //}
                                    });
                                }

                                for (var responsekey in response) {
                                       var found = false;
                                      for(var key in ccavailabletypes) {
                                            if(key != 'savedcard'){
                                                if(response[responsekey] == key.toUpperCase()){
                                                    found = true;
                                                    cckey = key;
                                                    ccvalue = ccavailabletypes[key];
                                                    break;
                                                }
                                            }
                                      }

                                      if(found){
                                        filtercclist[cckey] = ccvalue;
                                      }
                                }
                                if(filtercards.length){
                                    filtercclist['savedcard'] = ccavailabletypes['savedcard'];
                                }
                             }else{
                               filtercclist = ccavailabletypes;
                               filtercards = savedcardlists;
                             }

                             var ccTypesArr1 = _.map(filtercclist, function (value, key) {
                               return {
                                'ccValue': key,
                                'ccLabel': value
                            };
                         });
                         fullScreenLoader.stopLoader();
                         ccTypesArr(ccTypesArr1);
                         filtersavedcardLists(filtercards);
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
            paymentToken:ko.observable(),

            getCode: function() {
                return 'worldpay_cc';
            },

            loadEventAction: function(data, event){
                if ((data.ccValue)) {
                    if (data.ccValue=="savedcard") {
                        $("#saved-Card-Visibility-Enabled").show();
                        $(".cc-Visibility-Enabled").children().prop('disabled',true);
                        $("#saved-Card-Visibility-Enabled").children().prop('disabled',false);
                        $(".cc-Visibility-Enabled").hide();
                        $("#worldpay_cc_save-card_div").hide();
                    }else{
                        $("#worldpay_cc_save-card_div").show();
                        $(".cc-Visibility-Enabled").children().prop('disabled',false);
                        $("#saved-Card-Visibility-Enabled").children().prop('disabled',true);
                        $("#saved-Card-Visibility-Enabled").hide();
                        $(".cc-Visibility-Enabled").show();
                    }
                } else {
                    if (data.selectedCCType() =="savedcard") {
                        $("#saved-Card-Visibility-Enabled").show();
                        $(".cc-Visibility-Enabled").children().prop('disabled',true);
                        $("#saved-Card-Visibility-Enabled").children().prop('disabled',false);
                        $(".cc-Visibility-Enabled").hide();
                        $("#worldpay_cc_save-card_div").hide();
                    }else{
                        $("#worldpay_cc_save-card_div").show();
                        $(".cc-Visibility-Enabled").children().prop('disabled',false);
                        $("#saved-Card-Visibility-Enabled").children().prop('disabled',true);
                        $("#saved-Card-Visibility-Enabled").hide();
                        $(".cc-Visibility-Enabled").show();
                    }
                }
            },
            getTemplate: function(){
                if (this.intigrationmode == 'direct') {
                    return this.direcTemplate;
                } else{
                    return this.redirectTemplate;
                }
            },
            threeDSEnabled: function(){
                return window.checkoutConfig.payment.ccform.is3DSecureEnabled;
            },

            getSavedCardsList:function(){
                return filtersavedcardLists;
            },

            getSavedCardsCount: function(){
                return window.checkoutConfig.payment.ccform.savedCardCount;
            },
             /**
             * Get payment icons
             * @param {String} type
             * @returns {Boolean}
             */
            getIcons: function (type) {
                return window.checkoutConfig.payment.ccform.wpicons.hasOwnProperty(type) ?
                    window.checkoutConfig.payment.ccform.wpicons[type]
                    : false;
            },

            getTitle: function() {
               return window.checkoutConfig.payment.ccform.cctitle ;
            },
            hasVerification:function() {
               return window.checkoutConfig.payment.ccform.isCvcRequired ;
            },
            getSaveCardAllowed: function(){
                if(customer.isLoggedIn()){
                    return window.checkoutConfig.payment.ccform.saveCardAllowed;
                }
            },
            isActive: function() {
                return true;
            },
            paymentMethodSelection: function() {
                return window.checkoutConfig.payment.ccform.paymentMethodSelection;
            },
            getselectedCCType : function(inputName){
                if(this.paymentMethodSelection()=='radio'){
                     return $("input[name='"+inputName+"']:checked").val();
                    } else{
                      return  this.selectedCCType();
                }
            },

            /**
             * @override
             */
            getData: function () {
                return {
                    'method': "worldpay_cc",
                    'additional_data': {
                        'cc_cid': this.creditCardVerificationNumber(),
                        'cc_type': this.getselectedCCType('payment[cc_type]'),
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.creditCardNumber(),
                        'cc_name': $('#' + this.getCode() + '_cc_name').val(),
                        'save_my_card': this.saveMyCard,
                        'cse_enabled': this.isClientSideEncryptionEnabled(),
                        'encryptedData': this.cseData,
                        'tokenCode': this.paymentToken,
                        'saved_cc_cid': $('.saved-cvv-number').val(),
                        'isSavedCardPayment': this.isSavedCardPayment,
                        'dfReferenceId': this.dfReferenceId
                    }
                };
            },
            isClientSideEncryptionEnabled:function(){
                if (this.getCsePublicKey()) {
                    return window.checkoutConfig.payment.ccform.cseEnabled;
                }
                return false;
            },
             getCsePublicKey:function(){
                return window.checkoutConfig.payment.ccform.csePublicKey;
            },
            getRegexCode:function(cardType){
                if ('AMEX' == cardType) {
                    return /^[0-9]{4}$/;
                }else{
                    return /^[0-9]{3}$/;
                }
            },
            preparePayment:function() {
                var self = this;
                this.redirectAfterPlaceOrder = false;
                this.isSavedCardPayment=false;
                this.paymentToken = null;
                var $form = $('#' + this.getCode() + '-form');
                var $savedCardForm = $('#' + this.getCode() + '-savedcard-form');
                var selectedSavedCardToken = $("input[name='payment[token_to_use]']:checked").val();

                var cc_type_selected = this.getselectedCCType('payment[cc_type]');
                // 3DS2 JWT create function 
                if(window.checkoutConfig.payment.ccform.isDynamic3DS2Enabled){
                    createJwt(this.creditCardNumber());
                }
                
                this.dfReferenceId = null;

                 if(cc_type_selected == 'savedcard'){
                      //Saved card handle
                      if((this.intigrationmode == 'direct' && $savedCardForm.validation() && $savedCardForm.validation('isValid') && selectedSavedCardToken) ||
                        (this.intigrationmode == 'redirect' && $form.validation() && $form.validation('isValid') && selectedSavedCardToken)){
                            var cardType = $("input[name='payment[token_to_use]']:checked").next().val();
                            this.isSavedCardPayment=true;
                            this.paymentToken = selectedSavedCardToken;
                            var savedcvv = $('.saved-cvv-number').val();
                            var res = this.getRegexCode(cardType).exec(savedcvv);
                            if(savedcvv != res){
                                $('#saved-cvv-error').css('display', 'block');
                                $('#saved-cvv-error').html('Please, enter valid Card Verification Number');
                            }else{
                                this.redirectAfterPlaceOrder = false;
                                if(window.checkoutConfig.payment.ccform.isDynamic3DS2Enabled){
                                    window.addEventListener("message", function(event) {
                                    var data = JSON.parse(event.data);
                                    if (event.origin === "https://secure-test.worldpay.com") {
                                    var data = JSON.parse(event.data);
                                        console.warn('Merchant received a message:', data);
                                        if (data !== undefined && data.Status) {
                                            window.sessionId = data.SessionId;

                                            var sessionId = data.SessionId;

                                            if(sessionId){
                                                that.dfReferenceId = sessionId;
                                            }
                                                //place order with direct CSE method
                                                self.placeOrder();
                                            }
                                        }
                                    }, false);
                                } else {
                                    self.placeOrder();
                                }
                            }
                      }
                 }else if($form.validation() && $form.validation('isValid')){
                    //Direct form handle
                    this.saveMyCard = $('#' + this.getCode() + '_save_card').is(":checked");
                     if (this.intigrationmode == 'direct') {
                            var that = this;
                            // Need to check for 3ds2 enable or not
                            //jwtCreate(that.creditCardNumber());
                            var sessionId = window.sessionId;
                            that.dfReferenceId = sessionId;
                            
                            if(this.isClientSideEncryptionEnabled()){
                                require(["https://payments.worldpay.com/resources/cse/js/worldpay-cse-1.0.1.min.js"], function (worldpay) {
                                    worldpay.setPublicKey(that.getCsePublicKey());
                                    var cseData = {
                                        cvc: that.creditCardVerificationNumber(),
                                        cardHolderName: $('#' + that.getCode() + '_cc_name').val(),
                                        cardNumber: that.creditCardNumber(),
                                        expiryMonth: that.creditCardExpMonth(),
                                        expiryYear: that.creditCardExpYear()
                                    };
                                    var encryptedData = worldpay.encrypt(cseData);
                                    that.cseData = encryptedData;
                                    //place order with direct CSE method
                                    that.dfReferenceId = null;
                                    if(window.checkoutConfig.payment.ccform.isDynamic3DS2Enabled){
                                        window.addEventListener("message", function(event) {
                                        var data = JSON.parse(event.data);
                                        if (event.origin === "https://secure-test.worldpay.com") {
                                        var data = JSON.parse(event.data);
                                            console.warn('Merchant received a message:', data);
                                            if (data !== undefined && data.Status) {
                                                window.sessionId = data.SessionId;
                                                var sessionId = data.SessionId;

                                                if(sessionId){
                                                    that.dfReferenceId = sessionId;
                                                }
                                                    self.placeOrder();
                                                }
                                            }
                                        }, false);
                                    } else {
                                        self.placeOrder();
                                    }                                    
                                });
                            } else{
                                if(window.checkoutConfig.payment.ccform.isDynamic3DS2Enabled){  
                                    window.addEventListener("message", function(event) {
                                        var data = JSON.parse(event.data);
                                        if (event.origin === "https://secure-test.worldpay.com") {
                                        var data = JSON.parse(event.data);
                                        console.warn('Merchant received a message:', data);
                                        if (data !== undefined && data.Status) {
                                            window.sessionId = data.SessionId;

                                            var sessionId = data.SessionId;

                                            if(sessionId){
                                                that.dfReferenceId = sessionId;
                                            }
                                                //place order with direct CSE method
                                                self.placeOrder();
                                            }
                                        }
                                    }, false);
                                } else {
                                    self.placeOrder();
                                }  
                            }
                        }else if(this.intigrationmode == 'redirect'){
                        //place order with Redirect CSE Method
                        self.placeOrder();
                    }
                }else {
                    return $form.validation() && $form.validation('isValid');
                }
            },
            afterPlaceOrder: function (data, event) {
                if (this.isSavedCardPayment) {
                    window.location.replace(url.build('worldpay/savedcard/redirect'));
                }else if(this.intigrationmode == 'redirect' && !this.isSavedCardPayment){
                    window.location.replace(url.build('worldpay/redirectresult/redirect'));
                }else if(this.intigrationmode == 'direct' && !this.isSavedCardPayment){
                    window.location.replace(url.build('worldpay/threedsecure/auth'));
                }
            },
            threeDS2Enabled: function(){
                return window.checkoutConfig.payment.ccform.isDynamic3DS2Enabled;
            },
            jwtIssuer: function(){
                return window.checkoutConfig.payment.ccform.isJwtIssuer;
            },
            organisationalUnitId: function(){
                return window.checkoutConfig.payment.ccform.isOrganisationalUnitId;
            },
            testDdcUrl: function(){
                return window.checkoutConfig.payment.ccform.isTestDdcUrl;
            },
        });
    }
);