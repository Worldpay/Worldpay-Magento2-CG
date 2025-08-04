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
                                    var maskedQuoteId = "";
                                    const isCustomerLoggedIn = customer.isLoggedIn();
                                    if (!isCustomerLoggedIn) {
                                        maskedQuoteId = quote.getQuoteId();
                                        quote.billingAddress().email = quote.guestEmail;
                                    }
                                    var shippingrequired = false;
                                    if (quote.shippingMethod()) {
                                        shippingrequired = true;
                                    }
                                    var checkoutData = {
                                        billingAddress: quote.billingAddress(),
                                        shippingAddress: quote.shippingAddress(),
                                        shippingMethod: quote.shippingMethod(),
                                        paymentDetails: {
                                            'method': "worldpay_apm", //TBD
                                            'additional_data': {
                                                'cc_type': 'PAYPAL-SSL',
                                                'paypal_smart': true,
                                                'cse_enabled': false
                                            }
                                        },
                                        storecode: window.checkoutConfig.storeCode,
                                        quote_id: quote.getQuoteId(),
                                        guest_masked_quote_id: maskedQuoteId,
                                        isCustomerLoggedIn: isCustomerLoggedIn,
                                        isRequiredShipping: shippingrequired
                                    }

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

