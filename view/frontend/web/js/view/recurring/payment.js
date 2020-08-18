/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'mage/translate',
        'Magento_Ui/js/modal/alert'
    ],
    function ($, $t, alert) {
        'use strict';
        
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

        $.widget('mage.eprotect', {

            /**
             * Validation creation.
             *
             * @protected
             */
            _create: function () {
                $('#worldpay-subscription-edit').submit(this.submitEprotect.bind(this));
                this.options.submitFlag = false;

                var scriptUrl = this.options.config.scriptUrl;
                delete this.options.config.scriptUrl;

                require([scriptUrl], this.initEprotect.bind(this));

                $("input[name='worldpay_subscription_payment']").change(this.paymentMethodChange.bind(this));
            },

            /**
             * Payment Method change callback
             *
             * @param event
             */
            paymentMethodChange: function(event) {
                if(this.isNewCard()) {
                    $('#new_card').show();
                } else {
                    $('#new_card').hide();
                }
            },

            /**
             * Init payframe client.
             */
            initEprotect: function () {
                this.options.config.callback = this.eprotectCallback.bind(this);
                this.payframeClient = new LitlePayframeClient(this.options.config);
            },

            /**
             * Submit eProtect iFrame.
             */
            submitEprotect: function () {
                if(!this.isNewCard()) {
                    return true;
                }
                if (!this.options.submitFlag) {
                    this.payframeClient.getPaypageRegistrationId({
                        "id": Math.floor(Math.random() * 999999),
                        "orderId": ""
                    });

                    return false;
                } else {
                    return true;
                }
            },

            /**
             * eProtect submit callback.
             *
             * @param responseData
             */
            eprotectCallback: function (responseData) {
                if (responseData.response == "870") {
                    this.options.submitFlag = true;

                    $("#worldpay-paypage-registration-id").val(responseData.paypageRegistrationId);
                    $("#worldpay-cc-type").val(responseData.type);
                    $('#worldpay-subscription-edit').submit();
                } else {
                    alert({
                        title: $t('Credit Card Form'),
                        content: $t(getCreditCardExceptions('CCAM10')) + $t(responseData.message)
                    });
                }
            },

            /**
             * Determines if user is entering new card data
             *
             * @returns {boolean}
             */
            isNewCard: function() {
                return ($("input[name='worldpay_subscription_payment']:checked").val() === "-2");
            }
        });

        return $.mage.eprotect;
    }
);
