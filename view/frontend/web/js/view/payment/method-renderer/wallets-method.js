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
        'googlePay'
    ],
    function (Component, $, quote, customer,validator, url, placeOrderAction, redirectOnSuccessAction,ko, setPaymentInformationAction, errorProcessor, urlBuilder, storage, fullScreenLoader, googlePay) {
        'use strict';
        var ccTypesArr = ko.observableArray([]);
        var paymentService = false;
        var billingAddressCountryId = "";
        var googleResponse = "";
        
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
                
                merchantId: 'b21b1d14ba43077',
                merchantName: window.checkoutConfig.payment.ccform.googleGatewayMerchantname
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
                        'walletResponse' : googleResponse
                    }
                };
            },
            preparePayment:function() {
                var self = this;
                var $form = $('#' + this.getCode() + '-form');
				if(this.getselectedCCType()== undefined){
					$('.mage-error').css({'display' : 'block','margin-bottom': '7px'});
					$('.mage-error').html('Please select one of the options.');
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
                            self.placeOrder();
                        }else {
                            return $form.validation() && $form.validation('isValid');
                        }
                    })
                    .catch(function(err) {
                        // show error in developer console for debugging
                        console.log('payment client resposnse else condition');
                        console.error(err);
                        return false;
                    });
                }
                
            },
            afterPlaceOrder: function (data, event) {
                window.location.replace(url.build('worldpay/wallets/success'));
            }

        });
    }
);
