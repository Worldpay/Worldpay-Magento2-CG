define(
    [
        'Sapient_Worldpay/js/view/payment/method-renderer/apm-method',
        'jquery',
        'domReady!'
    ],
    function (
        Component,
        $
    ) {
        'use strict';
        var paymentService = false;
        return Component.extend({
            defaults: {
                continueSelector: '#payment-continue',
                multishipping: true
            },
            initObservable: function () {
                this._super();
                $(this.continueSelector).click(this.onContinue.bind(this));
                return this;
            },
            onContinue: function (e) {
                window.checkoutConfig.CCMethodClass.multishipping = true;
                var self = this;

                if (!this.isWorldpayMethodSelected())
                    return;

                e.preventDefault();
                e.stopPropagation();
                if (!this.validatePaymentMethod())
                    return;

                var $form = $('#' + this.getCode() + '-form');
                if (this.getselectedCCType() == undefined) {
                    $('#apm-type-error').css('display', 'block');
                    $('#apm-type-error').html("<div>" + this.getCreditCardExceptions('CCAM6') + "</div>");
                    return false;
                }
                if ($form.validation() && $form.validation('isValid')) {
                    $('#apm_type-error').css('display', 'none');
                    this.preparePayment();
                    return false;
                }
                return false;
            },
            getCreditCardExceptions: function (exceptioncode) {
                var ccData = window.checkoutConfig.payment.ccform.creditcardexceptions;
                for (var key in ccData) {
                    if (ccData.hasOwnProperty(key)) {
                        var cxData = ccData[key];
                        if (cxData['exception_code'].includes(exceptioncode)) {
                            return cxData['exception_module_messages'] ? cxData['exception_module_messages'] : cxData['exception_messages'];
                        }
                    }
                }
            },
            isWorldpayMethodSelected: function () {
                var methods = $('[name^="payment["]');

                if (methods.length === 0)
                    return false;

                var worldpay = methods.filter(function (index, value) {
                    if (value.id == "p_method_worldpay_apm")
                        return value;
                });

                if (worldpay.length == 0)
                    return false;

                return worldpay[0].checked;
            },
            validatePaymentMethod: function () {
                var methods = $('[name^="payment["]'), isValid = false;

                if (methods.length === 0)
                    this.showError($.mage.__('We can\'t complete your order because you don\'t have a payment method set up.'));
                else if (methods.filter('input:radio:checked').length)
                    return true;
                else
                    this.showError($.mage.__('Please choose a payment method.'));

                return isValid;
            }

        });
    }
);
