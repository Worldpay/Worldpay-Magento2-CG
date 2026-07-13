define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'underscore',
        'mage/translate',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Sapient_Worldpay/js/model/checkout-utils',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Customer/js/model/customer',
    ], function ($, ko, Component, _, $t, quote, customerData, checkoutUtils, urlBuilder, url, customer) {

        'use strict';

        return Component.extend({
            defaults: {
                template: 'Sapient_Worldpay/payment/paypal'
            },

            initialize: function () {
                this._super();
                this.loadPaypalSdk();
            },

            loadPaypalSdk: function() {
                if(!window.paypal) {
                    const script = document.createElement('script');
                    var currency = quote.totals().quote_currency_code;
                    var clientId = window.checkoutConfig.payment.ccform.paypalClientId;
                    if(!clientId) {
                        return;
                    }
                    script.src = 'https://www.paypal.com/sdk/js?client-id=' + clientId + '&currency=' + currency + '&intent=authorize';
                    script.type = 'text/javascript';
                    script.async = true;
                    document.head.appendChild(script);
                }


                $(document).ready(function () {
                    var checkExists = setInterval(function () {
                        var container = $('#paypal-button-container');
                        if(container.length) {
                            clearInterval(checkExists);
                            let orderId = null;

                            paypal.Buttons({
                                style: {
                                    layout: 'horizontal',
                                    height: 40,
                                    tagline: false
                                },

                                createOrder: async function() {
                                    var checkoutData = checkoutUtils.createCheckoutData(quote, customer, 'worldpay_apm', 'PAYPAL-SSL');
                                    checkoutData.paymentDetails.additional_data.paypal_smart = true;
                                    var data = await checkoutUtils.setPaymentInformationAndPlaceOrder(checkoutData);

                                    if(data.error){
                                        return Promise.reject(new Error('Failed to create Paypal order'));
                                    }

                                    var paypalId = data.paypalId;
                                    orderId = data.orderId;
                                    return paypalId;
                                },

                                onApprove: async function (data, actions) {
                                    var approveUrl = BASE_URL + 'rest/V1/worldpay/paypal/order/approve';

                                    return fetch(approveUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json'
                                        },
                                        body: JSON.stringify({'orderId': orderId})
                                    }).then(response => response.json())
                                        .then(result => {
                                            const parsedResult = JSON.parse(result);
                                            if(parsedResult.success) {
                                                window.location.href = BASE_URL + 'worldpay/redirectresult/success';
                                                return;
                                            } else {
                                                return Promise.reject(new Error(response.message));
                                            }
                                    }).catch((error) => {
                                        console.log(error);
                                        return Promise.reject(error);
                                    });
                                },

                                onCancel: () => window.location.href = BASE_URL + 'worldpay/redirectresult/cancel',

                                onError: err => window.location.href = BASE_URL + 'worldpay/redirectresult/error'

                            }).render('#paypal-button-container');
                        }
                    }, 300);
                });
            },

            isActive: function () {
                if(!window.checkoutConfig.payment.ccform.paypalSmartButton){
                    return false;
                }

                const currencies = window.checkoutConfig.payment.ccform.paypalCurrency.split(",");

                if(!currencies.includes(quote.totals().quote_currency_code)){
                    return false;
                }

                return true;
            }
        });
    });

