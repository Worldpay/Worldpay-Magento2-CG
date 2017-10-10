define([
    "jquery",
    "worldpay"
], function($, worldpay){
    "use strict";
    $.widget('mage.worldpayForm', {
         options: {
            clientKey: false
        },
        prepare : function(event, method) {
            if (method === 'worldpay_moto') {
                this.preparePayment();
                this.ccForm();
            }

        },
        preparePayment: function() {
             var self = this;
        },
        ccForm: function(){
            var cctypevalue = $('#worldpay_cc_type').val();
             if (cctypevalue=="savedcard") {
                $(".saved_card_form").show();
                $(".directform").find('input, select').prop('disabled',true);
                $(".saved_card_form").find('input, select').prop('disabled',false);
                $(".directform").hide();
            }else{
                $(".saved_card_form").hide();
                $(".directform").find('input, select').prop('disabled',false);
                $(".saved_card_form").find('input, select').prop('disabled',true);
                $(".directform").show();
            }
        },
        _create: function() {  
            var self = this;
            $('#edit_form').on('changePaymentMethod', this.prepare.bind(this));
            if ($('.saved_tokens').length) {
                $('.saved_tokens').first().click();
            }
           this.ccForm();
           $( "#worldpay_cc_type" ).on('change', this.ccForm.bind(this));
        }
    });

    return $.mage.worldpayForm;
});
