/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */
define([
    'jquery',
    'Magento_Ui/js/form/components/insert-form'
], function ($, InsertForm) {
    'use strict';

    return InsertForm.extend({
        defaults: {
            modules: {
                plansGrid: 'product_form.product_form.subscriptions.worldpay_recurring_plans.plans'
            },
            listens: {
                responseStatus: 'processResponseStatus',
                responseData: 'processResponseData'
            }
        },

        /**
         * Process response status.
         */
        processResponseStatus: function () {
            if (this.responseStatus()) {
                this.resetForm();
            }
        },

        /**
         * Process response data
         *
         * @param {Object} data
         */
        processResponseData: function (data) {
            var self = this;
            $('body').notification('clear');
            if (data.messages || data.message) {
                $.each(data.messages || [data.message] || [], function (key, message) {
                    $('body').notification('add', {
                        error: data.error,
                        message: message,

                        /**
                         * Insert method.
                         *
                         * @param {String} msg
                         */
                        insertMethod: function (msg) {
                            var $wrapper = $('</div>').addClass(self.messagesClass).html(msg);

                            $('.page-main-actions', self.prefix).after($wrapper);
                        }
                    });
                });
            }

            if (data.plan_data) {
                var plansGrid = this.plansGrid();
                plansGrid.source.set(
                    plansGrid.dataScope + '.' + plansGrid.index + '.' + plansGrid.recordData().length,
                    data.plan_data
                );
                plansGrid.processingAddChild(data.plan_data, plansGrid.recordData().length - 1, data.plan_data.plan_id);
            }
        }
    });
});
