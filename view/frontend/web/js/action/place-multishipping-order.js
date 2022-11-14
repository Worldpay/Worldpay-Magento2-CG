define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Customer/js/customer-data',
        'Magento_Ui/js/modal/alert',
        'mage/url'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, fullScreenLoader, customerData, alert, url) {
        'use strict';        
        return function (paymentData, samsungResponse = null) {
            var serviceUrl, payload;
            payload = {
                cartId: quote.getQuoteId(),
                billingAddress: quote.billingAddress(),
                paymentMethod: paymentData
            };

            var serviceUrl = urlBuilder.createUrl('/worldpay/place_multishipping_order', {});

            storage.post(serviceUrl, JSON.stringify(payload))
                .done(function (result) {
                    fullScreenLoader.stopLoader();
                    var response = JSON.parse(result);
                    if (response.status == 'error') {
                        $("#" + response.method + "_multishipping_error").css('display', 'block');
                        $("#" + response.method + "_multishipping_error").html("<div>" + response.message + "</div>");
                        return false;
                    }
                    if (response.cc_type == 'SAMSUNGPAY-SSL') {
                        var cancel = url.build('worldpay/samsungpay/CallBack');
                        var serviceId = window.checkoutConfig.payment.ccform.samsungServiceId;
                        var callback = url.build('worldpay/samsungpay/CallBack');
                        var countryCode = window.checkoutConfig.defaultCountryId;
                        console.log('Authentication is success, Redirecting to Samsung Payment Page......');
                        SamsungPay.connect(
                            samsungResponse.id, samsungResponse.href, serviceId, callback, cancel, countryCode,
                            samsungResponse.encInfo.mod, samsungResponse.encInfo.exp, samsungResponse.encInfo.keyId
                        );
                        return false;
                    }
                    if (response.redirect) {
                        return $.mage.redirect(response.redirect);
                    }
                    return true;
                })
                .fail(function (result) {
                    fullScreenLoader.stopLoader();
                    //Scroll to top
                    window.scrollTo({top: 0, behavior: 'smooth'});
                    errorProcessor.process(result);
                });
        };
    }
);