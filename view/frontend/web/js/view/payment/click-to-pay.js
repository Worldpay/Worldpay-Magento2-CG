define([
    'uiComponent',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Sapient_Worldpay/js/model/checkout-utils',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Customer/js/model/customer'
], function (Component, $, quote, checkoutUtils, stepNavigator, customer) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Sapient_Worldpay/payment/click-to-pay'
        },

        initialize: function () {
            this._super();

            if(!this.isActive()) {
                return this;
            }

            this.initialized = false;

            var self = this;

            stepNavigator.steps.subscribe(function (steps) {
                self.subscribeToPaymentStep(steps);
            });

            this.subscribeToPaymentStep(stepNavigator.steps());

            return this;
        },

        subscribeToPaymentStep: function (steps) {
            if (this.paymentStepSubscribed) {
                return;
            }

            var self = this;
            var paymentStep = steps.filter(function (step) {
                return step.code === 'payment';
            })[0];

            if (paymentStep) {
                this.paymentStepSubscribed = true;

                if (paymentStep.isVisible()) {
                    this.onPaymentStepVisible();
                }

                paymentStep.isVisible.subscribe(function (isVisible) {
                    if (isVisible) {
                        self.onPaymentStepVisible();
                    }
                });
            }
        },

        onPaymentStepVisible: function () {
            if (this.initialized) {
                return;
            }
            this.initialized = true;
            this.requestCaptureContext();
        },

        requestCaptureContext: function () {
            var backendEndpoint = BASE_URL +  'rest/V1/worldpay/clicktopay/capturecontext';

            fetch(backendEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(function (decoded) {
                if (typeof decoded === 'string') {
                    decoded = JSON.parse(decoded);
                }

                if (decoded.error) {
                    console.error('[ClickToPay] Backend error:', decoded.error);
                    return;
                }

                this.captureContextJwt = decoded.jwt;
                this.loadClickToPayScript(decoded.clientLibrary, decoded.clientLibraryIntegrity);
            }.bind(this))
            .catch(function (error) {
                console.error('[ClickToPay] Backend request failed:', error);
            });

            return this;
        },

        loadClickToPayScript: function (clientLibrary, clientLibraryIntegrity) {
            if (!clientLibrary) {
                console.warn('[ClickToPay] No clientLibrary URL found in JWT payload.');
                return;
            }

            var existing = document.querySelector('script[data-click-to-pay-script="cybersource"]');
            if (existing) {
                return;
            }

            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = clientLibrary;
            script.async = false;
            script.setAttribute('data-click-to-pay-script', 'cybersource');
            if (clientLibraryIntegrity) {
                script.setAttribute('integrity', clientLibraryIntegrity);
                script.setAttribute('crossorigin', 'anonymous');
            }

            if (window.define && window.define.amd) {
                var originalAmd = window.define.amd;
                window.define.amd = false;
            }

            script.onload = function () {
                if (originalAmd !== undefined) {
                    window.define.amd = originalAmd;
                }
                this.showClickToPay();
            }.bind(this);

            script.onerror = function (e) {
                if (originalAmd !== undefined) {
                    window.define.amd = originalAmd;
                }
                console.error('[ClickToPay] Failed to load script:', clientLibrary);
            };

            document.head.appendChild(script);
        },

        showClickToPay: function () {
            if (!this.captureContextJwt) {
                return;
            }

            if (!window.Accept) {
                return;
            }

            var showArgs = {
                containers: {
                    paymentSelection: "#payment-clicktopay-container"
                }
            };

            var attempt = 0;
            var maxAttempts = 15;
            var checkInterval = 500;

            var cc = this.captureContextJwt;

            function checkContainer() {
                var container = document.querySelector('#payment-clicktopay-container');
                if (!container) {
                    attempt++;
                    if (attempt >= maxAttempts) {
                        return;
                    }
                    setTimeout(checkContainer, checkInterval);
                } else {
                    Accept(cc)
                        .then(function (accept) {
                            return accept.unifiedPayments();
                        })
                        .then(function (up) {
                            return up.show(showArgs);
                        })
                        .then(async function (transientToken) {
                            var checkoutData = checkoutUtils.createCheckoutData(quote, customer, 'worldpay_apm', 'CLICKTOPAY-SSL');
                            checkoutData.paymentDetails.additional_data.transientToken = transientToken;
                            var data = await checkoutUtils.setPaymentInformationAndPlaceOrder(checkoutData);

                            if (data.error) {
                                window.location.href = BASE_URL + 'worldpay/redirectresult/error';
                            }
                        }).catch(function (error) {
                            window.location.href = BASE_URL + 'worldpay/redirectresult/error';
                        });
                }
            }

            checkContainer();
        },

        isActive: function () {
           if(!window.checkoutConfig.payment.ccform.clickToPayEnabled){
                return false;
            }
            if(window.checkoutConfig.payment.ccform.isSubscribed){
                return false;
            }
            if(window.checkoutConfig.payment.ccform.isProductOnDemand){
                return false;
            }
            return true;
        }
    });
});
