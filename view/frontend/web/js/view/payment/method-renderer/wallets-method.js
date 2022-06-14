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
        'googlePay',
        'samsungPay'
    ],
    function (Component, $, quote, customer,validator, url, placeOrderAction, redirectOnSuccessAction,ko, setPaymentInformationAction, errorProcessor, urlBuilder, storage, fullScreenLoader, googlePay, samsungPay) {
        'use strict';
        var ccTypesArr = ko.observableArray([]);        
        
        var paymentService = false;
        var billingAddressCountryId = "";
        var googleResponse = "";
        var appleResponse = "";
        var paymentToken = "";
        var merchantId = '';
        var response = '';
        var response1 = '';
        var dfReferenceId = "";
  
        
        if(window.checkoutConfig.payment.general.environmentMode == 'PRODUCTION'){
            merchantId = "merchantId:"+window.checkoutConfig.payment.ccform.googleMerchantid;
        }
        
        //apple pay validation
        var debug = true;
        if (window.ApplePaySession) {
            //var merchantIdentifier = '<?=PRODUCTION_MERCHANTIDENTIFIER?>';
            var merchantIdentifier = window.checkoutConfig.payment.ccform.appleMerchantid;
            var promise = ApplePaySession.canMakePaymentsWithActiveCard(merchantIdentifier);
            promise.then(function (canMakePayments) {
                if (canMakePayments) {
                   var wallets_APPLEPAY = document.getElementById("wallets_APPLEPAY-SSL");
                   var wallets_image_APPLEPAY = document.getElementById("wallets_image_APPLEPAY-SSL");
                   var wallets_label_APPLEPAY = document.getElementById("wallets_label_APPLEPAY-SSL");

                    if(wallets_APPLEPAY) {
                       //document.getElementById("wallets_APPLEPAY-SSL").style.display = "block";
                       document.getElementById("wallets_APPLEPAY-SSL").style.display = "table-cell";
                    }
                    if(wallets_image_APPLEPAY) {
                       document.getElementById("wallets_image_APPLEPAY-SSL").style.display = "table-cell";
                    }
                    if(wallets_label_APPLEPAY) {
                       document.getElementById("wallets_label_APPLEPAY-SSL").style.display = "table-cell";
                    }
                }
            }); 
        }
        
        /***** Google pay Elements Started  */
        const baseRequest = {apiVersion: 2, apiVersionMinor: 0 };
        const allowedCardNetworks = window.checkoutConfig.payment.ccform.googlePaymentMethods.split(",");
        const allowedCardAuthMethods = window.checkoutConfig.payment.ccform.googleAuthMethods.split(",");
        const tokenizationSpecification = {
            type: 'PAYMENT_GATEWAY',
            parameters: {
                'gateway': window.checkoutConfig.payment.ccform.googleGatewayMerchantname,
                'gatewayMerchantId': window.checkoutConfig.payment.ccform.googleGatewayMerchantid
            }
        };
        
        const baseCardPaymentMethod = {
            type: 'CARD',
            parameters: {
                allowedAuthMethods: allowedCardAuthMethods,
                allowedCardNetworks: allowedCardNetworks
            }
        };
        const cardPaymentMethod = Object.assign(
            {},
            baseCardPaymentMethod,
            {
                tokenizationSpecification: tokenizationSpecification
            }
        );
        
        var paymentDataRequest = null;
        var paymentsClient = null;
        /***** Google pay Elements End  */

        if (quote.billingAddress()) {
            billingAddressCountryId = quote.billingAddress._latestValue.countryId;
        }
        
        function getGooglePaymentDataRequest(){
            const paymentDataRequest = Object.assign({}, baseRequest);
            paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];
            paymentDataRequest.transactionInfo = getGoogleTransactionInfo();
            paymentDataRequest.merchantInfo = {
                // @todo a merchant ID is available for a production environment after approval by Google
                // See {@link https://developers.google.com/pay/api/web/guides/test-and-deploy/integration-checklist|Integration checklist}
                
                merchantId,
                merchantName: window.checkoutConfig.payment.ccform.googleMerchantname
            };
            return paymentDataRequest;
        }
        function getGoogleTransactionInfo(){
            return {
                currencyCode: window.checkoutConfig.totalsData.base_currency_code,
                totalPriceStatus: 'FINAL',
                // set to cart total
                totalPrice: parseFloat(window.checkoutConfig.totalsData.base_grand_total).toFixed(2)
            };
        }
        function getGooglePaymentsClient() {
            if ( paymentsClient === null ) {
                    paymentsClient = new google.payments.api.PaymentsClient({environment: window.checkoutConfig.payment.general.environmentMode}); 
                }
            return paymentsClient;
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
        
        return Component.extend({         
            defaults: {
                redirectAfterPlaceOrder: false,
                direcTemplate: 'Sapient_Worldpay/payment/wallets',
                cardHolderName:'',
                SavedcreditCardVerificationNumber:'',
                cseData:null
            },
            initialize: function () {
                this._super();
                this.selectedCCType(null);
                if(paymentService == false) {
                    this.filterwalletajax(1);
                }

            },
            initObservable: function () {
                var that = this;
                this._super();
                quote.billingAddress.subscribe(function (newAddress) {
                    if (quote.billingAddress._latestValue != null  && quote.billingAddress._latestValue.countryId != billingAddressCountryId) {
                        billingAddressCountryId = quote.billingAddress._latestValue.countryId;
                        that.filterwalletajax(1);
                        paymentService = true;
                    }
                }); 
                return this;
            },
            filterwalletajax: function(statusCheck){
                if(!statusCheck){
                    return;
                }
                if (quote.billingAddress._latestValue == null) {
                    return;
                }
                var ccavailabletypes = this.getCcAvailableTypes();
                var filtercclist = {};

                fullScreenLoader.startLoader();                
                filtercclist = ccavailabletypes;

                var ccTypesArr1 = _.map(filtercclist, function (value, key) {
                    return {
                       'ccValue': key,
                       'ccLabel': value
                    };
                });
                fullScreenLoader.stopLoader();
                ccTypesArr(ccTypesArr1);
                //filtersavedcardLists(filtercards);
            },
            getCcAvailableTypesValues : function(){
                return ccTypesArr;
            },
            availableCCTypes : function(){
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
            selectedCCType : ko.observable(),
            //paymentToken:ko.observable(),

            getCode: function() {
                return 'worldpay_wallets';
            },

            getTemplate: function(){
                return this.direcTemplate;
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
               return window.checkoutConfig.payment.ccform.walletstitle ;
            },
            isActive: function() {
                return true;
            },
            paymentMethodSelection: function() {
                return window.checkoutConfig.payment.ccform.paymentMethodSelection;
            },
            getselectedCCType : function(){
                if(this.paymentMethodSelection()=='radio'){
                     return $("input[name='wallets_type']:checked").val();
                    } else{
                      return  this.selectedCCType();
                }
            },

            /**
             * @override
             */
            getData: function () {
                return {
                    'method': "worldpay_wallets",
                    'additional_data': {
                        'cc_type': this.getselectedCCType(),
                        'walletResponse' : googleResponse,
                        'dfReferenceId': window.sessionId,
                        'appleResponse' : appleResponse
                    }
                };
            },
           
            preparePayment:function() {
                var self = this;
                var $form = $('#' + this.getCode() + '-form');
                if(this.getselectedCCType()== undefined){
                    $('.mage-error').css({'display' : 'block','margin-bottom': '7px'});
                    $('.mage-error').html(getCreditCardExceptions('CCAM6'));
                    return false;
                }
                if (this.getselectedCCType()=='PAYWITHGOOGLE-SSL') {
                    const paymentsClient = getGooglePaymentsClient();
                    const paymentDataRequest = getGooglePaymentDataRequest();
                    paymentDataRequest.transactionInfo = getGoogleTransactionInfo();
                    paymentsClient.loadPaymentData(paymentDataRequest)
                    .then(function(paymentData) {
                        googleResponse = JSON.stringify(paymentData);
                        if($form.validation() && $form.validation('isValid')){
                            fullScreenLoader.startLoader();
                            if(window.checkoutConfig.payment.ccform.isDynamic3DS2Enabled){
                                createJwt();
                                window.addEventListener("message", function(event) {
                                    var data = JSON.parse(event.data);
                                    var envUrl;
                                    if(window.checkoutConfig.payment.ccform.jwtEventUrl !== '') {
                                        envUrl = window.checkoutConfig.payment.ccform.jwtEventUrl;
                                    }
                                    if (event.origin === envUrl) {
                                        var data = JSON.parse(event.data);
                                        //console.warn('Merchant received a message:', data);
                                        if (data !== undefined && data.Status) {
                                            //window.sessionId = data.SessionId;
                                            var sessionId = data.SessionId;
                                            if(sessionId){
                                                this.dfReferenceId = sessionId;
                                            }
                                            window.sessionId = this.dfReferenceId;
                                            //place order with direct CSE method
                                            fullScreenLoader.stopLoader();
                                            self.placeOrder();
                                        }
                                    }
                                }, false);
                            } else {
                                fullScreenLoader.stopLoader();
                                self.placeOrder();
                            }
                            //self.placeOrder();
                        }else {
                            return $form.validation() && $form.validation('isValid');
                        }
                    })
                    .catch(function(err) {
                        // show error in developer console for debugging
                        console.error(err);
                        return false;
                    });
                    
                } else if (this.getselectedCCType()=='APPLEPAY-SSL') {
                    
                    //---------------------------------- Apple Pay starts -----------------------
             
                    var baseGrandTotal     = window.checkoutConfig.totalsData.base_subtotal;
                    var runningAmount = (Math.round(baseGrandTotal * 100) / 100).toFixed(2);
                    var subTotal    = window.checkoutConfig.quoteData.base_grand_total;
                    var runningTotal = (Math.round(subTotal * 100) / 100).toFixed(2);
                    var subTotalDescr    = "Cart Subtotal";
                    var currencyCode = window.checkoutConfig.quoteData.quote_currency_code;
                    var countryCode = window.checkoutConfig.defaultCountryId;
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

                    var session = new ApplePaySession(1, paymentRequest);

                    // Merchant Validation
                    session.onvalidatemerchant = function (event) {                        
                        var promise = performValidation(event.validationURL);
                        promise.then(function (merchantSession) {
                            session.completeMerchantValidation(merchantSession);
                        }); 
                    }

                    session.onpaymentmethodselected = function(event) {                    

                        var linkUrl = url.build('worldpay/applepay/index?u=getTotal');                         
                        var xhttp = new XMLHttpRequest();
                        xhttp.open("GET", linkUrl, false);
                         xhttp.setRequestHeader("Content-type", "application/json");
                        xhttp.send();
                        var finalTotal = xhttp.responseText.slice(1, -1); // removing quotes
                        
                        var runningTotal = (Math.round(finalTotal * 100) / 100).toFixed(2);
                        var newTotal = { type: 'final', label: 'Order Total', amount: runningTotal };
                        var newLineItems =[{type: 'final',label: subTotalDescr, amount: runningAmount }];

                        session.completePaymentMethodSelection( newTotal, newLineItems );
                    }

                    session.onpaymentauthorized = function (event) {
                        var promise = sendPaymentToken(event.payment.token);

                        promise.then(function (success) {    
                            var status;
                            if (success){
                                status = ApplePaySession.STATUS_SUCCESS;
                            } else {
                                status = ApplePaySession.STATUS_FAILURE;
                            }
                            session.completePayment(status);
                        });
                        appleResponse = JSON.stringify(event.payment.token);
                        self.placeOrder();       
                    }

                    session.oncancel = function(event) {
                    }

                    session.begin();
                }  else if (this.getselectedCCType()=='SAMSUNGPAY-SSL') {
                    console.log('Initiating Samsung Pay........');

                var quoteId = window.checkoutConfig.quoteData.entity_id;
                    
                    var linkUrl = url.build('worldpay/samsungpay/index?quoteId=' + quoteId);                         
                        var xhttp = new XMLHttpRequest();
                        xhttp.open("GET", linkUrl, false);
                         
                        xhttp.send(); 
                        
                        var response = JSON.parse(xhttp.responseText);                                           
                         response1 = JSON.parse(response); 
                         
                         if(response1.resultMessage == 'SUCCESS') {
                             self.placeOrder();           
                           
                         }

                } //else if end
            },
            afterPlaceOrder: function (data, event) { 
              if (this.getselectedCCType()=='SAMSUNGPAY-SSL') {
                var cancel = url.build('worldpay/samsungpay/CallBack');
                var serviceId = window.checkoutConfig.payment.ccform.samsungServiceId;
 
                var callback = url.build('worldpay/samsungpay/CallBack');
                var countryCode = window.checkoutConfig.defaultCountryId;
                console.log('Authentication is success, Redirecting to Samsung Payment Page......');
                                  SamsungPay.connect(
                        response1.id, response1.href, serviceId, callback, cancel, countryCode,
                        response1.encInfo.mod, response1.encInfo.exp, response1.encInfo.keyId
                        ); 
            } else if (this.getselectedCCType()=='PAYWITHGOOGLE-SSL') {
                window.location.replace(url.build('worldpay/savedcard/redirect'));
            } else{
             window.location.replace(url.build('worldpay/wallets/success'));
            }
        }
    }); //return ends
           
    function performValidation(valURL) {
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
    }

    function getRealTotal() {
        var linkUrl = url.build('worldpay/applepay/index?u=getTotal');
                
        var xhttp = new XMLHttpRequest();
        xhttp.open("GET", linkUrl, false);
        xhttp.setRequestHeader("Content-type", "application/json");
        xhttp.send();
       
        var finalTotal = xhttp.responseText;

    }
    function createJwt(){
            var encryptedBin = "";
            var jwtUrl = url.build('worldpay/hostedpaymentpage/jwt');
            $('body').append('<iframe src="'+jwtUrl+'?instrument='+encryptedBin+'" name="jwt_frm" id="jwt_frm" style="display: none"></iframe>');
    }
    function sendPaymentToken(paymentToken) {
        return new Promise(function(resolve, reject) {
            var appleResponse = paymentToken;

            if ( debug == true )
            resolve(true);
            else
            reject;
        });
    }        
});
