/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'ko',  
    'underscore',  
    'mage/storage',
    'mage/url',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'Magento_Catalog/js/price-utils',
    'googlePay'
], function ($, ko,_,storage,urlBuilder,$t,customerData,priceUtils) {
    'use strict';  
    return {
        paymentsClient : null,
        getCardPaymentObj : function(initData){
            var self= this;
            if(window.walletpayObj){
                var paymentObj = Object.assign(
                    {},
                    {
                        type: 'CARD',
                        parameters: {
                            allowedAuthMethods: initData.allowedCardAuthMethods,
                            allowedCardNetworks: initData.allowedCardNetworks,
                            billingAddressRequired: true,
                            billingAddressParameters: self.getGoogleBillingAddressParameters()
                        }
                    },
                    {
                        tokenizationSpecification: initData.tokenizationSpecification
                    }
                );
            }else{
                var paymentObj = Object.assign(
                    {},
                    {
                        type: 'CARD',
                        parameters: {
                            allowedAuthMethods: initData.allowedCardAuthMethods,
                            allowedCardNetworks: initData.allowedCardNetworks
                        }
                    },
                    {
                        tokenizationSpecification: initData.tokenizationSpecification
                    }
                );

            }

            return paymentObj;
        },     
        getGoogleBillingAddressParameters: function(){
            return  {
                 "format": "FULL",
                 "phoneNumberRequired": true
               }
         },
        getGoogleIsReadyToPay : function(initData){
            var self = this;
            return Object.assign(
                {},
                initData.baseRequest,
                {
                  allowedPaymentMethods: [self.getCardPaymentObj(initData)]
                }
            );
        },
        addGooglePayButton : function(container,initData,callback){
            var self = this;
            const paymentsClient = self.getGooglePaymentsClient(initData);
            paymentsClient.isReadyToPay(self.getGoogleIsReadyToPay(initData)).then(function(response){
                if (response.result) {
                   const button =
                    paymentsClient.createButton({
                        buttonColor : initData.google_btn_customisation.buttonColor,
                        buttonType : initData.google_btn_customisation.buttonType,
                        buttonLocale : initData.google_btn_customisation.buttonLocale,
						buttonSizeMode : initData.google_btn_customisation.buttonSizeMode,
                        onClick: callback
                    });
                    document.getElementById(container).appendChild(button);
                    self.addStyling(container);
                }
            }).catch(function(err) {                
                console.error(err);                
            });
        },
        addStyling : function(container){
           
            if((window.screen.width <=768) || (window.screen.width > 768 && window.screen.width<=820)){
                $("#"+container).css(
                    {
                        'width': "100%",
                        'margin-bottom': "10px"
                    }
                )   
            }else{
                $("#"+container).css(
                    {
                        'width': "88%",
                        'margin-bottom': "10px"
                    }
                )
            }
        },
        getGooglePaymentDataRequest : function(initData){
            var self = this;            
            const paymentDataRequest = Object.assign({}, initData.baseRequest);
             paymentDataRequest.allowedPaymentMethods = [self.getCardPaymentObj(initData)];
             paymentDataRequest.merchantInfo = {
                merchantName: initData.merchantName
             };

             if(window.walletpayObj){
                if(window.walletpayObj.isRequiredShipping() == true){
                    paymentDataRequest.callbackIntents = ["SHIPPING_ADDRESS",  "SHIPPING_OPTION", "PAYMENT_AUTHORIZATION"];
                    paymentDataRequest.shippingAddressRequired = true;
                    paymentDataRequest.shippingAddressParameters = self.getGoogleShippingAddressParameters();
                    paymentDataRequest.shippingOptionRequired = true;
                }else{
                    paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];

                }
                
                paymentDataRequest.emailRequired = true;
            }
             return paymentDataRequest;
        },
        getGoogleShippingAddressParameters: function(){
            return  {
                phoneNumberRequired: true
              };
        },
        initGooglePay: function (initData) {           
            var self= this;
            
            if(window.walletpayObj){
                if(window.walletpayObj.isRequiredShipping() == false){
                    var paymentsClientObj = self.getGooglePaymentsClientNS(initData);
                }else{
                    var paymentsClientObj = self.getGooglePaymentsClient(initData);
                }
                const paymentsClient = paymentsClientObj;
                const paymentDataRequest = self.getGooglePaymentDataRequest(initData);
                paymentDataRequest.transactionInfo = self.getGoogleTransactionInfo(initData);
                return  paymentsClient.loadPaymentData(paymentDataRequest);

            }else{
                var paymentsClientObj = self.getGooglePaymentsClient(initData);
                const paymentsClient = paymentsClientObj;
                const paymentDataRequest = self.getGooglePaymentDataRequest(initData);
                paymentDataRequest.transactionInfo = self.getGoogleTransactionInfoCheckout(initData);
                console.log(paymentDataRequest);
                return  paymentsClient.loadPaymentData(paymentDataRequest);

            }           
           
        },
        getGooglePaymentsClientNS: function(initData){
            window.gpayLib = this;
            return new google.payments.api.PaymentsClient({ 
                        environment: initData.env_mode,
                        paymentDataCallbacks: {
                            onPaymentAuthorized: window.gpayLib.onPaymentAuthorized
                          }
                    });       
        },
        getGooglePaymentsClient : function(initData){
            window.gpayLib = this;
            if(window.walletpayObj){
                return new google.payments.api.PaymentsClient({ 
                    environment: initData.env_mode,
                    paymentDataCallbacks: {
                        onPaymentAuthorized: window.gpayLib.onPaymentAuthorized,
                        onPaymentDataChanged: window.gpayLib.onPaymentDataChanged
                      }
                });
            }else{
                return new google.payments.api.PaymentsClient({ 
                    environment: initData.env_mode
                });
            }

            
        },
        reloadShippingOptions : function(shippingaddress,PaymentDataRequestUpdate,resolve){
            var self = window.gpayLib;
            var formattedSHippingAdress = {
                'firstname' : '',
                'lastname' : '',
                'street' : [],
                'city' : shippingaddress.locality,
                'country_id' : shippingaddress.countryCode,
                'postcode' : shippingaddress.postalCode
            }
            var regions = self.isRegionAvailable(shippingaddress.countryCode);
            var shippingStateName =  shippingaddress.administrativeArea;
            _.each(regions,function(value,key){
                if(value.code == shippingStateName){
                    shippingStateName = value.name
                    formattedSHippingAdress.region_id = key;
                }
            });

            if(typeof formattedSHippingAdress.region_id =='undefined'){
                formattedSHippingAdress.region = shippingStateName;
            }
            window.walletpayObj.fetchRatesByDynamicAddress(formattedSHippingAdress,function(){
               var defaultShippingMethods=  $.localStorage.get('wp-default-shipping-method');

                var shippingMethodList = [];
                var shippingMethodListLS = [];
                _.each(defaultShippingMethods,function(value){
                    var titledesc = priceUtils.formatPrice(value.amount, window.walletpayObj.priceFormat)+' '+value.carrier_title+' - '+ value.method_title;
                    shippingMethodList.push({
                        "id": value.carrier_code+'_'+value.method_code,
                        "label": titledesc,
                        "description": value.method_title,
                    });

                    shippingMethodListLS.push({
                        "id": value.carrier_code+'_'+value.method_code,
                        "label": titledesc,
                        "amount": value.amount,
                        "description": value.method_title,
                    });

                });
                $.localStorage.set('wp-default-shipping-method',shippingMethodListLS); // set in localstorage
                PaymentDataRequestUpdate.newShippingOptionParameters = {
                    defaultSelectedOptionId: shippingMethodList[0].id,
                    shippingOptions: shippingMethodList
                }

                PaymentDataRequestUpdate.newTransactionInfo = self.calculateNewTransactionInfo(PaymentDataRequestUpdate.newShippingOptionParameters.defaultSelectedOptionId);
                resolve(PaymentDataRequestUpdate);
            });

        },
        isRegionAvailable: function(countryCode){
            var cacheStorage = $.localStorage.get('mage-cache-storage');
            var directoryData = cacheStorage['directory-data'];
            var regionsData = directoryData[countryCode];
            if(regionsData.regions){
                return regionsData.regions;
            }
            return {};
        },
        onPaymentDataChanged: function(intermediatePaymentData){
            var self = window.gpayLib;
            return new Promise(function(resolve, reject) {

                let shippingAddress = intermediatePaymentData.shippingAddress;
                let shippingOptionData = intermediatePaymentData.shippingOptionData;
                let PaymentDataRequestUpdate = {};
            
                if (intermediatePaymentData.callbackTrigger == "INITIALIZE") {
                    PaymentDataRequestUpdate.newShippingOptionParameters = self.getGoogleDefaultShippingOptions();                    
                    let selectedShippingOptionId = PaymentDataRequestUpdate.newShippingOptionParameters.defaultSelectedOptionId;
                    PaymentDataRequestUpdate.newTransactionInfo = self.calculateNewTransactionInfo(selectedShippingOptionId);
                    resolve(PaymentDataRequestUpdate);

                }else if (intermediatePaymentData.callbackTrigger == "SHIPPING_ADDRESS") {
                    self.reloadShippingOptions(shippingAddress,PaymentDataRequestUpdate,resolve);
                }
                else if (intermediatePaymentData.callbackTrigger == "SHIPPING_OPTION") {
                    PaymentDataRequestUpdate.newTransactionInfo = self.calculateNewTransactionInfo(shippingOptionData.id);
                  resolve(PaymentDataRequestUpdate);
                }                
              });
        },
        getGoogleDefaultShippingOptions: function(){
            var defaultShippingMethods = $.localStorage.get('wp-default-shipping-method');
            var shippingMethodList = [];
            _.each(defaultShippingMethods,function(value){
                var titledesc = priceUtils.formatPrice(value.amount, window.walletpayObj.priceFormat)+' '+value.carrier_title+' - '+ value.method_title;
                shippingMethodList.push({
                    "id": value.carrier_code+'_'+value.method_code,
                    "label": titledesc,
                    "description": value.method_title,
                });
            });
            return {
                defaultSelectedOptionId: shippingMethodList[0].id,
                shippingOptions: shippingMethodList
              };
        },
        
        getShippingCosts: function() {
            var defaultShippingMethods = $.localStorage.get('wp-default-shipping-method');
            var shippingMethodPrice = {};
            _.each(defaultShippingMethods,function(value){
                shippingMethodPrice[value.id] = value.amount; 
            });
            return shippingMethodPrice;
        },

        getGoogleUnserviceableAddressError: function(){
            return {
                reason: "SHIPPING_ADDRESS_UNSERVICEABLE",
                message: "Cannot ship to the selected address",
                intent: "SHIPPING_ADDRESS"
              };
        },
        calculateNewTransactionInfo: function(shippingOptionId){
            var self = window.gpayLib;
            let newTransactionInfo = self.getGoogleTransactionInfo();
            let shippingCost = self.getShippingCosts()[shippingOptionId];

            //let shippingCost = 5;
            newTransactionInfo.displayItems.push({
                type: "LINE_ITEM",
                label: "Shipping",
                price: parseFloat(shippingCost).toFixed(2),
                status: "FINAL"
            });
            let totalPrice = 0.00;
            newTransactionInfo.displayItems.forEach(displayItem => totalPrice += parseFloat(displayItem.price));
            newTransactionInfo.totalPrice = totalPrice.toString();

            return newTransactionInfo;
        },
        getGoogleTransactionInfo : function(){
            var cart = customerData.get('cart');
            var totalAmount = cart().subtotalAmount;
            if(window.walletpayObj){
                totalAmount = window.walletpayObj.grandtotal();
            }
            return {
            displayItems: [
                {
                  label: "Subtotal",
                  type: "SUBTOTAL",
                  price: parseFloat(totalAmount).toFixed(2),
                },
              ],
              countryCode: 'US',
              currencyCode: "USD",
              totalPriceStatus: $t('ESTIMATED'),
              totalPrice: parseFloat(totalAmount).toFixed(2),
              totalPriceLabel: $t('Total')
            };
        },
        getGoogleTransactionInfoCheckout: function(initData){
            return {
                displayItems:[],
                currencyCode: initData.currencyCode,
                totalPriceStatus: $t('ESTIMATED'),
                totalPrice: parseFloat(initData.totalPrice).toFixed(2),
                totalPriceLabel: $t("Total")
            };
        },
        formattedGpayAddress: function(address){
            var self= window.gpayLib;
            var name = address.name;
            var firstname = name.substring(0, name.indexOf(' ')); 
            var lastname = name.substring(name.indexOf(' ') + 1);             
            var formattedAdress = {
                'firstname' : firstname,
                'lastname' : lastname,
                'street' : [address.address1,address.address2,address.address3],
                'city' : address.locality,
                'country_id' : address.countryCode,
                'postcode' : address.postalCode,
                'telephone': address.phoneNumber
            }
            var regions = self.isRegionAvailable(address.countryCode);
            var stateName =  address.administrativeArea;
            _.each(regions,function(value,key){
                if(value.code == stateName){
                    stateName = value.name
                    formattedAdress.region_id = key;
                }
            });
            return formattedAdress;
        },
        processPayment: function(paymentData) {
            var self= window.gpayLib;
            return new Promise(function(resolve, reject) {
                setTimeout(function() {
                    paymentData.formattedBillingAddress = self.formattedGpayAddress(paymentData.paymentMethodData.info.billingAddress);
                    if(typeof paymentData.shippingAddress !='undefined'){
                        paymentData.formattedShippingAddress = self.formattedGpayAddress(paymentData.shippingAddress);
                        window.walletpayObj.selectedShippingAddress(paymentData.formattedShippingAddress); 
                    }

                    if(typeof paymentData.shippingOptionData != 'undefined'){
                        let shippingMethodTitle = paymentData.shippingOptionData.id;
                        const explodedShippingMethod = shippingMethodTitle.split("_");

                        window.walletpayObj.selectedShippingMethod({
                            carrier_code : explodedShippingMethod[0],
                            method_code : explodedShippingMethod[1],
                        });
                    }

                    window.walletpayObj.selectedBillingAddress(paymentData.formattedBillingAddress);
                    

                resolve({paymentData:paymentData});
            }, 3000);
         });
        },
        onPaymentAuthorized : function(paymentData){  
            var self= window.gpayLib;
            return new Promise(function(resolve, reject){
                // handle the response
                self.processPayment(paymentData)
                  .then(function() {
                    resolve({transactionState: 'SUCCESS'});
                  })
                  .catch(function() {
                      resolve({
                      transactionState: 'ERROR',
                      error: {
                        intent: 'PAYMENT_AUTHORIZATION',
                        message: 'Insufficient funds',
                        reason: 'PAYMENT_DATA_INVALID'
                      }
                    });
                  });              
                });
        }, 
    
    };
});
