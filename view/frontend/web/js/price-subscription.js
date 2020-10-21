/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*jshint browser:true jquery:true expr:true*/
define([
    "jquery"
], function ($) {
    "use strict";
    
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

    $.widget('mage.worldpayPriceSubscription', {
        options: {
            priceHolderSelector: '.price-box',
            qtySelector: '.box-tocart .field.qty'
        },

        _create: function () {
            this.element.find(this.options.planElement).on('change', $.proxy(function () {
                $('#subscription-error').css('display', 'none');
                this._reloadPrice();
            }, this));

            $(this.options.addPlanElement).on('change', $.proxy(function () {
                $('#subscription-error').css('display', 'none');
                if ($(this.options.addPlanElement).prop('checked')) {
                    this.element.show();
                } else {
                    this.element.hide();
                    $(this.options.startDateContainerElement).hide();
                    $(this.options.endDateContainerElement).hide();
                    $(this.options.qtySelector).show();

                    var selectedPlan = this.element.find(this.options.planElement + ':checked').first();
                    if(selectedPlan.length) {
                        selectedPlan.prop('checked', false);
                    }
                }
                this._reloadPrice();
            }, this));

            if(this.element.find(this.options.planElement + ':checked').first().length) {
                $(this.options.addPlanElement).prop('checked', true);
                $(this.options.instantPurchaseButton).hide();
                this.element.show();
            }

            this._reloadPrice();
        },

        /**
         * Reload product price with selected subscription plan price
         * @private
         */
        _reloadPrice: function () {
            var basePrice, finalPrice;

            var selectedPlan = this.element.find(this.options.planElement + ':checked').first();
            if (!selectedPlan.length) {
                finalPrice = this.options.config.defaults.finalPrice;
                basePrice = this.options.config.defaults.basePrice;
            } else {
                basePrice = this.options.config.plans[selectedPlan.val()].basePrice;
                finalPrice = this.options.config.plans[selectedPlan.val()].finalPrice;

                $(this.options.startDateContainerElement).show();
                $(this.options.endDateContainerElement).show();
                $(this.options.qtySelector).hide();
            }

            $(this.options.priceHolderSelector).trigger('replacePrice', {
                'prices': {
                    'finalPrice': {'amount': finalPrice},
                    'basePrice': {'amount': basePrice}
                }
            });
        }
    });
    
    $('#product-addtocart-button').on('click', function(){
        if ($('#worldpay-add-plan').length && $('#worldpay-add-plan').prop('checked') === true) {
            var result = false;
            $('input:radio').each(function () {
                if ($(this).prop('checked') === true){ 
                    result = true;
                }
            });
            if(result === true && $('#show_endDate').val() !== '1' && $('#subscription_date').val() !== ''){
                $('#subscription-error').html('');				
                return result;
            } 
            if(result === true && $('#show_endDate').val() === '1' && 
                    $('#subscription_end_date').val() !== ''){
                $('#subscription-error').html('');				
                return result;
            } else {
                if(result === false){
                    $('#subscription-error').html(getMyAccountExceptions('MCAM1'));
                } else if($('#subscription_date').val() === '') {
                    $('#subscription-error').html(getMyAccountExceptions('MCAM2'));
                } else if($('#subscription_end_date').val() === '') {
                    $('#subscription-error').html(getMyAccountExceptions('MCAM22'));
                }
                $('#subscription-error').css('display', 'block');                
                return false;
            }
            return result;
        } 
    });
    $('#product-updatecart-button').on('click', function(){
        if ($('#worldpay-add-plan').length && $('#worldpay-add-plan').prop('checked') === true) {
            var result = false;
            $('input:radio').each(function () {
                if ($(this).prop('checked') === true){ 
                    result = true;
                }
            });
            if(result === true && $('#show_endDate').val() !== '1'&& $('#subscription_date').val() !== ''){
                $('#subscription-error').html('');				
                return true;
            } 
            if(result === true && $('#show_endDate').val() === '1' && 
                    $('#subscription_end_date').val() !== ''){
                $('#subscription-error').html('');				
                return result;
            }else {
                event.stopImmediatePropagation();
                event.preventDefault();
                event.stopPropagation();
                if(result === false){
                    $('#subscription-error').html(getMyAccountExceptions('MCAM1'));
                } else if($('#subscription_date').val() === '') {
                    $('#subscription-error').html(getMyAccountExceptions('MCAM2'));
                } else if($('#subscription_end_date').val() === '') {
                    $('#subscription-error').html(getMyAccountExceptions('MCAM22'));
                }
                $('#subscription-error').css('display', 'block');                
                return false;
            }
            return result;
        } 
    });

    return $.mage.worldpayPriceSubscription;
});
