/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'underscore',
    'Magento_Ui/js/form/form',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/action/set-billing-address',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'Magento_Checkout/js/model/billing-address-postcode-validator'
],
function (
    ko,
    _,
    Component,
    customer,
    addressList,
    quote,
    createBillingAddress,
    selectBillingAddress,
    checkoutData,
    checkoutDataResolver,
    customerData,
    setBillingAddressAction,
    globalMessageList,
    $t,
     billingAddressPostcodeValidator
) {
    'use strict';

    var lastSelectedBillingAddress = null,
        newAddressOption = {
            /**
             * Get new address label
             * @returns {String}
             */
            getAddressInline: function () {
                return $t('New Address');
            },
            customerAddressId: null
        },
        countryData = customerData.get('directory-data'),
        addressOptions = addressList().filter(function (address) {
            return address.getType() == 'customer-address'; //eslint-disable-line eqeqeq
        });

    addressOptions.push(newAddressOption);

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/billing-address',
            actionsTemplate: 'Magento_Checkout/billing-address/actions',
            formTemplate: 'Magento_Checkout/billing-address/form',
            detailsTemplate: 'Magento_Checkout/billing-address/details',
            links: {
                isAddressFormVisible: '${$.billingAddressListProvider}:isNewAddressSelected'
            }
        },
        currentBillingAddress: quote.billingAddress,
        addressOptions: addressOptions,
        customerHasAddresses: addressOptions.length > 1,

        /**
         * Init component
         */
        initialize: function () {
            this._super();
            quote.paymentMethod.subscribe(function () {
                checkoutDataResolver.resolveBillingAddress();
            }, this);
            billingAddressPostcodeValidator.initFields(this.get('name') + '.form-fields');
        },

        /**
         * @return {exports.initObservable}
         */
        initObservable: function () {
            this._super()
                .observe({
                    selectedAddress: null,
                    isAddressDetailsVisible: quote.billingAddress() != null,
                    isAddressFormVisible: !customer.isLoggedIn() || addressOptions.length === 1,
                    isAddressSameAsShipping: false,
                    saveInAddressBook: 1
                });

            quote.billingAddress.subscribe(function (newAddress) {
                if (quote.isVirtual()) {
                    this.isAddressSameAsShipping(false);
                } else {
                    this.isAddressSameAsShipping(
                        newAddress != null &&
                        newAddress.getCacheKey() == quote.shippingAddress().getCacheKey() //eslint-disable-line eqeqeq
                    );
                }

                if (newAddress != null && newAddress.saveInAddressBook !== undefined) {
                    this.saveInAddressBook(newAddress.saveInAddressBook);
                } else {
                    this.saveInAddressBook(1);
                }
                this.isAddressDetailsVisible(true);
            }, this);

            return this;
        },

        canUseShippingAddress: ko.computed(function () {
            return !quote.isVirtual() && quote.shippingAddress() && quote.shippingAddress().canUseForBilling();
        }),

        /**
         * @param {Object} address
         * @return {*}
         */
        addressOptionsText: function (address) {
            return address.getAddressInline();
        },

        /**
         * @return {Boolean}
         */
        useShippingAddress: function () {
            if (this.isAddressSameAsShipping()) {
                selectBillingAddress(quote.shippingAddress());

                this.updateAddresses();
                this.isAddressDetailsVisible(true);
            } else {
                lastSelectedBillingAddress = quote.billingAddress();
                quote.billingAddress(null);
                this.isAddressDetailsVisible(false);
            }
            checkoutData.setSelectedBillingAddress(null);
            
            
            
        if (window.ApplePaySession) {
        //var merchantIdentifier = '<?=PRODUCTION_MERCHANTIDENTIFIER?>';
        var merchantIdentifier = window.checkoutConfig.payment.ccform.appleMerchantid;
        var promise = ApplePaySession.canMakePaymentsWithActiveCard(merchantIdentifier);
        promise.then(function (canMakePayments) {
               if (canMakePayments) {
                   var wallets_APPLEPAY = document.getElementById("wallets_APPLEPAY-SSL");
                   var wallets_image_APPLEPAY = document.getElementById("wallets_image_APPLEPAY-SSL");
                   var wallets_label_APPLEPAY = document.getElementById("wallets_label_APPLEPAY-SSL");
                   
                   if(wallets_APPLEPAY) {
                       //document.getElementById("wallets_APPLEPAY-SSL").style.display = "block";
                       document.getElementById("wallets_APPLEPAY-SSL").style.display = "table-cell";
                   }
                   if(wallets_image_APPLEPAY) {
                       document.getElementById("wallets_image_APPLEPAY-SSL").style.display = "table-cell";
                   }
                   if(wallets_label_APPLEPAY) {
                       document.getElementById("wallets_label_APPLEPAY-SSL").style.display = "table-cell";
                   }
                  
                     
               } 
             }); 
     } 
                   
                   

            return true;
        },

        /**
         * Update address action
         */
        updateAddress: function () {
            var addressData, newBillingAddress;

            if (this.selectedAddress() && this.selectedAddress() != newAddressOption) { //eslint-disable-line eqeqeq
                selectBillingAddress(this.selectedAddress());
                checkoutData.setSelectedBillingAddress(this.selectedAddress().getKey());
            } else {
                this.source.set('params.invalid', false);
                this.source.trigger(this.dataScopePrefix + '.data.validate');

                if (this.source.get(this.dataScopePrefix + '.custom_attributes')) {
                    this.source.trigger(this.dataScopePrefix + '.custom_attributes.data.validate');
                }

                if (!this.source.get('params.invalid')) {
                    addressData = this.source.get(this.dataScopePrefix);

                    if (customer.isLoggedIn() && !this.customerHasAddresses) { //eslint-disable-line max-depth
                        this.saveInAddressBook(1);
                    }
                    addressData['save_in_address_book'] = this.saveInAddressBook() ? 1 : 0;
                    newBillingAddress = createBillingAddress(addressData);

                    // New address must be selected as a billing address
                    selectBillingAddress(newBillingAddress);
                    checkoutData.setSelectedBillingAddress(newBillingAddress.getKey());
                    checkoutData.setNewCustomerBillingAddress(addressData);
                }
            }
            this.updateAddresses();
            
                   if (window.ApplePaySession) {
        //var merchantIdentifier = '<?=PRODUCTION_MERCHANTIDENTIFIER?>';
        var merchantIdentifier = window.checkoutConfig.payment.ccform.appleMerchantid;
        var promise = ApplePaySession.canMakePaymentsWithActiveCard(merchantIdentifier);
        promise.then(function (canMakePayments) {
               if (canMakePayments) {
                   var wallets_APPLEPAY = document.getElementById("wallets_APPLEPAY-SSL");
                   var wallets_image_APPLEPAY = document.getElementById("wallets_image_APPLEPAY-SSL");
                   var wallets_label_APPLEPAY = document.getElementById("wallets_label_APPLEPAY-SSL");
                   
                   if(wallets_APPLEPAY) {
                       //document.getElementById("wallets_APPLEPAY-SSL").style.display = "block";
                       document.getElementById("wallets_APPLEPAY-SSL").style.display = "inline";
                   }
                   if(wallets_image_APPLEPAY) {
                       document.getElementById("wallets_image_APPLEPAY-SSL").style.display = "inline";
                   }
                   if(wallets_label_APPLEPAY) {
                       document.getElementById("wallets_label_APPLEPAY-SSL").style.display = "inline";
                   }
                  
                     
               } 
             }); 
     } 
            
                  
                  
        },

    });
});                                                                                                                            