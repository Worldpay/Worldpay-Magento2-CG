/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    "jquery"
], function ($) {
    'use strict';

    var mixin = {
        replacePrice: function replacePrice(newPrices) {
            if (newPrices) {
                $.extend(this.options, newPrices);
            }

            this.element.trigger('updatePrice');
        },

        /**
         * Call on event replacePrice. Proxy to replacePrice method.
         * @param {Event} event
         * @param {Object} prices
         */
        onReplacePrice: function onUpdatePrice(event, prices) {
            return this.replacePrice(prices);
        },

        _create: function createPriceBox() {
            this._super();
            this.element.on('replacePrice', this.onReplacePrice.bind(this));
        }
    };

    return function (target) {
        $.widget('mage.priceBox', target, mixin);
        return $.mage.priceBox;
    };
});
