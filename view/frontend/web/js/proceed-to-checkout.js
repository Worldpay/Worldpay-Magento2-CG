define([
    'jquery',
    'Magento_Customer/js/model/authentication-popup',
    'Magento_Customer/js/customer-data'
], function ($, authenticationPopup, customerData) {
    'use strict';

    return function (config, element) {

        $(element).click(function (event) {
            var cart = customerData.get('cart'),
                customer = customerData.get('customer');

            event.preventDefault();
            //
            // if (!customer().firstname && cart().isGuestCheckoutAllowed === false) {
            //     authenticationPopup.showModal();
            //
            //     return false;
            // }

             if(window.PaymentRequest && window.ChromepayEnabled == 1 && window.ChromePaymentMode == 'direct') {
                var request = initPaymentRequest();
                onBuyClicked(request);
                request = initPaymentRequest();
                
                //console.log(request);
            } else {
                location.href = this.options.url.checkout;
            }
        });

    };
});