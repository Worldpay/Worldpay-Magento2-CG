/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        $,
        Component,
        rendererList
    ) {
        'use strict';
        var CCcomponent = 'Sapient_Worldpay/js/view/payment/method-renderer/cc-method';
        var APMcomponent = 'Sapient_Worldpay/js/view/payment/method-renderer/apm-method';

        var methods = [
            {type: 'worldpay_cc', component: CCcomponent},
            {type: 'worldpay_apm', component: APMcomponent}
        ];

         $.each(methods, function (k, method) {
            rendererList.push(method);
        });

        return Component.extend({});
    }
);
