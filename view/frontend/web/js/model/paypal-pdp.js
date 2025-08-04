/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define(
    [
        'jquery',
        'ko',
    ], function ($, ko) {
        'use strict';

        return {
            loadPaypalSdk: function(callback) {
                if(!window.paypal) {
                    const script = document.createElement('script');
                    var currency = window.currency;
                    var clientId = window.paypalClientId;
                    if(!clientId || !currency) {
                        return;
                    }
                    script.src = 'https://www.paypal.com/sdk/js?client-id=' + clientId + '&currency=' + currency + '&intent=authorize';
                    script.type = 'text/javascript';
                    script.async = true;

                    script.onload = function() {
                        $(document).ready(function () {
                            var checkExists = setInterval(function () {
                                var container = $('#paypal-button-fake');
                                if (container.length) {
                                    clearInterval(checkExists);

                                    paypal.Buttons({
                                        style: {
                                            layout: 'horizontal',
                                            height: 40,
                                            tagline: false
                                        },

                                        onInit(data, actions) {
                                            actions.disable();
                                        },

                                        onClick: function (data, actions) {
                                            callback('paypal');
                                            return actions.reject();
                                        },
                                    }).render('#paypal-button-fake');
                                }
                            }, 300);
                        });
                    }
                    document.head.appendChild(script);
                }
            },

            triggerRealPaypal: function (callback) {
                $(document).ready(function () {
                    var checkExists = setInterval(function () {
                        var container = $('#paypal-button-real');
                        if (container.length) {
                            clearInterval(checkExists);
                            let orderId = null;
                            paypal.Buttons({
                                style: {
                                    layout: 'horizontal',
                                    height: 32,
                                    tagline: false
                                },

                                onInit: function(data, actions) {
                                    window.walletpayObj.selectedShippingAddress.subscribe(() => validateSelections());
                                    window.walletpayObj.selectedShippingMethod.subscribe(() => validateSelections());

                                    function validateSelections() {
                                        const address = window.walletpayObj.dropdownSelectionShippingAddress();
                                        const method = window.walletpayObj.selectedShippingMethod();

                                        if(address) {
                                            window.walletpayObj.showNoShippingAddressError(false);
                                        }

                                        if(method) {
                                            window.walletpayObj.showNoShippingMethodError(false);
                                        }

                                        address && method ? actions.enable() : actions.disable();
                                    }
                                },

                                onClick: function(data, actions) {
                                    const address = window.walletpayObj.dropdownSelectionShippingAddress();
                                    const method = window.walletpayObj.selectedShippingMethod();

                                    window.walletpayObj.showNoShippingAddressError(!address);
                                    window.walletpayObj.showNoShippingMethodError(address && !method);

                                    return (!address || !method) ? actions.reject() : actions.resolve();
                                },

                                createOrder: async function () {
                                    var data = await callback();
                                    orderId = data.orderId;
                                    return data.paypalId;
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
                                                window.location.href = BASE_URL + 'checkout/onepage/success';
                                                return;
                                            } else {
                                                return Promise.reject(new Error(response.message));
                                            }
                                        }).catch((error) => {
                                            return Promise.reject(error);
                                        });
                                },

                                onCancel: () => window.location.href = BASE_URL + 'worldpay/redirectresult/cancel',

                                onError: err => window.location.href = BASE_URL + 'worldpay/redirectresult/error'
                            }).render('#paypal-button-real');
                        }
                    }, 300);
                });
            }
        };
    });

