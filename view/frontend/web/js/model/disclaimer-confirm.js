define([
    'ko',
    'jquery',
    'underscore',
    'Magento_Customer/js/model/customer',
    'Magento_Ui/js/modal/confirm'

], function (ko, $, _, customer, confirmation) {
    'use strict';
    
    return {
        openDisclaimer: function() {
            var self= this;
            var mandatoryMessage = this.isDisclaimerMessageMandatory();
            var disclaimerText = document.getElementById('dialog').innerHTML;
            var disclaimerFlag = false;     
            confirmation({
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
                        this.closeModal(event, true);
                    }
                },{
                    text: $.mage.__('Disagree'),
                    class: 'action-secondary action-dismiss',
                    click: function (event) {
                        disclaimerFlag = false;
                        window.disclaimerDialogue = false;
                        $('#disclaimer-error').css('display', 'none');
                        if(self.isStoredCredentialsEnabled() && self.isDisclaimerMessageEnabled()){
                            this.saveMyCard = '';
                            $('#' + self.getCode() + '_save_card').prop( "checked", false );
                        }
                        this.closeModal(event);
                    }
                }]
            });
            $('.modal-popup .modal-header h1').css({"color":"#333","font-weight":"bold","font-size":"1em"});
            $('.modal-popup .modal-content').css({"height":"232px"});
            $('.modal-popup .modal-footer').css({"text-align":"right","border-top":"1px solid #ddd"}); 
            return false;

        },    
        isStoredCredentialsEnabled: function (){
            if(customer.isLoggedIn()){
                return window.checkoutConfig.payment.ccform.storedCredentialsAllowed;
            }
        },

        isDisclaimerMessageEnabled: function (){
            if(customer.isLoggedIn()){
                return window.checkoutConfig.payment.ccform.isDisclaimerMessageEnabled;
            }
        },
        isDisclaimerMessageMandatory: function (){
            if(customer.isLoggedIn()){
                return window.checkoutConfig.payment.ccform.isDisclaimerMessageMandatory;
            }
        },
        getCode: function () {
            return 'worldpay_cc';
        }
    };

});
