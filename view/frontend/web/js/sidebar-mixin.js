/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Customer/js/model/authentication-popup',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'underscore',
    'jquery-ui-modules/widget',
    'mage/decorate',
    'mage/collapsible',
    'mage/cookies',
    'jquery-ui-modules/effect-fade'
], function ($, authenticationPopup, customerData, alert, confirm, _) {
    'use strict';

    function getMyAccountExceptions(exceptioncode) {
        var data = window.MyAccountExceptions;
        var gendata = JSON.parse(data);
        for (var key in gendata) {
            if (gendata.hasOwnProperty(key)) {
                var cxData = gendata[key];
                if (cxData['exception_code'] === exceptioncode) {
                    return cxData['exception_module_messages'] ? cxData['exception_module_messages'] : cxData['exception_messages'];
                }
            }
        }
    }

    return function (widget) {
        $.widget('mage.sidebar', widget , {
            /**
         * @private
         */
        _initContent: function () {
            var self = this,
                events = {};

            this.element.decorate('list', this.options.isRecursive);

            /**
             * @param {jQuery.Event} event
             */
            events['click ' + this.options.button.close] = function (event) {
                event.stopPropagation();
                $(self.options.targetElement).dropdownDialog('close');
            };
            events['click ' + this.options.button.checkout] = $.proxy(function () {
                var cart = customerData.get('cart'),
                    customer = customerData.get('customer'),
                    element = $(this.options.button.checkout);

                if (!customer().firstname && cart().isGuestCheckoutAllowed === false) {
                    // set URL for redirect on successful login/registration. It's postprocessed on backend.
                    $.cookie('login_redirect', this.options.url.checkout);

                    if (this.options.url.isRedirectRequired) {
                        element.prop('disabled', true);
                        location.href = this.options.url.loginUrl;
                    } else {
                        authenticationPopup.showModal();
                    }

                    return false;
                }
                
                element.prop('disabled', true);
                location.href = this.options.url.checkout;
                if(window.PaymentRequest && window.ChromepayEnabled == 1 && window.ChromePaymentMode == 'direct') {	
                    var request = initPaymentRequest();	
                    onBuyClicked(request);	
                    // request = initPaymentRequest();	
                } else {	
                    location.href = this.options.url.checkout;	
                }
            }, this);

            /**
             * @param {jQuery.Event} event
             */
            events['click ' + this.options.button.remove] =  function (event) {
                event.stopPropagation();
                confirm({
                    content: getMyAccountExceptions('MCAM3'),
                    actions: {
                        /** @inheritdoc */
                        confirm: function () {
                            self._removeItem($(event.currentTarget));
                        },

                        /** @inheritdoc */
                        always: function (e) {
                            e.stopImmediatePropagation();
                        }
                    }
                });
            };

            /**
             * @param {jQuery.Event} event
             */
            events['keyup ' + this.options.item.qty] = function (event) {
                self._showItemButton($(event.target));
            };

            /**
             * @param {jQuery.Event} event
             */
            events['change ' + this.options.item.qty] = function (event) {
                self._showItemButton($(event.target));
            };

            /**
             * @param {jQuery.Event} event
             */
            events['click ' + this.options.item.button] = function (event) {
                event.stopPropagation();
                self._updateItemQty($(event.currentTarget));
            };

            /**
             * @param {jQuery.Event} event
             */
            events['focusout ' + this.options.item.qty] = function (event) {
                self._validateQty($(event.currentTarget));
            };

            this._on(this.element, events);
            this._calcHeight();
        },
    });
        return $.mage.sidebar;
    }
});
