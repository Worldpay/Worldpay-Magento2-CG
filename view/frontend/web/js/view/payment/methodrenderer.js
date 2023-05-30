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
        'Magento_Checkout/js/model/payment/renderer-list',
        'uiLayout',
        'uiRegistry'
    ],
    function (
        $,
        Component,
        rendererList,
        layout, 
        registry
    ) {
        'use strict';
        var CCcomponent = 'Sapient_Worldpay/js/view/payment/method-renderer/cc-method';
        var APMcomponent = 'Sapient_Worldpay/js/view/payment/method-renderer/apm-method';
        var Walletscomponent = 'Sapient_Worldpay/js/view/payment/method-renderer/wallets-method';

        var methods = [
            {type: 'worldpay_cc', component: CCcomponent},
            {type: 'worldpay_apm', component: APMcomponent},
            {type: 'worldpay_wallets', component: Walletscomponent}
        ];

        var wpGroupName = 'worldpayGroup';

        layout([{
            name: wpGroupName,
            component: 'Magento_Checkout/js/model/payment/method-group',
            alias: 'worldpay',
            sortOrder: 1
        }]);

        registry.get(wpGroupName, function (wpGroup) {
            $.each(methods, function (k, method) {
                rendererList.push({
                    type: method.type,
                    component: method.component,
                    group: wpGroup,
                })
            });
        });

        

        return Component.extend({});
    }
);
