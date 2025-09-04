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
        'Sapient_Worldpay/js/action/place-multishipping-order',
        'Magento_Checkout/js/action/redirect-on-success',
        'ko',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-billing-address',
        'Magento_Ui/js/model/messageList',
        'Sapient_Worldpay/js/model/disclaimer-confirm',
        'Magento_Checkout/js/view/summary/abstract-total',
        'jquery/ui'
    ],

    function (Component, $, quote, customer,validator, url, placeOrderAction, placeMultishippingOrder, redirectOnSuccessAction,ko, setPaymentInformationAction, errorProcessor, urlBuilder, storage, fullScreenLoader, additionalValidators,setBillingAddressAction,globalMessageList,disclaimerConfirm) {
        'use strict';
        //Valid card number or not.
        var ccTypesArr = ko.observableArray([]);
        var filtersavedcardLists = ko.observableArray([]);
        var isInstalment = ko.observableArray([]);
        var checkInstal = ko.observable();
        var paymentService = false;
        var billingAddressCountryId = "";
        var dfReferenceId = "";
        var disclaimerFlag = null;
        window.disclaimerDialogue = null;
        var ccPayTypes = ko.observableArray([]);
        var ccMethodClass = this;
        if (quote.billingAddress()) {
            billingAddressCountryId = quote.billingAddress._latestValue.countryId;
        }
        $.validator.addMethod('worldpay-validate-number', function (value) {
            if (value) {
                return evaluateRegex(value, "^[0-9]{12,20}$");
            }
        }, $.mage.__(getCreditCardExceptions('CCAM1')));
        $.validator.addMethod('worldpay-validate-cpf-number', function (value) {
            if (value) {
                return (evaluateRegex(value, "^[0-9]{11,11}$") || evaluateRegex(value, "^[0-9]{14,14}$"));
            }
        }, $.mage.__(getCreditCardExceptions('CCAM20')));
        $.validator.addMethod('worldpay-validate-latm-desc', function (value) {
            if (value) {
                return evaluateRegex(value, "^[a-zA-Z0-9 ]+$");
            }
        }, $.mage.__(getCreditCardExceptions('CCAM21')));
        //Valid Card or not.
        $.validator.addMethod('worldpay-cardnumber-valid', function (value) {
            return doLuhnCheck(value);
        }, $.mage.__(getCreditCardExceptions('CCAM0')));

        var typeErrorMsg = $.mage.__('Card number entered does not match with card type selected');
        var cardTypeErrorDisplay = getCreditCardExceptions('CTYP01') ? getCreditCardExceptions('CTYP01') : typeErrorMsg;
        $.validator.addMethod('worldpay-validate-card-type', function (value) {
            if (value) {
                return (checkForCcTypeValidation());
            }
        }, $.mage.__(cardTypeErrorDisplay));

        function checkForCcTypeValidation() {
            var inputName = 'payment[cc_type]';
            var cc_type_selected = $("input[name='"+inputName+"']:checked").val();
            var typeclasslist = document.getElementsByClassName('ccnumber_withcardtype')[0].classList;
            if (cc_type_selected !== 'savedcard') {
                if (cc_type_selected === 'VISA-SSL' && typeclasslist.contains('is_visa')) {
                    return true;
                } else if (cc_type_selected === 'ECMC-SSL' && typeclasslist.contains('is_mastercard')) {
                    return true;
                } else if (cc_type_selected === 'CB-SSL' &&
                    (typeclasslist.contains('is_mastercard') || typeclasslist.contains('is_maestro') || typeclasslist.contains('is_visa'))) {
                    return true;
                } else if (cc_type_selected === 'CARTEBLEUE-SSL' && typeclasslist.contains('is_mastercard')){
                    return true;
                } else if (cc_type_selected === 'AMEX-SSL' && typeclasslist.contains('is_amex')) {
                    return true;
                } else if (cc_type_selected === 'DISCOVER-SSL' && typeclasslist.contains('is_discover')) {
                    return true;
                } else if (cc_type_selected === 'DINERS-SSL' && typeclasslist.contains('is_diners')) {
                    return true;
                } else if (cc_type_selected === 'MAESTRO-SSL' && typeclasslist.contains('is_maestro')) {
                    return true;
                } else if (cc_type_selected === 'JCB-SSL' && typeclasslist.contains('is_jcb')) {
                    return true;
                } else if (cc_type_selected === 'DANKORT-SSL' && typeclasslist.contains('is_dankort')) {
                    return true;
                } else {
                    return false;
                }
            }
        }
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
        function getCreditCardExceptions (exceptioncode){
            var ccData=window.checkoutConfig.payment.ccform.creditcardexceptions;
              for (var key in ccData) {
                if (ccData.hasOwnProperty(key)) {
                    var cxData=ccData[key];
                    if(cxData['exception_code'].includes(exceptioncode)){
                        return cxData['exception_module_messages']?cxData['exception_module_messages']:cxData['exception_messages'];
                    }
                }
            }
        }

        // 3DS2 part Start

        var jwtUrl = url.build('worldpay/hostedpaymentpage/jwt');

        function createJwt(cardNumber){
            var bin = cardNumber;
            var encryptedBin = btoa(bin);
            $('body').append('<iframe src="'+jwtUrl+'?instrument='+encryptedBin+'" name="jwt_frm" id="jwt_frm" style="display: none"></iframe>');
        }

        // 3DS2 part End

        return Component.extend({
            defaults: {
                intigrationmode: window.checkoutConfig.payment.ccform.intigrationmode,
                redirectAfterPlaceOrder: (window.checkoutConfig.payment.ccform.intigrationmode == 'direct') ? true : false,
                direcTemplate: 'Sapient_Worldpay/payment/direct-cc',
                redirectTemplate: 'Sapient_Worldpay/payment/redirect-cc',
                multishippingDirectTemplate: 'Sapient_Worldpay/multishipping/direct-cc',
                multishippingRedirectTemplate: 'Sapient_Worldpay/multishipping/redirect-cc',
                cardHolderName:'',
                SavedcreditCardVerificationNumber:'',
                saveMyCard:false,
                cseData: null,
                multishipping: false
            },
            totals: quote.getTotals(),
            showCardTypeDropDown : ko.observable(),
            selectedPayType : ko.observable(),
            isSaveThisCardVisible : ko.observable(true),
            isSaveThisCardReadOnly : ko.observable(false),
            isIframecardPage : false,
            billingCountryId: ko.observable(),
            initialize: function () {
                this._super();
                this.selectedCCType(null);
                this.initPaymentKeyEvents();
                if(paymentService == false){
                    this.filtercardajax(1);
                    this.getInstalmentValues(1);
                    //this.reloadCpfSection();
                }
                window.checkoutConfig.CCMethodClass = this;
                this.showCardTypeDropDown(false);
            },
            initObservable: function () {
                var that = this;
                this._super();
                this._super().observe(['cpfData']);
                quote.billingAddress.subscribe(function (newAddress) {
                    if (quote.billingAddress._latestValue != null  && quote.billingAddress._latestValue.countryId != billingAddressCountryId) {
                        billingAddressCountryId = quote.billingAddress._latestValue.countryId;
                        that.filtercardajax(1);
                        that.getInstalmentValues(1);
                        that.billingCountryId(billingAddressCountryId);
                        //that.reloadCpfSection();
                        paymentService = true;
                    }
                });
                return this;
            },
            /**
             * cpf reload
             *
             * @return {window.Promise}
             */
//        reloadCpfSection: function () {
//
//            return new window.Promise(function (resolve, reject) {
//                if (this.cpfData().isinstalment) {
//                    return resolve();
//                }
//            }.bind(this))
//                    .catch(function (error) {
//                        reject(error);
//                    });
//            },
            getInstalmentValues: function (statusCheck) {
                if (!statusCheck) {
                    return;
                }
                if (quote.billingAddress._latestValue == null) {
                    return;
                }
                var serviceUrl = urlBuilder.createUrl('/worldpay/latam/types', {});
                var filterinstal = {};
                var cckey, ccvalue;
                var payload = {
                    countryId: quote.billingAddress._latestValue.countryId
                };
                fullScreenLoader.startLoader();

                storage.post(
                    serviceUrl, JSON.stringify(payload)
                ).done(
                    function (apiresponse) {
                        var response = (apiresponse);
                        if (response.length) {
                            var str_array = response.split(',');
                            filterinstal[1]='One Payment';
                            for (var i = 0; i < str_array.length; i++) {
                                // Trim the excess whitespace.
                                str_array[i] = str_array[i].replace(/^\s*/, "").replace(/\s*$/, "");
                                // Add additional code here, such as:
                                cckey = str_array[i];
                                ccvalue = str_array[i];
                                filterinstal[cckey] = ccvalue;
                            }
                        }
                        var ccTypesArr1 = _.map(filterinstal, function (value, key) {
                            return {
                                'instalValue': key,
                                'instalccLabel': value
                            };
                        });
                        fullScreenLoader.stopLoader();
                        isInstalment(ccTypesArr1);

                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response);
                        fullScreenLoader.stopLoader();
                    }
                );
            },
            filtercardajax: function(statusCheck){
                var self = this;
                var CreditCardPreSelected = jQuery('.paymentmethods-radio-wrapper [name="payment[cc_type]"]:checked');
                /* Multishipping Code */
                if(this.multishipping){
                    var MultishippingCreditCardPreSelected = jQuery('#p_method_worldpay_cc:checked');
                    if(MultishippingCreditCardPreSelected.length){
                        jQuery('#payment-continue').html("<span>" + ("Place Order") + "</span>");
                        document.getElementById("checkout-agreement-div").style.display = "block";
                        this.selectPaymentMethod();
                    }
                }
                var APMPreSelected = jQuery('.paymentmethods-radio-wrapper [name="apm_type"]:checked');
                var WalletPreSelected = jQuery('.paymentmethods-radio-wrapper [name="wallets_type"]:checked');
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
                            if(quote.isVirtual()){
                                setBillingAddressAction(globalMessageList);
                            }
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
                                // if(self.getIntigrationMode() != 'redirect'){
                                filtercclist['savedcard'] = ccavailabletypes['savedcard'];
                                //}
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

                        if(CreditCardPreSelected.length){
                            jQuery('.paymentmethods-radio-wrapper #'+CreditCardPreSelected[0].id+'[name="payment[cc_type]"]').attr('checked',true).change();
                        }
                        if(APMPreSelected.length){
                            jQuery('.paymentmethods-radio-wrapper #'+APMPreSelected[0].id+'[name="apm_type"]').attr('checked',true).change();
                        }
                        if(WalletPreSelected.length){
                            jQuery('.paymentmethods-radio-wrapper #'+WalletPreSelected[0].id+'[name="wallets_type"]').attr('checked',true).change();
                        }
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
            showCardTypes : function(){
                this.showCardTypeDropDown(true);
            },
            savethiscard : function(obj,event){
                if($(event.target).is(":checked")){
                    if (this.isDisclaimerMessageMandatory() && this.isDisclaimerMessageEnabled() && (window.disclaimerDialogue === null || window.disclaimerDialogue === false)) {
                        $('#disclaimer-error').css('display', 'block');
                        $('#disclaimer-error').html(getCreditCardExceptions('CCAM5'));
                        return false;
                    } else {
                        $('#disclaimer-error').css('display', 'none');
                    }
                    $(event.target).attr( 'checked', true );
                }else{
                    $(event.target).removeAttr( 'checked' );
                }
                return true;
            },
            onSelectCcard:function(obj){
                obj.paymentToken = '';
                obj.isSavedCardPayment = false;
                obj.selectedPayType($('#worldpay_cc_cc_type').val());
                $('.hpp-checkout').show();
                obj.isSaveThisCardVisible(true);
                $("#worldpay_cc_save_card").removeAttr('disabled');
                return true;
            },
            onSelectNewCard:function(obj) {
                $('#saved-Card-Visibility-Enabled').hide();
                obj.isSaveThisCardVisible(true);
                $("#worldpay_cc_save-card_div").show();
                return true;
            },
            onSelectSavedCards:function(obj,ccData){
                obj.selectedPayType('savedcard');
                obj.paymentToken = ccData.token_code;
                obj.isSavedCardPayment=true;
                return true;
            },
            loadSavedCards : function(){
                this.selectedPayType('savedcard');
                this.unselectPreviousSelectedCard('payment[cc_type]');
                this.loadEventAction({
                    'ccValue':'savedcard'
                });
            },
            validateForms : function(selectedData){
                var self= this;
                var $form = $('#' + this.getCode() + '-form');
                var $savedCardForm = $('#' + this.getCode() + '-savedcard-form');
                if(selectedData.additional_data.cc_type == 'savedcard'){
                    if($savedCardForm.validation() && $savedCardForm.validation('isValid')) {
                        selectedData.additional_data.save_my_card = false;
                        return true;
                    }
                }else{
                    if($form.validation() && $form.validation('isValid')){
                        selectedData.additional_data.save_my_card = $('#' + this.getCode() + '_save_card').is(":checked");
                        if(this.isSubscribed()){
                            var saveCardOption = $('#' + this.getCode() + '_save_card').is(":checked");
                            if(!saveCardOption){
                                $('#disclaimer-error').css('display', 'block');
                                $('#disclaimer-error').html(getCreditCardExceptions('CCAM4'));
                                return false;
                            } else {
                                $('#disclaimer-error').html('');
                            }
                        }
                        if(this.isProductOnDemand()){
                            var saveCardOption = $('#' + this.getCode() + '_save_card').is(":checked");
                            if(!saveCardOption){
                                $('#disclaimer-error').css('display', 'block');
                                $('#disclaimer-error').html(getCreditCardExceptions('CCAM30'));
                                return false;
                            } else {
                                $('#disclaimer-error').html('');
                            }
                        }
                        return true;
                    }
                }
                return false;
            },
            afterIframeLoadActions : function(){
                // make other sections readonly and enable only iframe
                $("#checkout-payment-worldpay-container").css(
                    {
                        'z-index': "9999",
                        'pointer-events': "auto"
                    }
                );
                $("#payment").css(
                    {
                        'z-index': "100",
                        'pointer-events': "none",
                        'background': "#f2f2f2"
                    }
                );
                $(".opc-progress-bar").css(
                    {
                        'z-index': "100",
                        'pointer-events': "none",
                    }
                );
                $("#checkout-payment-worldpay-alert-message").show();
                $("#parent-payment-continue").hide();
            },
            loadHppIframe : function(){
                var self= this;
                this.redirectAfterPlaceOrder = false;
                var paymentDetails = this.getData();
                if (this.getselectedCCType('payment[cc_type]') == undefined ||
                    this.getselectedCCType('payment[cc_type]') == '') {
                    $('#cc_type-error').css('display', 'block');
                    $('#cc_type-error').html("<div>" + getCreditCardExceptions('CCAM6') + "</div>");
                    return false;
                }
               if(!this.validateForms(paymentDetails)){
                   console.log("Validation error");
                   return false;
               }
               if(!additionalValidators.validate()){
                    console.log("Validation Failed");
                    return false;
               }
                var serviceUrl = urlBuilder.createUrl('/worldpay/payment/hostedurl', {});
                var payload = {
                    quoteId: quote.getQuoteId(),
                    paymentdetails: paymentDetails.additional_data
                };
                fullScreenLoader.startLoader();
                $("#checkout-payment-worldpay-container").hide();
                $("#checkout-payment-worldpay-alert-message").hide();
                storage.post(
                    serviceUrl, JSON.stringify(payload)
                ).done(
                    function (apiresponse) {
                        window.checkoutConfig.CCMethodClass.redirectAfterPlaceOrder = false;
                        window.checkoutConfig.CCMethodClass.isIframecardPage = true;

                        if(window.checkoutConfig.CCMethodClass.multishipping){
                            placeMultishippingOrder(window.checkoutConfig.CCMethodClass.getData());
                        }
                        else{
                            window.checkoutConfig.CCMethodClass.placeOrder();
                        }
                        $("#checkout-payment-worldpay-container").show();
                        require(["https://payments.worldpay.com/resources/hpp/integrations/embedded/js/hpp-embedded-integration-library.js"], function (worldpay) {
                            var checkoutWorldPayLibraryObject = new WPCL.Library();
                            var iframeParams = JSON.parse(apiresponse);
                            iframeParams.customisation = {
                                "page": {
                                    "BackgroundColor":"#FFF",
                                    "border":{
                                        "style": "solid",
                                        "color":"#e4e3de",
                                        "width": "6px",
                                        "border-radius": "10px"
                                    }
                                }
                            };
                            iframeParams.resultCallback = function(responseData){
                                var redirectUrl,isredirect=false;
                                var status = responseData.order.status;
                                /*var urlParams  = Object.keys(responseData.gateway).map(function(k) {
                                    return encodeURIComponent(k) + '=' + encodeURIComponent(responseData.gateway[k])
                                }).join('&');*/
                                switch (status) {
                                    case "success":
                                        redirectUrl=url.build('worldpay/redirectresult/iframe/status/success/');
                                        break;
                                    case "failure":
                                        redirectUrl=url.build('worldpay/redirectresult/iframe/status/failure/');
                                        break;
                                    case "error":
                                        redirectUrl=url.build('worldpay/redirectresult/iframe/status/error/');
                                        break;
                                    case "cancel":
                                        redirectUrl=url.build('worldpay/redirectresult/iframe/status/cancel');
                                        break;
                                    case "cancelled_by_shopper":
                                        redirectUrl=url.build('worldpay/redirectresult/iframe/status/cancel');
                                        break
                                    default:
                                        redirectUrl=url.build('worldpay/redirectresult/iframe/status/pending');

                                }
                                window.location = redirectUrl;

                            }
                            checkoutWorldPayLibraryObject.setup(iframeParams);
                            window.addEventListener("message", function(event,s) {
                                var data = JSON.parse(event.data);
                                if(data.action == 'resize'){
                                    $('.hpp-checkout').hide();
                                    if(paymentDetails.additional_data.save_my_card){
                                        self.isSaveThisCardVisible(true);
                                        $("#worldpay_cc_save_card").attr('disabled',"disabled");
                                    }else{
                                        self.isSaveThisCardVisible(false);
                                        $("#worldpay_cc_save_card").removeAttr('disabled');
                                    }
                                    $("#wp-cl-checkout-payment-worldpay-container-iframe").attr('scrolling','yes');
                                    self.afterIframeLoadActions();
                                    fullScreenLoader.stopLoader();
                                }
                            }, { once: true });
                            /******* If addeventlister not working */
                            setTimeout(function () {
                                if($('.loading-mask:visible').length)
                                {
                                    $('.hpp-checkout').hide();
                                    if(paymentDetails.additional_data.save_my_card){
                                        self.isSaveThisCardVisible(true);
                                        $("#worldpay_cc_save_card").attr('disabled',"disabled");
                                    }else{
                                        self.isSaveThisCardVisible(false);
                                        $("#worldpay_cc_save_card").removeAttr('disabled');
                                    }

                                    $("#wp-cl-checkout-payment-worldpay-container-iframe").attr('scrolling','yes');

                                    self.afterIframeLoadActions();
                                    fullScreenLoader.stopLoader();
                                }

                            },1000);
                            /******** end  */
                        });
                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response);
                        fullScreenLoader.stopLoader();
                    }
                );
                return true;
            },
            availableCCTypes : function(){
                if(this.getIntigrationMode() == 'redirect' && this.getHppIntegrationType() == 'iframe'){
                    return ccTypesArr.filter(function(el) { return el.ccValue != "savedcard"; });
                }

                return ccTypesArr;
            },
            getCheckoutLabels: function (labelcode) {
                var ccData = window.checkoutConfig.payment.ccform.checkoutlabels;
                for (var key in ccData) {
                    if (ccData.hasOwnProperty(key)) {
                        var cxData = ccData[key];
                        if (cxData['wpay_label_code'].includes(labelcode)) {
                            return cxData['wpay_custom_label'] ? cxData['wpay_custom_label'] : cxData['wpay_label_desc'];
                        }
                    }
                }
            },
            availableInstalTypes: function () {
                return isInstalment();
            },
            availableInstalTypesCnt: function () {
                return isInstalment().length;
            },
            showCPFSection: function () {
                if((isInstalment().length === 0 || isInstalment().length !== 0) && this.isCPFEnabled()) {
                    return true;
                }
                return false;
            },
            showInstalmentSection: function () {
                if((isInstalment().length !== 0) && this.isInstalmentEnabled()) {
                    return true;
                }
                return false;
            },
            selectedCCType : ko.observable(),
            paymentToken:ko.observable(),
            selectedInstalment: ko.observable(),

            getCode: function() {
                return 'worldpay_cc';
            },
            unselectPreviousSelectedCard : function(radioName){
                $("input:radio[name='"+radioName+"']").each(function(i) {
                    this.checked = false;
                });

            },

            loadEventAction: function(data, event){
                var type = $("input[name='payment[cc_type]']:checked").data('type');
                if ((type !== undefined) && (type == 'redirect')) {
                    data.ccValue = $("input[name='payment[cc_type]']:checked").val();
                }
                if ((data.ccValue)) {
                    if (data.ccValue=="savedcard") {
                        $("#saved-Card-Visibility-Enabled").show();
                        $(".cc-Visibility-Enabled").children().prop('disabled',true);
                        $("#saved-Card-Visibility-Enabled").children().prop('disabled',false);
                        $(".cc-Visibility-Enabled").hide();
                        $("#worldpay_cc_save-card_div").hide();
                        $("#checkout-payment-worldpay-container").hide();
                        window.checkoutConfig.CCMethodClass.unselectPreviousSelectedCard('payment[token_to_use]');
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
                        $("#checkout-payment-worldpay-container").hide();
                        window.checkoutConfig.CCMethodClass.unselectPreviousSelectedCard('payment[token_to_use]');

                    }else{
                        $("#worldpay_cc_save-card_div").show();
                        $(".cc-Visibility-Enabled").children().prop('disabled',false);
                        $("#saved-Card-Visibility-Enabled").children().prop('disabled',true);
                        $("#saved-Card-Visibility-Enabled").hide();
                        $(".cc-Visibility-Enabled").show();
                    }
                }
                $('#disclaimer-error').html('');
            },

            initPaymentKeyEvents: function(){
                var that = this;
                $('.checkout-container, #multishipping-billing-form').on('keyup', '.payment_cc_number', function(e){
                    that.loadCCKeyDownEventAction(this, e);
                })
            },

            loadCCKeyDownEventAction: function(el, event){
                var curVal = $(el).val();

                var $ccNumberContain = $(el).parents('.ccnumber_withcardtype');
                var piCardType = '';

                var visaRegex = new RegExp('^4[0-9]{0,20}$'),
                mastercardRegex = new RegExp(
                '^(?:5[1-5][0-9]{0,2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{0,2}|27[01][0-9]|2720)[0-9]{0,12}$'
                ),
                amexRegex = new RegExp('^3$|^3[47][0-9]{0,13}$'),
                discoverRegex = new RegExp('^6[05]$|^601[1]?$|^65[0-9][0-9]?$|^6(?:011|5[0-9]{2})[0-9]{0,12}$'),
                jcbRegex = new RegExp('^35(2[89]|[3-8][0-9])'),
                dinersRegex = new RegExp('^36'),
                maestroRegex = new RegExp('^(5018|5020|5038|6304|679|6759|676[1-3])'),
                unionpayRegex = new RegExp('^62[0-9]{0,14}$|^645[0-9]{0,13}$|^65[0-9]{0,14}$'),
                dankortRegex = new RegExp('^(5019)');

                // get rid of spaces and dashes before using the regular expression
                curVal = curVal.replace(/ /g, '').replace(/-/g, '');

                // checks per each, as their could be multiple hits
                if (curVal.match(dankortRegex)) {
                    //console.log("enetered dankort");
                    piCardType = 'dankort';
                    $ccNumberContain.addClass('is_dankort');
                } else {
                    $ccNumberContain.removeClass('is_dankort');
                }

                if (curVal.match(visaRegex)) {
                    piCardType = 'visa';
                    $ccNumberContain.addClass('is_visa');
                } else {
                    $ccNumberContain.removeClass('is_visa');
                }

                if (curVal.match(mastercardRegex)) {
                    piCardType = 'mastercard';
                    $ccNumberContain.addClass('is_mastercard');
                } else {
                    $ccNumberContain.removeClass('is_mastercard');
                }

                if (curVal.match(amexRegex)) {
                    piCardType = 'amex';
                    $ccNumberContain.addClass('is_amex');
                } else {
                    $ccNumberContain.removeClass('is_amex');
                }

                if (curVal.match(discoverRegex)) {
                    piCardType = 'discover';
                    $ccNumberContain.addClass('is_discover');
                } else {
                    $ccNumberContain.removeClass('is_discover');
                }

                if (curVal.match(unionpayRegex)) {
                    piCardType = 'unionpay';
                    $ccNumberContain.addClass('is_unionpay');
                } else {
                    $ccNumberContain.removeClass('is_unionpay');
                }

                if (curVal.match(jcbRegex)) {
                    piCardType = 'jcb';
                    $ccNumberContain.addClass('is_jcb');
                } else {
                    $ccNumberContain.removeClass('is_jcb');
                }

                if (curVal.match(dinersRegex)) {
                    piCardType = 'diners';
                    $ccNumberContain.addClass('is_diners');
                } else {
                    $ccNumberContain.removeClass('is_diners');
                }

                if (curVal.match(maestroRegex)) {
                    piCardType = 'maestro';
                    $ccNumberContain.addClass('is_maestro');
                } else {
                    $ccNumberContain.removeClass('is_maestro');
                }

                // if nothing is a hit we add a class to fade them all out
                if (
                    curVal !== '' &&
                    !curVal.match(visaRegex) &&
                    !curVal.match(mastercardRegex) &&
                    !curVal.match(amexRegex) &&
                    !curVal.match(discoverRegex) &&
                    !curVal.match(jcbRegex) &&
                    !curVal.match(dinersRegex) &&
                    !curVal.match(maestroRegex) &&
                    !curVal.match(unionpayRegex) &&
                    !curVal.match(dankortRegex)
                ) {
                    $ccNumberContain.addClass('is_nothing');
                } else {
                    $ccNumberContain.removeClass('is_nothing');
                }
            },

            getTemplate: function(){
                if (this.intigrationmode == 'direct') {
                    if(this.multishipping){
                        return this.multishippingDirectTemplate;
                    }
                    return this.direcTemplate;
                } else{
                    if(this.multishipping){
                        return this.multishippingRedirectTemplate;
                    }
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
                return window.checkoutConfig.payment.ccform.cctitle;
            },
            hasVerification:function() {
                return window.checkoutConfig.payment.ccform.isCvcRequired;
            },
            getSaveCardAllowed: function(){
                if(customer.isLoggedIn()){
                    return window.checkoutConfig.payment.ccform.saveCardAllowed;
                }
            },
            isTokenizationEnabled: function(){
                if(customer.isLoggedIn()){
                    return window.checkoutConfig.payment.ccform.tokenizationAllowed;
                }
            },
            isStoredCredentialsEnabled: function(){
                if(customer.isLoggedIn()){
                    return window.checkoutConfig.payment.ccform.storedCredentialsAllowed;
                }
            },
            disclaimerMessage: function(){
                if(customer.isLoggedIn()){
                    return window.checkoutConfig.payment.ccform.disclaimerMessage;
                }
            },
            isDisclaimerMessageEnabled: function(){
                if(customer.isLoggedIn()){
                    return window.checkoutConfig.payment.ccform.isDisclaimerMessageEnabled;
                }
            },
            isDisclaimerMessageMandatory: function(){
                if(customer.isLoggedIn()){
                    return window.checkoutConfig.payment.ccform.isDisclaimerMessageMandatory;
                }
            },
            isActive: function() {
                return true;
            },
            paymentMethodSelection: function() {
                return window.checkoutConfig.payment.ccform.paymentMethodSelection;
            },
            getselectedCCType : function(inputName){
                if (this.paymentMethodSelection()=='radio') {
                    return $("input[name='"+inputName+"']:checked").val();
                } else {
                    return  this.selectedCCType();
                }
            },
            isSubscribed : function (){
                return window.checkoutConfig.payment.ccform.isSubscribed;
            },
            isProductOnDemand : function (){
                return window.checkoutConfig.payment.ccform.isProductOnDemand;
            },
            isCPFEnabled: function () {
                if(billingAddressCountryId === 'BR') {
                    return window.checkoutConfig.payment.ccform.isCPFEnabled;
                }
                return false;
            },

            isInstalmentEnabled: function () {
                return window.checkoutConfig.payment.ccform.isInstalmentEnabled;
            },
            getConfigLatamFound: function () {
                if(this.isInstalmentEnabled()) {
                    var countries = window.checkoutConfig.payment.ccform.latAmCountries;
                    for (var i = 0; i < countries.length; i++) {
                        if (countries[i].includes(billingAddressCountryId)) {
                            return true;
                        }
                    }
                }
                return false;
            },
            belongsToLACountries: function () {
                var lacountries = ['AR', 'BZ', 'BR', 'CL', 'CO', 'CR', 'SV', 'GT', 'HN', 'MX', 'NI', 'PA', 'PE'];
                if (lacountries.includes(billingAddressCountryId)) {
                    return true;
                }
                return false;
            },
            getShippingFeeForBrazil: function () {
                if (billingAddressCountryId == 'BR' && this.isCalculated()) {
                    var price = this.totals()['shipping_amount'];
                    return price;
                }
                return 0;
            },
            isCalculated: function () {
                return this.totals() && quote.shippingMethod() != null; //eslint-disable-line eqeqeq
            },
//            getInstalmentValues : function(billingAddressCountryId){
//                return window.checkoutConfig.payment.ccform.instalmentvalues.billingAddressCountryId;
//
//            },
            isCPF: ko.observable(),
            /**
             * @override
             */
            getData: function () {

                var selectedCardType =    this.getselectedCCType('payment[cc_type]');

                if(typeof selectedCardType == 'undefined'){
                    selectedCardType = this.selectedPayType();
                }


                if(this.isClientSideEncryptionEnabled()){
                    return {
                        'method': "worldpay_cc",
                        'additional_data': {
                            'cc_cid': this.creditCardVerificationNumber(),
                            'cc_type': selectedCardType,
                            'cc_exp_year': '',
                            'cc_exp_month': '',
                            'cc_number': '',
                            'cc_name': $('#' + this.getCode() + '_cc_name').val(),
                            'save_my_card': this.saveMyCard,
                            'cse_enabled': this.isClientSideEncryptionEnabled(),
                            'encryptedData': this.cseData,
                            'tokenCode': this.paymentToken,
                            'saved_cc_cid': $('.saved-cvv-number').val(),
                            'isSavedCardPayment': this.isSavedCardPayment,
                            'tokenization_enabled': this.isTokenizationEnabled(),
                            'stored_credentials_enabled': this.isStoredCredentialsEnabled(),
                            'dfReferenceId': window.sessionId,
                            'disclaimerFlag': this.disclaimerFlag,
                            'subscriptionStatus': this.isSubscribed(),
                            'productOnDemand': this.isProductOnDemand(),
                            'cpf_enabled': this.isCPFEnabled(),
                            'instalment_enabled': this.isInstalmentEnabled(),
                            'cpf': $('#' + this.getCode() + '_cpf').val(),
                            'instalment': $('#' + this.getCode() + '_instalment').val(),
                            'statement': this.statement,
                            'shippingfee': this.getShippingFeeForBrazil(),
                            'multishipping': this.multishipping,
                            'browser_screenheight': window.screen.height,
                            'browser_screenwidth': window.screen.width,
                            'browser_colordepth': window.screen.colorDepth
                        }
                    };

                }else{
                    return {
                        'method': "worldpay_cc",
                        'additional_data': {
                            'cc_cid': this.creditCardVerificationNumber(),
                            'cc_type': selectedCardType,
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
                            'tokenization_enabled': this.isTokenizationEnabled(),
                            'stored_credentials_enabled': this.isStoredCredentialsEnabled(),
                            'dfReferenceId': window.sessionId,
                            'disclaimerFlag': this.disclaimerFlag,
                            'subscriptionStatus': this.isSubscribed(),
                            'productOnDemand': this.isProductOnDemand(),
                            'cpf_enabled': this.isCPFEnabled(),
                            'instalment_enabled': this.isInstalmentEnabled(),
                            'cpf': $('#' + this.getCode() + '_cpf').val(),
                            'instalment': $('#' + this.getCode() + '_instalment').val(),
                            'statement': this.statement,
                            'shippingfee': this.getShippingFeeForBrazil(),
                            'multishipping': this.multishipping,
                            'browser_screenheight': window.screen.height,
                            'browser_screenwidth': window.screen.width,
                            'browser_colordepth': window.screen.colorDepth
                        }
                    };
                }


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
                var $cpfForm = $('#' + this.getCode() + '-cpf-form');
                var cc_type_selected = this.getselectedCCType('payment[cc_type]');
                this.statement = $('#' + this.getCode() + '_statement').val();

                if(!additionalValidators.validate()){
                    console.log("Validation Failed");
                    return false;
                }
                if(this.intigrationmode == 'redirect'){
                    if (this.getselectedCCType('payment[cc_type]') == undefined ||
                        this.getselectedCCType('payment[cc_type]') == '') {
                        $('#cc_type-error').css('display', 'block');
                        $('#cc_type-error').html("<div>" + getCreditCardExceptions('CCAM6') + "</div>");
                        return false;
                    }
                }
                // 3DS2 JWT create function
                if(window.checkoutConfig.payment.ccform.isDynamic3DS2Enabled){
                    var bin = this.creditCardNumber();
                    if(cc_type_selected == 'savedcard'){
                        bin = $("input[name='payment[token_to_use]']:checked").next().next().next().val();
                    }

                    if(this.intigrationmode == 'direct') {
                        if(cc_type_selected == 'savedcard'){
                            if($savedCardForm.validation() && $savedCardForm.validation('isValid')) {
                                if(bin) {
                                    createJwt(bin);
                                }else{
                                    alert(getCreditCardExceptions('CCAM2'));
                                    return;
                                }
                            }
                        }else {
                            if($form.validation() && $form.validation('isValid')) {
                                var binNew = bin.substring(0,6);

                                createJwt(binNew);
                            }
                        }
                    }
                }
                this.dfReferenceId = null;

                if(cc_type_selected == 'savedcard'){

                    //Saved card handle
                    if((this.intigrationmode == 'direct' && $savedCardForm.validation() && $savedCardForm.validation('isValid') && selectedSavedCardToken) ||
                        (this.intigrationmode == 'redirect' && $form.validation() && $form.validation('isValid') && selectedSavedCardToken)){
                        var cardType = $("input[name='payment[token_to_use]']:checked").next().next().val();
                        this.isSavedCardPayment=true;
                        this.paymentToken = selectedSavedCardToken;
                        var savedcvv = $('.saved-cvv-number').val();
                        var res = this.getRegexCode(cardType).exec(savedcvv);
                        this.statement = $('.statement').val();
                        if(savedcvv != res){
                            $('#saved-cvv-error').css('display', 'block');
                            $('#saved-cvv-error').html(getCreditCardExceptions('CCAM3'));
                        }else{
                            fullScreenLoader.startLoader();
                            this.redirectAfterPlaceOrder = false;
                            if(window.checkoutConfig.payment.ccform.isDynamic3DS2Enabled && this.intigrationmode == 'direct'){
                                window.addEventListener("message", function(event) {
                                    var data = JSON.parse(event.data);
                                    var envUrl;
                                    if(window.checkoutConfig.payment.ccform.jwtEventUrl !== '') {
                                        envUrl = window.checkoutConfig.payment.ccform.jwtEventUrl;
                                    }
                                    if (event.origin === envUrl) {
                                        var data = JSON.parse(event.data);
                                        //console.warn('Merchant received a message:', data);
                                        if(data !== undefined){
                                            if(data.Payload){
                                                // for cardinal
                                                if(data.Payload.ActionCode =="SUCCESS"){
                                                    var sessionId = data.Payload.SessionId;
                                                    //place order with direct CSE method
                                                    if(sessionId){
                                                        this.dfReferenceId = sessionId;
                                                    }
                                                    window.sessionId = this.dfReferenceId;
                                                    if(window.checkoutConfig.payment.ccform.isMultishipping){
                                                        placeMultishippingOrder(self.getData());
                                                    }
                                                    else{
                                                        fullScreenLoader.stopLoader();
                                                        self.placeOrder();
                                                    }
                                                }

                                            }else if(data.Status){
                                                //window.sessionId = data.SessionId;
                                                var sessionId = data.SessionId;
                                                if(sessionId){
                                                    this.dfReferenceId = sessionId;
                                                }
                                                window.sessionId = this.dfReferenceId;
                                                //place order with direct CSE method
                                                if(window.checkoutConfig.payment.ccform.isMultishipping){
                                                    placeMultishippingOrder(self.getData());
                                                }
                                                else{
                                                    fullScreenLoader.stopLoader();
                                                    self.placeOrder();
                                                }
                                            }

                                        }
                                    }
                                }, { once: true });
                            } else {
                                if(window.checkoutConfig.payment.ccform.isMultishipping){
                                    placeMultishippingOrder(self.getData());
                                }
                                else{
                                    fullScreenLoader.stopLoader();
                                    self.placeOrder();
                                }
                            }
                        }
                    }
                } else if($form.validation() && $form.validation('isValid')) {
                    // Subscription check
                    if(this.isSubscribed()){
                        if(cc_type_selected !== 'savedcard'){
                            var saveCardOption = $('#' + this.getCode() + '_save_card').is(":checked");
                            if(!saveCardOption){
                                $('#disclaimer-error').css('display', 'block');
                                $('#disclaimer-error').html(getCreditCardExceptions('CCAM4'));
                                return false;
                            } else {
                                $('#disclaimer-error').html('');
                            }
                        }
                    }
                    if(this.isProductOnDemand()){
                        if(cc_type_selected !== 'savedcard'){
                            var saveCardOption = $('#' + this.getCode() + '_save_card').is(":checked");
                            if(!saveCardOption){
                                $('#disclaimer-error').css('display', 'block');
                                $('#disclaimer-error').html(getCreditCardExceptions('CCAM30'));
                                return false;
                            } else {
                                $('#disclaimer-error').html('');
                            }
                        }
                    }
                    //Direct form handle
                    this.saveMyCard = $('#' + this.getCode() + '_save_card').is(":checked");
                    if(this.saveMyCard && !this.isDisclaimerMessageMandatory()){
                        this.saveMyCard = 1;
                    } else if(this.saveMyCard && this.isDisclaimerMessageMandatory() && this.isDisclaimerMessageEnabled() && window.disclaimerDialogue === null){
                        $('#disclaimer-error').css('display', 'block');
                        $('#disclaimer-error').html(getCreditCardExceptions('CCAM5'));
                        return false;
                    } else if(this.saveMyCard && this.isStoredCredentialsEnabled() && this.isDisclaimerMessageEnabled() && (window.disclaimerDialogue === null || window.disclaimerDialogue === false)){
                        if(this.isSubscribed()){
                            $('#disclaimer-error').css('display', 'block');
                            $('#disclaimer-error').html(getCreditCardExceptions('CCAM5'));
                            return false;
                        }
                        if(this.isProductOnDemand()){
                            $('#disclaimer-error').css('display', 'block');
                            $('#disclaimer-error').html(getCreditCardExceptions('CCAM5'));
                            return false;
                        }
                        this.saveMyCard = '';
                        $('#' + this.getCode() + '_save_card').prop( "checked", false );
                    }
                    if (this.intigrationmode == 'direct') {
                        fullScreenLoader.startLoader();
                        var that = this;
                        // Need to check for 3ds2 enable or not
                        //jwtCreate(that.creditCardNumber());
                        var sessionId = window.sessionId;
                        that.dfReferenceId = sessionId;

                        if(this.isClientSideEncryptionEnabled()){
                            require(["https://payments.worldpay.com/resources/cse/js/worldpay-cse-1.0.2.min.js"], function (worldpay) {
                                worldpay.setPublicKey(that.getCsePublicKey());
                                var expiryMonth = that.creditCardExpMonth();
                                if(expiryMonth < 10){
                                    expiryMonth = '0'+expiryMonth;
                                }
                                var cseData = {
                                    cvc: that.creditCardVerificationNumber(),
                                    cardHolderName: $('#' + that.getCode() + '_cc_name').val(),
                                    cardNumber: that.creditCardNumber(),
                                    expiryMonth: expiryMonth,
                                    expiryYear: that.creditCardExpYear()
                                };
                                var encryptedData = worldpay.encrypt(cseData);
                                that.cseData = encryptedData;
                                //place order with direct CSE method
                                that.dfReferenceId = null;
                                if(window.checkoutConfig.payment.ccform.isDynamic3DS2Enabled){
                                    window.addEventListener("message", function(event) {
                                        var data = JSON.parse(event.data);
                                        var envUrl;
                                        if(window.checkoutConfig.payment.ccform.jwtEventUrl !== '') {
                                            envUrl = window.checkoutConfig.payment.ccform.jwtEventUrl;
                                        }
                                        if (event.origin === envUrl) {
                                            var data = JSON.parse(event.data);
                                            //console.warn('Merchant received a message:', data);
                                            if(data !== undefined){
                                                if(data.Payload){
                                                    // for cardinal
                                                    if(data.Payload.ActionCode =="SUCCESS"){
                                                        window.sessionId = data.Payload.SessionId;
                                                        //place order with direct CSE method
                                                        if(window.checkoutConfig.payment.ccform.isMultishipping){
                                                            placeMultishippingOrder(self.getData());
                                                        }
                                                        else{
                                                            fullScreenLoader.stopLoader();
                                                            self.placeOrder();
                                                        }
                                                    }
                                                }else if(data.Status){
                                                    //window.sessionId = data.SessionId;
                                                    window.sessionId = data.SessionId;
                                                    //place order with direct CSE method
                                                    if(window.checkoutConfig.payment.ccform.isMultishipping){
                                                        placeMultishippingOrder(self.getData());
                                                    }
                                                    else{
                                                        fullScreenLoader.stopLoader();
                                                        self.placeOrder();
                                                    }
                                                }
                                            }
                                        }
                                    }, { once: true });
                                } else {
                                    if(window.checkoutConfig.payment.ccform.isMultishipping){
                                        placeMultishippingOrder(self.getData());
                                    }
                                    else{
                                        fullScreenLoader.stopLoader();
                                        self.placeOrder();
                                    }
                                }
                            });
                        } else{
                            if(window.checkoutConfig.payment.ccform.isDynamic3DS2Enabled){
                                window.addEventListener("message", function(event) {
                                    var data = JSON.parse(event.data);
                                    var envUrl;
                                    if(window.checkoutConfig.payment.ccform.jwtEventUrl !== '') {
                                        envUrl = window.checkoutConfig.payment.ccform.jwtEventUrl;
                                    }
                                    if (event.origin === envUrl) {
                                        var data = JSON.parse(event.data);
                                        console.warn('Merchant received a message:', data);

                                        if(data !== undefined){
                                            if(data.Payload){
                                                // for cardinal
                                                if(data.Payload.ActionCode =="SUCCESS"){
                                                    window.sessionId = data.Payload.SessionId;
                                                    //place order with direct CSE method
                                                    if(window.checkoutConfig.payment.ccform.isMultishipping){
                                                        placeMultishippingOrder(self.getData());
                                                    }
                                                    else{
                                                        fullScreenLoader.stopLoader();
                                                        self.placeOrder();
                                                    }
                                                }
                                            }else if(data.Status){
                                                //window.sessionId = data.SessionId;
                                                window.sessionId = data.SessionId;
                                                //place order with direct CSE method
                                                if(window.checkoutConfig.payment.ccform.isMultishipping){
                                                    placeMultishippingOrder(self.getData());
                                                }
                                                else{
                                                    fullScreenLoader.stopLoader();
                                                    self.placeOrder();
                                                }
                                            }
                                        }
                                    }
                                }, { once: true });
                            } else {
                                if(window.checkoutConfig.payment.ccform.isMultishipping){
                                    placeMultishippingOrder(self.getData());
                                }
                                else{
                                    fullScreenLoader.stopLoader();
                                    self.placeOrder();
                                }
                            }
                        }
                    }else if(this.intigrationmode == 'redirect'){
                        //place order with Redirect CSE Method
                        if(this.multishipping){
                            fullScreenLoader.startLoader();
                            placeMultishippingOrder(self.getData());

                        }
                        else{
                            self.placeOrder();
                        }
                    }
                }else {
                    jQuery('.paymentmethods-radio-wrapper .mage-error').css("margin-top", "45px");
                    return $form.validation() && $form.validation('isValid');
                }
            },
            afterPlaceOrder: function (data, event) {
                if(window.checkoutConfig.CCMethodClass.isIframecardPage === true){
                    return false;
                }
                if(this.intigrationmode === 'redirect'){
                    window.location.replace(url.build('worldpay/redirectresult/redirect'));
                }else if (this.isSavedCardPayment) {
                    window.location.replace(url.build('worldpay/savedcard/redirect'));
                }else if(this.intigrationmode === 'redirect' && !this.isSavedCardPayment){
                    window.location.replace(url.build('worldpay/redirectresult/redirect'));
                }else if(this.intigrationmode === 'direct' && !this.isSavedCardPayment){
                    window.location.replace(url.build('worldpay/threedsecure/auth'));
                }
            },
            disclaimerPopup: function(){
                disclaimerConfirm.openDisclaimer();
            },
            getIntigrationMode: function(){
                return window.checkoutConfig.payment.ccform.intigrationmode;
            },
            getHppIntegrationType: function(){
                return window.checkoutConfig.payment.ccform.hpp_integration_type;
            }
        });
    }
);
