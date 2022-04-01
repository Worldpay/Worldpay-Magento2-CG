/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($,customerData) {
    'use strict';

    return function (Component) {
        return Component.extend({
            checkoutNowUrl: window.checkout.shoppingCartUrl + '?chromepay=1',
            proceedToCheckoutUrl: window.checkout.checkoutUrl,
            checkoutNowTitle: window.ChromepayButtonName,
            chromepayEnabled: window.ChromepayEnabled,
            chromePaymentMode: window.ChromePaymentMode,
            isChromium: window.ChromepayAvailable,

            /**
             * Returns subscription status of cart item
             * @returns {Boolean}
             */
            getSubscriptionStatus: function () {
                var i, o;
                var cartItems = customerData.get('cart')().items;
                var options = [];
                for (i = 0; i < cartItems.length; i++) {
                    options = cartItems[i].options;
                    for (o = 0; o < options.length; o++) {
                        var plan_id = cartItems[i].options[o].plan_id;
                        if (plan_id) {
                            return true;
                        }
                    }
                }
                return false;
            }
        });
    }
});