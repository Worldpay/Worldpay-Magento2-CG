/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */
define([
    'jquery',
    'Magento_Ui/js/form/form'
], function ($, Form) {
    'use strict';

    return Form.extend({
        defaults: {
            modules: {
                plansGrid: 'product_form.product_form.subscriptions.worldpay_recurring_plans.plans'
            }
        },

        /**
         * Set additional data to source before form submit and after validation.
         *
         * @param {Object} data
         * @returns {Object}
         */
        setAdditionalData: function (data) {
            var plansGrid = this.plansGrid();
            if (plansGrid) {
                data = data || [];
                data['sort_order'] = plansGrid.maxPosition + 1;
            }

            return this._super(data);
        }
    });
});
