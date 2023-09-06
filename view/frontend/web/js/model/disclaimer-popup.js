/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'jquery-ui-modules/widget',
    'mage/translate'
], function ($, confirm) {
    'use strict';
    $.widget('mage.disclaimerPopup', {
        /**
         * Bind event handlers for agree and disagree disclaimer.
         * @private
         */
        _create: function () {
            var options = this.options,
            disclaimer = options.disclaimer,
            disclaimerMessageEnabled = parseInt(options.disclaimerMessageEnabled),
            storedCredentials = parseInt(options.storedCredentials);
            if (disclaimer && storedCredentials && disclaimerMessageEnabled) {
                $(disclaimer).bind('click',this._disclaimer.bind(this));
            }
        },
        /**
         * Open disclaimer popup.
         * @private
         */
        _disclaimer: function () {
            var self = this;
            var mandatoryMessage = parseInt(this.options.mandatoryMessage);
            var disclaimerText = document.getElementById('dialog').innerHTML;
            confirm({
                type: 'popup',
                innerScroll: true,
                clickableOverlay: false,
                title: $.mage.__('Disclaimer!'),
                content: $.mage.__(disclaimerText),
                buttons: [ {
                    text: $.mage.__('Agree'),
                    class: 'action-primary action-accept',
                    click: function (event) {
                        disclaimerFlag = true;
                        window.disclaimerDialogue = true;
                        $('#disclaimer-error').css('display', 'none');
                        $('#save_newcard').prop( "disabled", false );
                        this.closeModal(event, true);
                    }
                },{
                    text: $.mage.__('Disagree'),
                    class: 'action-secondary action-dismiss',
                    click: function (event) {
                        disclaimerFlag = false;
                        window.disclaimerDialogue = false;
                        $('#disclaimer-error').css('display', 'none');
                        if (mandatoryMessage){
                            $('#save_newcard').prop( "disabled", true );
                        }
                        this.closeModal(event);
                    }
                }]
            });
            $('.modal-popup .modal-header h1').css({"color":"#333","font-weight":"bold","font-size":"1em"});
            $('.modal-popup .modal-content').css({"height":"232px"});
            $('.modal-popup .modal-footer').css({"text-align":"right","border-top":"1px solid #ddd"}); 
            return false;
        }
    });
    return $.mage.disclaimerPopup;
});