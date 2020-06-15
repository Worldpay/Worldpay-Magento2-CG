/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*jshint browser:true jquery:true expr:true*/
define([
    "jquery"
], function ($) {
    "use strict";

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
            if(result === true && $('#subscription_date').val() !== ''){
                $('#subscription-error').html('');				
                return true;
            } else {
                if(result === false){
                    $('#subscription-error').html('Choose any of the plan!');
                } else if($('#subscription_date').val() === '') {
                    $('#subscription-error').html('Choose plan start date!');
                }
                $('#subscription-error').css('display', 'block');                
                return false;
            }
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
            if(result === true && $('#subscription_date').val() !== ''){
                $('#subscription-error').html('');				
                return true;
            } else {
                event.stopImmediatePropagation();
                event.preventDefault();
                event.stopPropagation();
                if(result === false){
                    $('#subscription-error').html('Choose any of the plan!');
                } else if($('#subscription_date').val() === '') {
                    $('#subscription-error').html('Choose plan start date!');
                }
                $('#subscription-error').css('display', 'block');                
                return false;
            }
        } 
    });

    return $.mage.worldpayPriceSubscription;
});
