/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/model/messageList'
],function ($, customerData, messageList) {
    'use strict';
    
    function getMyAccountExceptions (exceptioncode){
                var data=window.MyAccountExceptions;
                var gendata=JSON.parse(data);
                for (var key in gendata) {
                    if (gendata.hasOwnProperty(key)) {  
                        var cxData=gendata[key];
                    if(cxData['exception_code'].includes(exceptioncode)){
                        return cxData['exception_module_messages']?cxData['exception_module_messages']:cxData['exception_messages'];
                    }
                    }
                }
               
            }
    var mixin = {
        showModal: function() {
            if(this.shouldDisplayMessage()) {
                messageList.addSuccessMessage({'message': $.mage.__(getMyAccountExceptions('MCAM0'))});
            }

            $(this.modalWindow).modal('openModal');
        },

        shouldDisplayMessage: function () {
            var i, o;
            var cartItems = customerData.get('cart')().items;

            var options = [];

            for(i = 0; i < cartItems.length; i++) {
                options = cartItems[i].options;

                for(o = 0; o < options.length; o++) {
                    var plan_id = cartItems[i].options[o].plan_id;

                    if(plan_id) {
                        return true;
                    }
                }
            }

            return false;
        }
    };

    return function (target) {
        return $.extend(target, mixin);
    };
});
