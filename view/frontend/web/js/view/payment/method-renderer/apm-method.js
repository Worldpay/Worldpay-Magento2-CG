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
        'ko'
    ],
    function (Component, $, quote, customer,validator, url, placeOrderAction, redirectOnSuccessAction,ko) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                redirectTemplate: 'Sapient_Worldpay/payment/apm'
            },
             availableCCTypes : function(){
                var ccTypesArr = _.map(this.getCcAvailableTypes(), function (value, key) {
                                       return {
                                        'ccValue': key,
                                        'ccLabel': value
                                    };
                                });
                return ko.observableArray(ccTypesArr);
            },
            selectedCCType : ko.observable(),
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
            paymentMethodSelection: function() {
                return window.checkoutConfig.payment.ccform.paymentMethodSelection;
            },
            preparePayment:function() {
                var self = this;
                var $form = $('#' + this.getCode() + '-form');
                if($form.validation() && $form.validation('isValid')){
                    self.placeOrder();
                } else {
                    return $form.validation() && $form.validation('isValid');
                }
            },

            afterPlaceOrder: function (data, event) {
                window.location.replace(url.build('worldpay/redirectresult/redirect'));
            }

          
        });
    }
);


