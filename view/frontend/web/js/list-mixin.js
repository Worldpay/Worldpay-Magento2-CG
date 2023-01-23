/**
 * Mixin for Magento_Checkout/js/view/payment/list
 *
 * set template file using mixins
 */
 define([
    'jquery',
	'underscore',
    'ko',
	'uiComponent'
], function ($, _, ko, Component) {
    'use strict';

    let mixin = {
		defaults: {
                template: 'Sapient_Worldpay/payment-methods/list',
            },
			/**
             * Returns worldpay enable or disable
             */
			isWorldPayEnable: function () {
                var configValues = window.checkoutConfig.payment.general.worldPayEnable;
                return configValues;
            }
    };
    return function (target) {
        return target.extend(mixin);
    };
});