/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'underscore',
    'jquery',
    'Magento_Ui/js/modal/modal',
    'ko',
    'mage/storage',
    'Magento_Catalog/js/price-utils'
], function (Component,_, $,modal,ko,storage,priceUtils) {
    'use strict';

    return Component.extend({
        pm_element: '',
        sp_element: '',
        sp_popelement: '',
        pm_popelement: '',
        isLoadingShippingMethod : ko.observable(false),
        availableShippingMethods : ko.observableArray([]),
        allAvailableShippingMethods : ko.observableArray([]),
        availableTokens: ko.observableArray([]),
        successmsg: ko.observable(),
        msgColor: ko.observable('success'),
        popupOptions: {
            type: 'popup',
            responsive: true,
            clickableOverlay: false,
            buttons: []
        },
        priceFormat :{
            decimalSymbol: ".",
            groupLength: 3,
            groupSymbol: ",",
            integerRequired: false,
            pattern: "$%s",
            precision: 2,
            requiredPrecision: 2
        },
        shipmentApiUrl: 'rest/default/V1/worldpay/mine/estimate-recurring-shipping-methods',
        shipmentUpdateApiUrl: 'rest/default/V1/worldpay/mine/update-recurring-shipment',
        paymentTokenApiUrl: 'rest/default/V1/worldpay/mine/get-customer-payment-tokens',
        updatePaymentTokenApiUrl: 'rest/default/V1/worldpay/mine/update-recurring-payment-token',
        initialize: function () {
            this._super();
            this.pm_element = $(this.pm_elementId);
            this.sp_element = $(this.sp_elementId);
            this.sp_popelement = $(this.sp_popelement);
            this.pm_popelement = $(this.pm_popelement);

        },
        closeModalPopup: function(){
            var self = this;
            self.sp_popelement.modal("closeModal");
            window.location.reload();
        },
        closePaymentModalPopup: function(){
            var self = this;
            self.pm_popelement.modal("closeModal");
            window.location.reload();
        },
        openShippingPopup: function(){
            var self=this;
            var popupdetails = self.popupOptions;
            popupdetails.title = $.mage.__('Change Shipping Address');
            popupdetails.modalClass = 'change-shipping-address-modal confirm';
            popupdetails.buttons = [ {
                text: $.mage.__('Add New Address'),
                class: 'action-primary add-new-btn',
                click: function (event) {
                    window.location.href = '/customer/address/new/';
                }
            },{
                text: $.mage.__('Save'),
                class: 'action-primary',
                click: function (event) {
                    self.updateShipment();
                }
            },{
                text: $.mage.__('Close'),
                class: 'action-secondary',
                click: function (event) {
                    self.closeModalPopup();
                }
            }];
            self.sp_popelement.modal(popupdetails).modal('openModal').on('modalclosed',function(){
               window.location.reload();
            });
            $("#ship-address-dropdown").trigger('change');
        },
        getCartLabel: function(item){
            var self = this;
            var expiryCard = item.card_expiry_month+"/"+item.card_expiry_year;
            return item.card_number+", "+expiryCard;
        },
        getCartLabelMessage: function(item){
            var self = this;
            var expiredMsg = '';

            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            const currentMonth = currentDate.getMonth() + 1; // January is 0, so we add 1

            // Convert expiryDate and expiryYear to a Date object
            const expiry = new Date(`${item.card_expiry_year}-${item.card_expiry_month}-01`);

            if (expiry < currentDate) {
                expiredMsg = $.mage.__('Expired');
            } else if (expiry.getFullYear() === currentYear && expiry.getMonth() + 1 === currentMonth) {
                expiredMsg = $.mage.__('Expiring this month');
            }
            return expiredMsg;
        },
        updatePaymentToken: function(){
            var self = this;
            var selectedToken = $('input[name="available_recurring_tokens"]:checked').val();
            if(typeof selectedToken == 'undefined'){
                self.successmsg('Please select any card');
                self.msgColor('error');
                return false;
            }
            var obj ={
                'tokenId': selectedToken,
                'subscriptionId': self.current_subscription_id
            };
            $("body").trigger('processStart');
            self.successmsg("");
            storage.post(
                self.updatePaymentTokenApiUrl,
                JSON.stringify(obj)
            ).done(
                function (response) {
                    if(response.length){
                        response = JSON.parse(response);
                        self.successmsg(response.msg);
                        self.msgColor('success');
                    }
                    $("body").trigger('processStop');
                }
            ).fail(
                function (response) {
                    console.log('Fail',response);
                    $("body").trigger('processStop');
                }
            );
        },
        openPaymentPopup: function(){
            var self=this;
            var popupdetails = self.popupOptions;
            popupdetails.title = $.mage.__('Change Payment Method');
            popupdetails.modalClass = 'change-payment-method-modal confirm';
            popupdetails.buttons = [ {
                text: $.mage.__('Add New Card'),
                class: 'action-primary add-new-btn',
                click: function (event) {
                    window.location.href = '/worldpay/savedcard/addnewcard/';
                }
            },{
                text: $.mage.__('Save'),
                class: 'action-primary',
                click: function (event) {
                    self.updatePaymentToken();
                }
            },{
                text: $.mage.__('Close'),
                class: 'action-secondary',
                click: function (event) {
                    self.closePaymentModalPopup();
                }
            }];
            self.pm_popelement.modal(popupdetails).modal('openModal').on('modalclosed',function(){
                window.location.reload();
            });
            var obj = {
            }
            $("body").trigger('processStart');
             storage.post(
                    self.paymentTokenApiUrl,
                    JSON.stringify(obj)
                ).done(
                    function (response) {
                        response = JSON.parse(response);
                        var paymentMethodList = [];
                        if(response.tokens){
                            _.each(response.tokens,function(value){
                                paymentMethodList.push({
                                    "token_id": value.token_id,
                                    "token_code": value.token_code,
                                    "cardholder_name": value.cardholder_name,
                                    "card_number": value.card_number,
                                    "card_expiry_month": value.card_expiry_month,
                                    "card_expiry_year": value.card_expiry_year
                                });
                            });
                            self.availableTokens(paymentMethodList);

                        }
                        $("body").trigger('processStop');
                    }
                ).fail(
                    function (response) {
                        $("body").trigger('processStop');
                    }
                );

        },
        getShippingMethodObj: function(selectedMethod){
            var self = this;
            var shipObj = "";
            _.each(self.allAvailableShippingMethods(),function(value){
                var identifier = value.carrier+'_'+value.method;
                if(identifier == selectedMethod){
                    shipObj = value;
                }
            });
            return shipObj;
        },
        updateShipment: function(){
            var self=this;
            var addressId = $('#ship-address-dropdown').val();
            var orderIncrementId = self.order_incrementId;
            var selectedShippingMethod = $('input[name="shipping_method_recurring"]:checked').val();
            if(typeof selectedShippingMethod == 'undefined'){
                self.successmsg($.mage.__('Please select shipping method'));
                self.msgColor('error');
                return false;
            }
            var shippingMethodObj = self.getShippingMethodObj(selectedShippingMethod);
            var obj = {
                shipmentData: {
                        'orderIncrementId': orderIncrementId,
                        'addressId': addressId,
                        'shipping_method': shippingMethodObj,
                        'subscription_id': self.current_subscription_id
                   }
            }
            $(".ship-via").trigger('processStart');
            storage.post(
                self.shipmentUpdateApiUrl,
                JSON.stringify(obj)
            ).done(
                function (response) {
                    if(response.length){
                        response = JSON.parse(response);
                        self.successmsg(response.msg);
                        self.msgColor('success');
                    }
                    $(".ship-via").trigger('processStop');
                }
            ).fail(
                function (response) {
                    $(".ship-via").trigger('processStop');
                }
            );

        },
        fetchShippingMethodByAddress: function(){
            var self=this;
            var addressId = $('#ship-address-dropdown').val();
            var orderIncrementId = self.order_incrementId;
            var obj = {
                'orderIncrementId': orderIncrementId,
                'addressId': addressId
            }
            $(".ship-via").trigger('processStart');
            $('.shipping-information-content').hide();
             storage.post(
                    self.shipmentApiUrl,
                    JSON.stringify(obj)
                ).done(
                    function (response) {
                        response = JSON.parse(response);
                        var shippingMethodList = [];
                        if(response.length){
                            self.allAvailableShippingMethods(response);
                            _.each(response,function(value){
                                var titledesc = priceUtils.formatPrice(value.cost, self.priceFormat)+' '+value.carrier_title+' - '+ value.method_title;
                                shippingMethodList.push({
                                    "identifier": value.carrier+'_'+value.method,
                                    "label": titledesc,
                                    "detail": value.method_title,
                                    "amount": value.cost
                                });
                            });

                            self.availableShippingMethods(shippingMethodList);
                            $('.shipping-information-content').show();
                        }
                        $(".ship-via").trigger('processStop');
                    }
                ).fail(
                    function (response) {
                        console.log('Fail',response);
                        $(".ship-via").trigger('processStop');
                        $('.shipping-information-content').show();
                    }
                );
        }
    });
});
