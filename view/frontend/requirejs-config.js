/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
var config = {
    map: {
        '*': {
            worldpay: 'https://payments.worldpay.com/resources/cse/js/worldpay-cse-1.0.2.min.js',
            googlePay: 'https://pay.google.com/gp/p/js/pay.js',
            hmacSha256: 'https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/hmac-sha256.js',
            encBase64: 'https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/components/enc-base64-min.js',
            worldpayPriceSubscription: 'Sapient_Worldpay/js/price-subscription',
            "Magento_Checkout/js/sidebar": "Sapient_Worldpay/js/sidebar",
            "Magento_Checkout/js/proceed-to-checkout": "Sapient_Worldpay/js/proceed-to-checkout",
            "Magento_Checkout/template/minicart/content.html": "Sapient_Worldpay/template/minicart/content.html",
            "Magento_Checkout/template/payment.html": "Sapient_Worldpay/template/payment.html",
            "Magento_Checkout/template/payment-methods/list.html": "Sapient_Worldpay/template/payment-methods/list.html",
            "Magento_Checkout/js/view/minicart": "Sapient_Worldpay/js/minicart"
           // "Magento_Checkout/js/view/billing-address": "Sapient_Worldpay/js/view/billing-address",
           // "Magento_Checkout/js/view/billing-address/list": "Magento_Checkout/js/view/billing-address/list"
        }
    },
    mixins: {
        "Magento_Checkout/js/view/billing-address": {
            "Sapient_Worldpay/js/view/billing-address": true
        }
    },
    config: {
        mixins: {
            'Magento_Catalog/js/price-box': {
                'Sapient_Worldpay/js/price-box-mixin': true
            },
            'Magento_Customer/js/model/authentication-popup': {
                'Sapient_Worldpay/js/authentication-popup-mixin': true
            }
        }
    }
};
