define([
    'uiComponent',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Sapient_Worldpay/js/model/checkout-utils',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Customer/js/model/customer',
    'mage/url'
], function (Component, $, quote, checkoutUtils, stepNavigator, customer, url) {
    'use strict';

    var jwtUrl = url.build('worldpay/hostedpaymentpage/jwt');
    var DDC_TIMEOUT = 10000;

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

        validateTransientToken: function (transientToken) {
            var backendEndpoint = BASE_URL + 'rest/V1/worldpay/clicktopay/validatetoken';

            return fetch(backendEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ transientToken: transientToken })
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('[ClickToPay] Token validation failed');
                }
                return response.json();
            })
            .then(function (data) {
                if (typeof data === 'string') {
                    data = JSON.parse(data);
                }
                return data;
            });
        },

        createDdcIframe: function (cardBin) {
            $('#jwt_frm').remove();

            var encryptedBin = btoa(cardBin);
            $('body').append('<iframe src="' + jwtUrl + '?instrument=' + encryptedBin + '" name="jwt_frm" id="jwt_frm" style="display: none"></iframe>');
        },

        registerDdcListener: function () {
            var jwtEventUrl = window.checkoutConfig.payment.ccform.jwtEventUrl;

            return new Promise(function (resolve) {
                var timeoutId;

                var handler = function (event) {
                    if (typeof event.data === 'undefined' || !event.data) {
                        return;
                    }

                    if (jwtEventUrl && event.origin !== jwtEventUrl) {
                        return;
                    }

                    var data;
                    try {
                        data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
                    } catch (e) {
                        return;
                    }

                    var sessionId = null;

                    if (data?.Payload?.ActionCode === "SUCCESS") {
                        sessionId = data.Payload.SessionId;
                    }
                    else if (data?.Status && data?.SessionId) {
                        sessionId = data.SessionId;
                    }
                    else if (data?.MessageType === 'profile.completed' && data?.Status === true) {
                        sessionId = data.SessionId;
                    }
                    else {
                        return;
                    }

                    window.removeEventListener("message", handler);
                    clearTimeout(timeoutId);

                    resolve(sessionId);
                };

                window.addEventListener("message", handler);

                timeoutId = setTimeout(function () {
                    window.removeEventListener("message", handler);
                    resolve(null);
                }, DDC_TIMEOUT);
            });
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

            script.onerror = function () {
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
            var self = this;

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
                      var validationResult;
                      try {
                        validationResult = await self.validateTransientToken(transientToken);
                      } catch (error) {
                        console.error('[ClickToPay] Token validation request failed:', error);
                        window.location.href = BASE_URL + 'worldpay/redirectresult/error';
                        return;
                      }

                      if (validationResult.error) {
                        console.error('[ClickToPay] Token validation failed:', validationResult.error);
                        window.location.href = BASE_URL + 'worldpay/redirectresult/error';
                        return;
                      }

                      var ddcSessionId = null;

                      if (validationResult.tokenizedCardBin) {
                        var ddcPromise = self.registerDdcListener();
                        self.createDdcIframe(validationResult.tokenizedCardBin);
                        ddcSessionId = await ddcPromise;
                      }

                      var checkoutData = checkoutUtils.createCheckoutData(quote, customer, 'worldpay_apm', 'CLICKTOPAY-SSL', ddcSessionId);
                      checkoutData.paymentDetails.additional_data.transientToken = transientToken;

                      if (validationResult.tokenizedCardBin) {
                        checkoutData.paymentDetails.additional_data.tokenizedCardBin = validationResult.tokenizedCardBin;
                      }

                      if (ddcSessionId) {
                        checkoutData.paymentDetails.additional_data.dfReferenceId = ddcSessionId;
                      }

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
