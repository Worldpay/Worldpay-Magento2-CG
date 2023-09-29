define(
    [
        'Sapient_Worldpay/js/view/payment/method-renderer/cc-method',
        'jquery',
        'Sapient_Worldpay/js/model/disclaimer-confirm',
        'domReady!'
    ],
    function (
        Component,
        $,
        disclaimerConfirm
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


                if ($('.checkout-agreements').find('input[type="checkbox"]:not(:checked)').length > 0) {
                    $('#checkout-agreement-error-msg').css('display', 'block');
                    return false;
                }

                var $form = $('#' + this.getCode() + '-form');
                if (this.getselectedCCType('payment[cc_type]') == undefined && $('#saved-Card-Visibility-Enabled').css('display') == 'none') {
                    $('#cc_type-error').css('display', 'block');
                    $('#cc_type-error').html("<div>" + this.getCreditCardExceptions('CCAM6') + "</div>");
                    return false;
                }


                if ($form.validation() && $form.validation('isValid')) {
                    $('#cc_type-error').css('display', 'none');
                    if (this.getIntigrationMode() == 'redirect' && this.getHppIntegrationType() == 'iframe') {
                        this.loadHppIframe();
                    }
                    else {
                        this.preparePayment();
                    }
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
                    if (value.id == "p_method_worldpay_cc")
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
            },
            disclaimerPopup: function(){
                disclaimerConfirm.openDisclaimer();
            }
        });
    }
);
