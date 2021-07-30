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
    'text!Magento_InstantPurchase/template/confirmation.html',
    'mage/validation'
], function (ko, $, _, Component, confirm, customerData, urlBuilder, mageTemplate, $t, confirmationTemplate) {
    'use strict';
    function getCreditCardExceptions(exceptioncode){
                var data=window.CreditCardException;
                var gendata=JSON.parse(data);
                for (var key in gendata) {
                    if (gendata.hasOwnProperty(key)) {  
                        var cxData=gendata[key];
                    if(cxData['exception_code'].includes(exceptioncode)){
                        return cxData['exception_module_messages']?cxData['exception_module_messages']:cxData['exception_messages'];
                    }
                    }
                }
               
            }

    return Component.extend({
        defaults: {
            template: 'Sapient_Worldpay/instant-purchase/instant-purchase',
            buttonText: $t('Instant Purchase'),
            purchaseUrl: urlBuilder.build('worldpay/button/placeOrder'),
            jwtUrl: urlBuilder.build('worldpay/hostedpaymentpage/jwt'),
            bin: null,
            sessionId: null,
            showButton: false,
            paymentToken: null,
            shippingAddress: null,
            billingAddress: null,
            shippingMethod: null,
            dfreference: null,
            productFormSelector: '#product_addtocart_form',
            confirmationTitle: $t('Instant Purchase Confirmation'),
            confirmationData: {
                message: $t('Are you sure you want to place order and pay?'),
                shippingAddressTitle: $t('Shipping Address'),
                billingAddressTitle: $t('Billing Address'),
                paymentMethodTitle: $t('Payment Method'),
                shippingMethodTitle: $t('Shipping Method')
            }
        },

        /** @inheritdoc */
        initialize: function () {
            var instantPurchase = customerData.get('instant-purchase');
            this._super();
            this.setPurchaseData(instantPurchase());
            instantPurchase.subscribe(this.setPurchaseData, this);
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe('showButton paymentToken shippingAddress billingAddress shippingMethod dfreference');

            return this;
        },

        /**
         * Set data from customerData.
         *
         * @param {Object} data
         */
        setPurchaseData: function (data) {
            this.showButton(data.available);
            var fulltoken = data.paymentToken;
            if(window.isDynamic3DS2Enabled && (typeof fulltoken !== 'undefined')){
                var paymentTok = fulltoken['summary'].split(", bin:")[0];
                fulltoken['summary'] = paymentTok;
                this.bin = fulltoken['summary'].split(", bin:")[1];
                if(this.bin !== null) {
                var encryptedBin = btoa(this.bin);
                window.sessionId = this.sessionId;
                $('body').append('<iframe src="'+this.jwtUrl+'?instrument='+encryptedBin+'" name="jwt_frm" id="jwt_frm" style="display: none"></iframe>');
                window.addEventListener("message", function(event) {
                                    var data = JSON.parse(event.data);
                                    var envUrl;
                                    if(window.jwtEventUrl !== '') {
                                        envUrl = window.jwtEventUrl;
                                    }
                                    if (event.origin === envUrl) {
                                        var data = JSON.parse(event.data);
                                        if (data !== undefined && data.Status) {
                                            var sessionId1 = data.SessionId;
                                            if(sessionId1){
                                                this.dfreference = sessionId1;
                                            } else {
                                               this.dfreference = this.sessionId; 
                                            }
                                            window.sessionId = this.dfreference;
                                            jQuery('[name=instant_purchase_dfreference]').val(this.dfreference);
                                         }
                                    }
                                }, false);
                this.paymentToken(fulltoken);
                this.dfreference(window.sessionId);
            } else {
                alert(getCreditCardExceptions('CCAM2'));
                return; 
            }
            } else {
                 this.paymentToken(data.paymentToken);
                 this.dfreference("");
            }
            this.shippingAddress(data.shippingAddress);
            this.billingAddress(data.billingAddress);
            this.shippingMethod(data.shippingMethod);
        },

        /**
         * Confirmation method
         */
        instantPurchase: function () {
                var form = $(this.productFormSelector),
                confirmTemplate = mageTemplate(confirmationTemplate),
                confirmData = _.extend({}, this.confirmationData, {
                    paymentToken: this.paymentToken().summary,
                    shippingAddress: this.shippingAddress().summary,
                    billingAddress: this.billingAddress().summary,
                    shippingMethod: this.shippingMethod().summary,
                    dfreference: this.dfreference()
                });
            if (!(form.validation() && form.validation('isValid'))) {
                return;
            }
            confirm({
                title: this.confirmationTitle,
                content: confirmTemplate({
                    data: confirmData
                }),
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                                 $.ajax({
                            url: this.purchaseUrl,
                            data: form.serialize(),
                            type: 'post',
                            dataType: 'json',
                            success: function (data) {
                                if(window.isDynamic3DS2Enabled || window.is3DsEnabled) {
                                window.location.replace(urlBuilder.build('worldpay/savedcard/instantredirect'));
                            }
                            },
                            /** Show loader before send */
                            beforeSend: function () {
                                $('body').trigger('processStart');
                            }
                        }).always(function () {
                            $('body').trigger('processStop');
                        });
                    }.bind(this)
                }
            });
        }
    });
});