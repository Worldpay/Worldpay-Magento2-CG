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
         'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/full-screen-loader',
        'ko',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function (Component, $, quote, customer,validator, url, placeOrderAction, placeMultishippingOrder, redirectOnSuccessAction,errorProcessor, urlBuilder, storage, fullScreenLoader, ko,additionalValidators) {
        'use strict';
        var ccTypesArr = ko.observableArray([]);
        var paymentService = false;
        var isACH = false;
        var billingAddressCountryId = "";
        if (quote.billingAddress()) {
            billingAddressCountryId = quote.billingAddress._latestValue.countryId;
        }
        var klarnaPay = window.checkoutConfig.payment.ccform.klarnaTypesAndContries;
        
        var achAccountCodeMessage = 'Maximum allowed length of 17 exceeded';
        var achAccountDisplayMessage = getCreditCardExceptions('CACH03')?getCreditCardExceptions('CACH03'):achAccountCodeMessage;
        var achRoutingCodeMessage = 'Required length should be 8 or 9';
        var achRoutingDisplayMessage = getCreditCardExceptions('CACH04')?getCreditCardExceptions('CACH04'):achRoutingCodeMessage;
        var achCheckNumberCodeMessage = 'Maximum allowed length of 15 exceeded';
        var achCheckNumberDisplayMsg = getCreditCardExceptions('CACH05')?getCreditCardExceptions('CACH05'):achCheckNumberCodeMessage;
        var achCompanyCodeMessage = 'Maximum allowed length of 40 exceeded' ;
        var achCompanyDisplayMsg = getCreditCardExceptions('CACH06')?getCreditCardExceptions('CACH06'):achCompanyCodeMessage;
        var klarnaTypesArr = ko.observableArray([]);
        var isKlarnaAvailabel,isKlarna;
        $.validator.addMethod('worldpay-validate-ach-accountnumber', function (value) {
            if (value) {
                return evaluateRegex(value, "^[0-9]{0,17}$");
            }
	    }, $.mage.__(achAccountDisplayMessage));
        $.validator.addMethod('worldpay-validate-ach-routingnumber', function (value) {
            if (value) {
                return evaluateRegex(value, "^[0-9]{8,9}$");
            }
	    }, $.mage.__(achRoutingDisplayMessage));
        $.validator.addMethod('worldpay-validate-ach-checknumber', function (value) {
            if ((value) || value.length === 0) {
                return evaluateRegex(value, "^[0-9]{0,15}$");
            }
	    }, $.mage.__(achCheckNumberDisplayMsg));
        $.validator.addMethod('worldpay-validate-ach-companyname', function (value) {
            if (value || value.length === 0) {
                return value.length<40;
            }
	    }, $.mage.__(achCompanyDisplayMsg));
        function evaluateRegex(data, re) {
            var patt = new RegExp(re);
            return patt.test(data);
        }
        function getCreditCardExceptions (exceptioncode){
                var ccData=window.checkoutConfig.payment.ccform.creditcardexceptions;
                  for (var key in ccData) {
                    if (ccData.hasOwnProperty(key)) {  
                        var cxData=ccData[key];
                    if(cxData['exception_code'] === exceptioncode){
                        return cxData['exception_module_messages']?cxData['exception_module_messages']:cxData['exception_messages'];
                    }
                    }
                }
        }
        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                redirectTemplate: 'Sapient_Worldpay/payment/apm',
                multishippingRedirectTemplate: 'Sapient_Worldpay/multishipping/apm',
                idealBankType:null,
                ach_accountType:null,
                ach_accountnumber:null,
                ach_routingNumber:null,
                statementNarrative:null,
                multishipping:false,
                klarnaType:null
            },
            
            billingCountryId: ko.observable(),

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
                        that.billingCountryId(billingAddressCountryId);
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
                /* Multishipping Code */
                if(this.multishipping){
					var MultishippingApmPreSelected = jQuery('#p_method_worldpay_apm:checked');
					if(MultishippingApmPreSelected.length){
                        jQuery('#payment-continue').html("<span>Place Order</span>");
						this.selectPaymentMethod();
					}
				}
                var ccavailabletypes = this.getCcAvailableTypes();
                var filtercclist = {};
                var cckey,ccvalue,typeKey,typeValue;
                var serviceUrl = urlBuilder.createUrl('/worldpay/payment/types', {});
                 var payload = {
                    countryId: quote.billingAddress._latestValue.countryId
                };
                var integrationMode = window.checkoutConfig.payment.ccform.intigrationmode;
                 fullScreenLoader.startLoader();
                isKlarnaAvailabel = !("" in klarnaPay);
                isKlarna = isKlarnaAvailabel 
                                && (klarnaPay.KLARNA_PAYLATER.includes(quote.billingAddress._latestValue.countryId)
                                || klarnaPay.KLARNA_SLICEIT.includes(quote.billingAddress._latestValue.countryId) 
                                || klarnaPay.KLARNA_PAYNOW.includes(quote.billingAddress._latestValue.countryId));

                 storage.post(
                    serviceUrl, JSON.stringify(payload)
                ).done(
                    function (apiresponse) {
                        var response = JSON.parse(apiresponse);
                        var klarnaTypes = {};
                        if(response.length){
                            $.each(response, function(responsekey, value){
                                var found = false;
                                $.each(ccavailabletypes, function(key, value){
                                    if(response[responsekey] == key.toUpperCase()){
                                       if((integrationMode === 'redirect' && key !== 'ACH_DIRECT_DEBIT-SSL')
                                            || integrationMode === 'direct' ){
                                        found = true;
                                        cckey = key;
                                        ccvalue = ccavailabletypes[key];
                                        if (key === 'ACH_DIRECT_DEBIT-SSL'){
                                            isACH = true;
                                        }
                                        return false;
                                    }
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
                        //Klarna
                        if(isKlarna){
                            var klarnaObj = {
                                'ccValue': 'KLARNA-SSL',
                                'ccLabel': 'KLARNA'
                            };
                            ccTypesArr1.push(klarnaObj);
                        }
                        for (var key in klarnaPay) {
                            if (klarnaPay.hasOwnProperty(key)) {
                                if (klarnaPay[key] !== null && klarnaPay[key].includes(quote.billingAddress._latestValue.countryId)) {
                                    typeKey = key+'-SSL';
                                    typeValue = key;
                                    klarnaTypes[typeKey] = typeValue;
                                }
                            }
                        }
                        var klarnaTypesArr1 = _.map(klarnaTypes, function (value, key) {
                            return {
                             'klarnaLabel': key,
                             'klarnaCode': value
                            };
                        });
			
                        fullScreenLoader.stopLoader();
                        ccTypesArr(ccTypesArr1.filter((set => f => !set.has(f.ccValue) && set.add(f.ccValue))(new Set)));
			klarnaTypesArr(klarnaTypesArr1);
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
            availableKlarnaTypes : function(){
               return klarnaTypesArr;
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
            selectedCCType : ko.observable(),
            selectedIdealBank:ko.observable(),
            selectedACHAccountType:ko.observable(),
            selectedKlarnaType:ko.observable(),
            achaccountnumber: ko.observable(),
            achroutingnumber: ko.observable(),
            achchecknumber: ko.observable(),
            achcompanyname: ko.observable(),
            achemailaddress:ko.observable(),
            stmtNarrative:ko.observable(),
            getTemplate: function(){
                if(this.multishipping){
                    return this.multishippingRedirectTemplate;
                }
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
            getACHAccounttypes: function(code) {
                var accounttypes = window.checkoutConfig.payment.ccform.achdetails;
                return accounttypes[code];
            },
            /**
             * @override
             */
            getData: function () {
                return {
                    'method': "worldpay_apm",
                    'additional_data': {
                        'cc_type': this.getselectedCCType(),
                        'cc_bank': this.idealBankType,
                        'klarna_type': this.klarnaType,
                        'ach_account': this.ach_accountType,
                        'ach_accountNumber': this.ach_accountnumber,
                        'ach_routingNumber': this.ach_routingnumber,
                        'ach_checknumber': this.ach_checknumber,
                        'ach_companyname': this.ach_companyname,
                        'ach_emailaddress': this.ach_emailaddress,
                        'statementNarrative': this.statementNarrative
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
            getACHBankAccountTypes : function() {
                var accounttypes = _.map(window.checkoutConfig.payment.ccform.achdetails, function (value, key) {
                                           return {
                                              'accountCode': key,
                                              'accountText': value
                                    };
                                });
                return ko.observableArray(accounttypes);
            },
            showACH : function() {
                if(isACH && this.getselectedCCType() == 'ACH_DIRECT_DEBIT-SSL'){
                    return true;
                }
              return false;
            },
            isKlarnaPayLater : function() {
                if(isKlarna && this.selectedKlarnaType() == 'KLARNA_PAYLATER-SSL'){
                    return true;
                }
		return false;
            },
            paymentMethodSelection: function() {
                return window.checkoutConfig.payment.ccform.paymentMethodSelection;
            },
            preparePayment:function() {
                var self = this;
                var $form = $('#' + this.getCode() + '-form');
                if($form.validation() && $form.validation('isValid')){
                    if(!additionalValidators.validate()){
                        console.log("Validation Failed");
                        return false;
                    }
                    if (this.getselectedCCType() =='IDEAL-SSL') {
                        this.idealBankType = this.selectedIdealBank();
                    }else if(isKlarna && this.getselectedCCType() == 'KLARNA-SSL'){
                        this.klarnaType = this.selectedKlarnaType();
                    }else if(this.getselectedCCType() == 'ACH_DIRECT_DEBIT-SSL'){
                        this.ach_accountType = this.getACHAccounttypes(this.selectedACHAccountType());
                        this.ach_accountnumber = this.achaccountnumber();
                        this.ach_routingnumber = this.achroutingnumber();
                        this.ach_checknumber = this.achchecknumber();
                        this.ach_companyname = this.achcompanyname();
                        this.ach_emailaddress = this.achemailaddress();
                        
                    }
                    this.statementNarrative = this.stmtNarrative();
                    if(window.checkoutConfig.payment.ccform.isMultishipping){  
						fullScreenLoader.startLoader();					
						placeMultishippingOrder(self.getData());
					}
					else{
						self.placeOrder();
					}
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
                if(this.getselectedCCType()=='ACH_DIRECT_DEBIT-SSL'){
                       window.location.replace(url.build('worldpay/threedsecure/auth'));
                }else{
                window.location.replace(url.build('worldpay/redirectresult/redirect'));
            }
            },
            checkPaymentTypes: function (data, event){
               if (data && data.ccValue) {
                    if (data.ccValue=='IDEAL-SSL') {
                        $(".ideal-block").show();
                        $("#ideal_bank").prop('disabled',false);
                        $(".ach-block").hide();
                        $(".klarna-block").hide();
                    }else if(data.ccValue=='ACH_DIRECT_DEBIT-SSL'){
                        $(".ach-block").show();
                        $("#ach_pay").prop('disabled',false);
                        $(".ideal-block").hide();
                        $(".klarna-block").hide();
                    }else if(isKlarna && data.ccValue=='KLARNA-SSL'){
                        $(".klarna-block").show();
                        $("#klarna_pay").prop('disabled',false);
                        $(".ideal-block").hide();
                        $(".ach-block").hide();
                    }else{
                        $("#ideal_bank").prop('disabled',true);
                        $(".ideal-block").hide();
                        $("#ach_pay").prop('disabled',true);
                        $(".ach-block").hide();
                        $("#klarna_pay").prop('disabled',true);
                        $(".klarna-block").hide();
                    }
                     $(".statment-narrative").show();
                }else if(data){
                    if (data.selectedCCType() && data.selectedCCType() == 'IDEAL-SSL') {
                        $(".ideal-block").show();
                        $("#ideal_bank").prop('disabled',false);
                        $(".ach-block").hide();
                        $(".klarna-block").hide();
                    }else if (data.selectedCCType() && data.selectedCCType() == 'ACH_DIRECT_DEBIT-SSL') {
                        $(".ach-block").show();
                        $("#ach_pay").prop('disabled',false);
                        $(".ideal-block").hide();
                        $(".klarna-block").hide();
                    }else if (isKlarna && data.selectedCCType() && data.selectedCCType() == 'KLARNA-SSL') {
                        $(".klarna-block").show();
                        $("#klarna_pay").prop('disabled',false);
                        $(".ideal-block").hide();
                        $(".ach-block").hide();
                    }
                    else{
                        $("#ideal_bank").prop('disabled',true);
                        $(".ideal-block").hide();
                        $("#ach_pay").prop('disabled',true);
                        $(".ach-block").hide();
                        $("#klarna_pay").prop('disabled',true);
                        $(".klarna-block").hide();
                    }
                     $(".statment-narrative").show();
                }else {
                    $("#apm_KLARNA-SSL").prop('checked', false);
                    $("#apm_ACH_DIRECT_DEBIT-SSL").prop('checked', false);
                    $("#apm_IDEAL-SSL").prop('checked', false);
                    $("#ideal_bank").prop('disabled',true);
                    $(".ideal-block").hide();
                    $(".ach-block").hide();
                    $(".klarna-block").hide();
                }
            }
        });
    }
);
