/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    // 'Magento_Checkout/js/model/quote',
    'jquery-ui-modules/widget',
    'mage/translate'
], function ($, confirm) {
    'use strict';
    function getCreditCardExceptions(exceptioncode) {
        var data = window.CreditCardException;
        var gendata = JSON.parse(data);
        for (var key in gendata) {
            if (gendata.hasOwnProperty(key)) {
                var cxData = gendata[key];
                if (cxData['exception_code'].includes(exceptioncode)) {
                    return cxData['exception_module_messages'] ?
                            cxData['exception_module_messages'] : cxData['exception_messages'];
                }
            }
        }

    }
    function getMyAccountExceptions(exceptioncode) {
        var data = window.MyAccountExceptions;
        var gendata = JSON.parse(data);
        for (var key in gendata) {
            if (gendata.hasOwnProperty(key)) {
                var cxData = gendata[key];
                if (cxData['exception_code'].includes(exceptioncode)) {
                    return cxData['exception_module_messages'] ?
                            cxData['exception_module_messages'] : cxData['exception_messages'];
                }
            }
        }

    }

    $.widget('mage.newcard', {
        /**
         * Options common to all instances of this widget.
         * @type {Object}
         */
        options: {
            confirmMessage: $.mage.__(getMyAccountExceptions('IAVMA1') ?
                    getMyAccountExceptions('IAVMA1') :
                    'Please verify the Billing Address in your Address Book before adding new card!')
        },

        /**
         * Bind event handlers for adding and deleting addresses.
         * @private
         */
        _create: function () {
            var options = this.options,
                    addCard = options.addCard;

            if (addCard) {
                $(document).on('click', addCard, this._addCard.bind(this));
            }
        },

        /**
         * Add a new address.
         * @private
         */
        _addCard: function () {
            var self = this;
            var isBillingFound = this.options.isBilling;
            confirm({
                content: this.options.confirmMessage,
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        if (isBillingFound) {
                            window.location = self.options.addCardLocation;
                        }
                    }
                }
            });

            return false;
        }
    });

    return $.mage.newcard;
});
