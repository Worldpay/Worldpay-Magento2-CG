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
            browserScreenHeight: window.screen.height,
            browserScreenWidth: window.screen.width,
            browserColorDepth: window.screen.colorDepth,
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
                .observe(
                    'showButton paymentToken shippingAddress billingAddress shippingMethod dfreference browserScreenHeight browserScreenWidth browserColorDepth');

            return this;
        },

        /**
         * Set data from customerData.
         *
         * @param {Object} data
         */
        setPurchaseData: function (data) {
            var self = this;
            this.showButton(data.available);
            var fulltoken = data.paymentToken;
            if(window.isDynamic3DS2Enabled && (typeof fulltoken !== 'undefined')){
                var paymentTok = fulltoken['summary'].split(", bin:")[0];
                this.bin = fulltoken['summary'].split(", bin:")[1];
                fulltoken['summary'] = paymentTok;
                if(this.bin !== null) {
                    var encryptedBin = btoa(this.bin);
                    window.sessionId = this.sessionId;

                    var jwtUrl = new URL(this.jwtUrl, window.location.origin);

                    if (jwtUrl.origin !== window.location.origin) {
                        return;
                    }

                    jwtUrl.searchParams.set('instrument', encryptedBin);

                    var iframe = document.createElement('iframe');
                    iframe.setAttribute('src', jwtUrl.href);
                    iframe.setAttribute('name', 'jwt_frm');
                    iframe.setAttribute('id', 'jwt_frm');
                    iframe.style.cssText = 'display:none;';

                    document.body.appendChild(iframe);

                    self.registerCardinalListener(self);

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
            this.browserScreenHeight();
            this.browserScreenWidth();
            this.browserColorDepth();
        },

        registerCardinalListener: function(self) {
            const handler = function (event) {
                var expectedOrigin = window.jwtEventUrl;
                if (!expectedOrigin || event.origin !== expectedOrigin) {
                    return;
                }

                var messageData;
                try {
                    messageData = JSON.parse(event.data);
                } catch (e) {
                    console.error('Failed to parse JWT message data:', e);
                    return;
                }

                if (messageData === undefined) {
                    console.warn('JWT message data is undefined');
                    return;
                }

                var sessionId = null;

                if (messageData.Payload && messageData.Payload.ActionCode === "SUCCESS") {
                    sessionId = messageData.Payload.SessionId || this.sessionId;
                } else if (messageData.Status) {
                    sessionId = messageData.SessionId || this.sessionId;
                }

                window.removeEventListener("message", handler);

                if (sessionId) {
                    this.dfreference = sessionId;
                    window.sessionId = sessionId;
                    jQuery('[name=instant_purchase_dfreference]').val(sessionId);
                }
            }

            window.addEventListener("message", handler);
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
                            if(window.isDynamic3DS2Enabled ||
                                window.is3DsEnabled) {
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
