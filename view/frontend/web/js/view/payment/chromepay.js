define([
    'jquery',
    'mage/storage'
], function ($, storage) {
    'use strict';

    return function (config) {
        var carrierCode;
        var cardType;
        var totalAmount;
        var orderCode;
        var cseData = {};
        var sessionId = config.sessionId || '';
        var baseUrl = config.baseUrl;
        var storeCode = config.storeCode;
        var currency = config.currency;
        var reqShipping = !!config.reqShipping;

        window.chromepayButtonName = config.chromepayButtonName || '';
        window.chromepayEnabled = !!config.chromepayEnabled;
        window.chromePaymentMode = config.chromePaymentMode || '';
        window.subscriptionStatus = !!config.subscriptionStatus;
        window.GeneralExceptions = config.generalExceptions || '';
        window.MyAccountExceptions = config.myAccountExceptions || '';
        window.CreditCardException = config.creditCardException || '';
        window.isDynamic3DS2Enabled = !!config.isDynamic3DS2Enabled;
        window.is3DsEnabled = !!config.is3DsEnabled;
        window.jwtEventUrl = config.jwtEventUrl || '';
        window.isCheckoutCartPage = !!config.isCheckoutCartPage;
        window.currency = config.currency || '';

        document.cookie = 'envMode=' + encodeURIComponent(
            config.envMode === 'Test Mode' ? 'Test Mode' : 'Live Mode'
        ) + '; path=/';

        registerServiceWorker();

        function registerServiceWorker() {
            if ('serviceWorker' in navigator && config.serviceWorkerUrl) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register(config.serviceWorkerUrl, {
                        scope: config.serviceWorkerScope
                    }).then(function () {
                        console.log('Service worker registered successfully');
                    }).catch(function (e) {
                        console.error('Error during service worker registration:', e.message);
                    });
                });
            }
        }

        function getChromeVersion() {
            var raw = navigator.userAgent.match(/Chrom(e|ium)\/([0-9]+)\./);
            return raw ? parseInt(raw[2], 10) : false;
        }

        function ajaxJson(method, url, payload) {
            return $.ajax({
                url: url,
                type: method,
                data: payload ? JSON.stringify(payload) : null,
                contentType: 'application/json',
                dataType: 'json'
            });
        }

        function getIsCustomerLoggedIn() {
            return !!(window.checkoutConfig && window.checkoutConfig.isCustomerLoggedIn);
        }

        function getGuestMaskedQuoteId() {
            return window.checkoutConfig && window.checkoutConfig.quoteData
                ? window.checkoutConfig.quoteData.entity_id
                : null;
        }

        function getTotalsUrl() {
            if (getIsCustomerLoggedIn()) {
                return baseUrl + 'rest/' + storeCode + '/V1/carts/mine/totals-information';
            }

            return baseUrl + 'rest/' + storeCode + '/V1/guest-carts/' +
                getGuestMaskedQuoteId() + '/totals-information';
        }

        function getEstimateShippingUrl() {
            if (getIsCustomerLoggedIn()) {
                return baseUrl + 'rest/' + storeCode + '/V1/carts/mine/estimate-shipping-methods';
            }

            return baseUrl + 'rest/' + storeCode + '/V1/guest-carts/' +
                getGuestMaskedQuoteId() + '/estimate-shipping-methods';
        }

        function getShippingInformationUrl() {
            if (getIsCustomerLoggedIn()) {
                return baseUrl + 'rest/' + storeCode + '/V1/carts/mine/shipping-information';
            }

            return baseUrl + 'rest/' + storeCode + '/V1/guest-carts/' +
                getGuestMaskedQuoteId() + '/shipping-information';
        }

        function getOrderUrl() {
            if (getIsCustomerLoggedIn()) {
                return baseUrl + 'rest/' + storeCode + '/V1/carts/mine/payment-information';
            }

            return baseUrl + 'rest/' + storeCode + '/V1/guest-carts/' +
                getGuestMaskedQuoteId() + '/order';
        }

        function updateTotals(carrierCodeArg, shippingAddress, shippingOption) {
            var req = {
                addressInformation: {
                    address: {
                        countryId: shippingAddress.country,
                        region: shippingAddress.region,
                        postcode: shippingAddress.postalCode
                    },
                    shipping_method_code: shippingOption,
                    shipping_carrier_code: carrierCodeArg
                }
            };

            return ajaxJson('POST', getTotalsUrl(), req);
        }

        function updateDetailsAddress(details, request, resolve, reject) {
            var req = {
                address: {
                    country_id: request.country
                }
            };

            ajaxJson('POST', getEstimateShippingUrl(), req)
                .done(function (shippingMethods) {
                    var shippingOptionsNew = [];
                    var i;

                    for (i = 0; i < shippingMethods.length; i++) {
                        shippingOptionsNew.push({
                            id: shippingMethods[i].method_code,
                            label: shippingMethods[i].carrier_title,
                            carrier: shippingMethods[i].carrier_code,
                            amount: {
                                currency: currency,
                                value: shippingMethods[i].amount
                            },
                            selected: false
                        });
                    }

                    details.shippingOptions = shippingOptionsNew;
                    resolve(details);
                })
                .fail(function (response) {
                    reject(response);
                });
        }

        function updateDetails(details, shippingOption, shippingAddress, resolve, reject) {
            var k;
            var selectedOptIndex;
            var selectedShippingOption;
            var newShippingAmount;
            var newTotal;
            var productCount = Number(config.productCount);
            var shippingPosition = Number(productCount + 1);

            for (k = 0; k < details.shippingOptions.length; k++) {
                details.shippingOptions[k].selected = false;
                if (details.shippingOptions[k].id === shippingOption) {
                    selectedOptIndex = k;
                }
            }

            carrierCode = details.shippingOptions[selectedOptIndex].carrier;
            newShippingAmount = Number(details.shippingOptions[selectedOptIndex].amount.value);
            newTotal = Number(config.subtotal) + newShippingAmount;
            selectedShippingOption = details.shippingOptions[selectedOptIndex];

            details.total.amount.value = newTotal;
            totalAmount = newTotal;
            details.displayItems[shippingPosition].amount.value = newShippingAmount;
            selectedShippingOption.selected = true;

            updateTotals(carrierCode, shippingAddress, shippingOption)
                .done(function (totals) {
                    if (totals) {
                        var taxPosition = Number(productCount + 2);
                        details.displayItems[taxPosition].amount.value = totals.base_tax_amount;
                        details.total.amount.value = totals.base_grand_total;
                    }
                    resolve(details);
                })
                .fail(function (response) {
                    reject(response);
                });
        }

        function createJwt(cardNumber) {
            var encryptedBin = btoa(cardNumber);
            var iframe = document.createElement('iframe');

            iframe.src = baseUrl + 'worldpay/hostedpaymentpage/jwt?instrument=' + encryptedBin;
            iframe.name = 'jwt_frm';
            iframe.id = 'jwt_frm';
            iframe.style.display = 'none';

            document.body.appendChild(iframe);
        }

        function getCsePublicKey() {
            return window.checkoutConfig.payment.ccform.csePublicKey;
        }

        function isClientSideEncryptionEnabled() {
            if (getCsePublicKey()) {
                return window.checkoutConfig.payment.ccform.cseEnabled;
            }

            return false;
        }

        function getRegionById(countryId, regionCode) {
            return ajaxJson('GET', baseUrl + 'rest/V1/directory/countries/' + countryId)
                .then(function (regions) {
                    var i;
                    if (typeof regions.available_regions !== 'undefined' && regions.available_regions.length > 0) {
                        for (i = 0; i < regions.available_regions.length; i++) {
                            if (regions.available_regions[i].code === regionCode) {
                                return regions.available_regions[i].id;
                            }
                        }
                    }
                    return 0;
                });
        }

        function saveShippingInformation(customerDetails) {
            return ajaxJson('POST', getShippingInformationUrl(), customerDetails);
        }

        function placePayment(paymentDetails) {
            if (getIsCustomerLoggedIn()) {
                return ajaxJson('POST', getOrderUrl(), paymentDetails);
            }

            return ajaxJson('PUT', getOrderUrl(), paymentDetails);
        }

        function createOrder(instrumentResponse) {
            if (typeof instrumentResponse.details.billingAddress === 'undefined' ||
                instrumentResponse.details.billingAddress === null) {
                instrumentResponse.details.billingAddress = instrumentResponse.shippingAddress;
            }

            return getRegionById(
                instrumentResponse.details.billingAddress.country,
                instrumentResponse.details.billingAddress.region
            ).then(function (regionId) {
                var fullName = instrumentResponse.details.billingAddress.recipient || '';
                var strArray = fullName.split(' ');
                var firstName = strArray[0] || '';
                var lastName = strArray[1] || firstName;
                var customerDetails;
                var cardNumber;
                var cvc;
                var cardholderName;
                var expiryMonth;
                var expiryYear;
                var encryptedCseData;
                var additionalData;
                var paymentDetails;

                customerDetails = {
                    addressInformation: {
                        shipping_address: {
                            region: '',
                            region_id: regionId,
                            region_code: '',
                            country_id: '',
                            street: [''],
                            postcode: '',
                            city: '',
                            firstname: '',
                            lastname: '',
                            email: '',
                            telephone: ''
                        },
                        billing_address: {
                            email: instrumentResponse.payerEmail,
                            region: instrumentResponse.details.billingAddress.region,
                            region_id: regionId,
                            region_code: instrumentResponse.details.billingAddress.region,
                            country_id: instrumentResponse.details.billingAddress.country,
                            street: instrumentResponse.details.billingAddress.addressLine,
                            postcode: instrumentResponse.details.billingAddress.postalCode,
                            city: instrumentResponse.details.billingAddress.city,
                            telephone: instrumentResponse.details.billingAddress.phone,
                            firstname: firstName,
                            lastname: lastName
                        },
                        shipping_carrier_code: carrierCode,
                        shipping_method_code: instrumentResponse.shippingOption
                    }
                };

                if (instrumentResponse.shippingAddress) {
                    customerDetails.addressInformation.shipping_address.region = instrumentResponse.shippingAddress.region;
                    customerDetails.addressInformation.shipping_address.region_id = 0;
                    customerDetails.addressInformation.shipping_address.region_code = instrumentResponse.shippingAddress.region;
                    customerDetails.addressInformation.shipping_address.country_id = instrumentResponse.shippingAddress.country;
                    customerDetails.addressInformation.shipping_address.street = instrumentResponse.shippingAddress.addressLine;
                    customerDetails.addressInformation.shipping_address.postcode = instrumentResponse.shippingAddress.postalCode;
                    customerDetails.addressInformation.shipping_address.city = instrumentResponse.shippingAddress.city;
                    customerDetails.addressInformation.shipping_address.telephone = instrumentResponse.shippingAddress.phone;
                    customerDetails.addressInformation.shipping_address.firstname = firstName;
                    customerDetails.addressInformation.shipping_address.lastname = lastName;
                }

                cardNumber = instrumentResponse.details.cardNumber;
                cardType = getCardType(cardNumber);

                cardNumber = instrumentResponse.details.cardNumber;
                cvc = instrumentResponse.details.cardSecurityCode;
                cardholderName = instrumentResponse.details.cardholderName;
                expiryMonth = instrumentResponse.details.expiryMonth;
                expiryYear = instrumentResponse.details.expiryYear;
                encryptedCseData = cseData.length > 0 ? cseData : null;

                if (encryptedCseData) {
                    additionalData = {
                        cc_cid: '',
                        cc_type: '',
                        cc_exp_year: '',
                        cc_exp_month: '',
                        cc_number: '',
                        cc_name: '',
                        save_my_card: false,
                        cse_enabled: isClientSideEncryptionEnabled(),
                        encryptedData: encryptedCseData,
                        tokenCode: null,
                        isSavedCardPayment: false,
                        isChromePay: '1',
                        dfReferenceId: sessionId,
                        browser_screenheight: window.screen.height,
                        browser_screenwidth: window.screen.width,
                        browser_colordepth: window.screen.colorDepth
                    };
                } else {
                    additionalData = {
                        cc_cid: cvc,
                        cc_type: cardType,
                        cc_exp_year: expiryYear,
                        cc_exp_month: expiryMonth,
                        cc_number: cardNumber,
                        cc_name: cardholderName,
                        save_my_card: false,
                        cse_enabled: isClientSideEncryptionEnabled(),
                        encryptedData: encryptedCseData,
                        tokenCode: null,
                        isSavedCardPayment: false,
                        isChromePay: '1',
                        dfReferenceId: sessionId,
                        browser_screenheight: window.screen.height,
                        browser_screenwidth: window.screen.width,
                        browser_colordepth: window.screen.colorDepth
                    };
                }

                paymentDetails = {
                    paymentMethod: {
                        method: 'worldpay_cc',
                        additional_data: additionalData
                    },
                    billing_address: {
                        email: instrumentResponse.payerEmail,
                        region: instrumentResponse.details.billingAddress.city,
                        region_id: regionId,
                        region_code: instrumentResponse.details.billingAddress.region,
                        country_id: instrumentResponse.details.billingAddress.country,
                        street: instrumentResponse.details.billingAddress.addressLine,
                        postcode: instrumentResponse.details.billingAddress.postalCode,
                        city: instrumentResponse.details.billingAddress.city,
                        telephone: instrumentResponse.details.billingAddress.phone,
                        firstname: firstName,
                        lastname: lastName
                    }
                };

                if (instrumentResponse.shippingAddress) {
                    return saveShippingInformation(customerDetails).then(function () {
                        return placePayment(paymentDetails);
                    });
                }

                return placePayment(paymentDetails);
            });
        }

        function placeOrder(instrumentResponse) {
            createOrder(instrumentResponse).done(function (response) {
                orderCode = response;

                if (isNaN(orderCode)) {
                    if (response.hasOwnProperty('message') && response.message.indexOf('3DS2') !== -1) {
                        window.location.href = baseUrl + 'checkout/cart?error=true&message=error';
                        return;
                    }

                    window.location.href = baseUrl + 'checkout/cart?error=true';
                    return;
                }

                window.location.href = baseUrl + 'worldpay/threedsecure/auth';
            }).fail(function () {
                window.location.href = baseUrl + 'checkout/cart?error=true';
            });
        }

        function handleJwtResponse(instrumentResponse) {
            var messageHandler = function (event) {
                var envUrl = window.jwtEventUrl || '';
                var data;
                var receivedSessionId;

                if (event.origin !== envUrl) {
                    return;
                }

                try {
                    data = JSON.parse(event.data);
                } catch (e) {
                    return;
                }

                if (data !== undefined) {
                    if (data.Payload && data.Payload.ActionCode === 'SUCCESS') {
                        receivedSessionId = data.Payload.SessionId;
                    } else if (data.Status) {
                        receivedSessionId = data.SessionId;
                    }

                    if (receivedSessionId) {
                        sessionId = receivedSessionId;
                        window.removeEventListener('message', messageHandler, false);
                        placeOrder(instrumentResponse);
                    }
                }
            };

            return messageHandler;
        }

        function onBuyClicked(request) {
            if (!getIsCustomerLoggedIn() && config.isDownloadable) {
                alert('Please log in to continue');
                throw new Error('Please log in to continue');
            }

            request.show().then(function (instrumentResponse) {
                if (isClientSideEncryptionEnabled()) {
                    require([
                        'https://payments.worldpay.com/resources/cse/js/worldpay-cse-1.0.2.min.js'
                    ], function (worldpay) {
                        worldpay.setPublicKey(getCsePublicKey());

                        cseData = {
                            cardNumber: instrumentResponse.details.cardNumber,
                            cvc: instrumentResponse.details.cardSecurityCode,
                            cardHolderName: instrumentResponse.details.cardholderName,
                            expiryMonth: instrumentResponse.details.expiryMonth,
                            expiryYear: instrumentResponse.details.expiryYear
                        };

                        cseData = worldpay.encrypt(cseData);

                        if (window.isDynamic3DS2Enabled) {
                            createJwt(instrumentResponse.details.cardNumber.substring(0, 6));
                            sessionId = '';
                            window.addEventListener('message', handleJwtResponse(instrumentResponse), false);
                        } else {
                            placeOrder(instrumentResponse);
                        }
                    });
                } else if (window.isDynamic3DS2Enabled) {
                    createJwt(instrumentResponse.details.cardNumber.substring(0, 6));
                    sessionId = config.sessionId || '';
                    window.addEventListener('message', handleJwtResponse(instrumentResponse), false);
                } else {
                    placeOrder(instrumentResponse);
                }
            }).catch(function (err) {
                throw err;
            });
        }

        function initPaymentRequest() {
            var supportedMethods = 'basic-card';
            var supportedInstruments;
            var details;
            var options;
            var request;

            if (getChromeVersion() > 99) {
                supportedMethods = baseUrl + 'worldpay/payment/pay';
            }

            supportedInstruments = [{
                supportedMethods: supportedMethods,
                data: {
                    supportedNetworks: ['amex', 'mastercard', 'visa'],
                    supportedTypes: ['credit']
                }
            }];

            details = {
                displayItems: config.displayItems.concat([
                    {
                        label: 'Subtotal',
                        amount: {
                            currency: currency,
                            value: config.subtotal
                        }
                    },
                    {
                        label: 'Shipping',
                        amount: {
                            currency: currency,
                            value: config.shippingRate
                        },
                        pending: true
                    },
                    {
                        label: 'Tax',
                        amount: {
                            currency: currency,
                            value: config.taxRate
                        },
                        pending: true
                    },
                    {
                        label: 'Discount',
                        amount: {
                            currency: currency,
                            value: config.discountRate
                        },
                        pending: true
                    }
                ]),
                total: {
                    label: 'Total',
                    amount: {
                        currency: currency,
                        value: config.total
                    }
                }
            };

            options = {
                requestShipping: reqShipping,
                requestPayerEmail: true,
                requestPayerPhone: true,
                requestPayerName: true
            };

            request = new PaymentRequest(supportedInstruments, details, options);

            request.addEventListener('shippingaddresschange', function (evt) {
                evt.updateWith(new Promise(function (resolve, reject) {
                    updateDetailsAddress(details, request.shippingAddress, resolve, reject);
                }));
            });

            request.addEventListener('shippingoptionchange', function (evt) {
                evt.updateWith(new Promise(function (resolve, reject) {
                    updateDetails(details, request.shippingOption, request.shippingAddress, resolve, reject);
                }));
            });

            return request;
        }

        function getCardType(cardNumber) {
           const cardTypes = [
                { regex: /^(401178|401179|431274|438935|451416|457393|457631|457632|504175|627780|636297|636368|655000|655001|651652|651653|651654|650485|650486|650487|650488|506699|5067[0-6][0-9]|50677[0-8]|509\d{3})\d{10}$/, code: 'ELO-SSL' },
                { regex: /^(5019)/, code: 'DANKORT-SSL' },
                { regex: /^4[0-9]{0,20}$/, code: 'VISA-SSL' },
                { regex: /^(?:5[1-5][0-9]{0,2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{0,2}|27[01][0-9]|2720)[0-9]{0,12}$/, code: 'ECMC-SSL' },
                { regex: /^3$|^3[47][0-9]{0,13}$/, code: 'AMEX-SSL' },
                { regex: /^6[05]$|^601[1]?$|^65[0-9][0-9]?$|^6(?:011|5[0-9]{2})[0-9]{0,12}$/, code: 'DISCOVER-SSL' },
                { regex: /^35(2[89]|[3-8][0-9])/, code: 'JCB-SSL' },
                { regex: /^36/, code: 'DINERS-SSL' },
                { regex: /^(5018|5020|5038|6304|679|6759|676[1-3])/, code: 'MAESTRO-SSL' },
                { regex: /^62[0-9]{0,14}$|^645[0-9]{0,13}$|^65[0-9]{0,14}$/, code: 'CHINAUNIONPAY-SSL' }
            ];

            for (const card of cardTypes) {
                if (cardNumber.match(card.regex)) {
                    return card.code;
                }
            }

            return '';
        }

        window.initPaymentRequest = initPaymentRequest;
        window.onBuyClicked = onBuyClicked;

        var userAgent = window.navigator.userAgent || '';
        var isChrome = /chrome/i.test(userAgent);
        var isEdge = /edg/i.test(userAgent);

        window.chromepayAvailable = isChrome && !isEdge;

        var buttonObj = document.getElementById('checkout-now-bottom');

        if (buttonObj) {
            if (
                window.chromepayEnabled && window.chromePaymentMode === 'direct'
                && !window.subscriptionStatus && window.chromepayAvailable
            ) {
                buttonObj.style.visibility = 'visible';
                buttonObj.disabled = false;
                buttonObj.innerHTML = '<span>' + (window.chromepayButtonName || 'Checkout Now') + '</span>';
                buttonObj.addEventListener('click', function (event) {
                    event.preventDefault();
                    var paymentRequest = initPaymentRequest();
                    onBuyClicked(paymentRequest);
                });
            } else {
                buttonObj.style.visibility = 'hidden';
                buttonObj.disabled = true;
                if (buttonObj.parentNode) {
                    buttonObj.parentNode.style.height = 0;
                    buttonObj.parentNode.style.margin = 0;
                }
            }
        }
    };
});
