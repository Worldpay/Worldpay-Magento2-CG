/**
 * Magento_Checkout/js/view/billing-address
 *
 * Applepay and other modifications
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
    'Magento_Checkout/js/model/billing-address-postcode-validator',
    'Magento_Checkout/js/model/address-converter'
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
    billingAddressPostcodeValidator,
    addressConverter
) {
    'use strict';
    var lastSelectedBillingAddress = null,
        addressUpadated = false

    let mixin = {
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
            // Worldpay - Applepay modification start
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
            // Worldpay - Applepay modification end
            return true;
        },
        /**
         * Update address action
         */
            updateAddress: function () {
            var addressData, newBillingAddress;

            addressUpadated = true;

            if (this.selectedAddress() && !this.isAddressFormVisible()) {
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
            setBillingAddressAction(globalMessageList);
            this.updateAddresses();
            // Worldpay - Applepay modification start
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
            // Worldpay - Applepay modification end
        },
    };
    return function (target) {
        return target.extend(mixin);
    };
});