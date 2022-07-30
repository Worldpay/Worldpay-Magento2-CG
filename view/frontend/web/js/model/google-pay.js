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
    'mage/storage',
    'mage/url',
    'mage/translate',
    'googlePay'
], function ($, ko,storage,urlBuilder,$t) {
    'use strict';  

    return {
        paymentsClient : null,
        getCardPaymentObj : function(initData){
            var self= this;
            return Object.assign(
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
                console.log('Gpay response ==>',response);
                if (response.result) {
                   const button =
                    paymentsClient.createButton({
                        buttonColor : initData.google_btn_customisation.buttonColor,
                        buttonType : initData.google_btn_customisation.buttonType,
                        buttonLocale : initData.google_btn_customisation.buttonLocale,
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
                        'width': "49%",
                        'margin-bottom': "10px"
                    }
                )
            }
        },
        getGooglePaymentDataRequest : function(initData){
            var self = this;            
            const paymentDataRequest = Object.assign({}, initData.baseRequest);
             paymentDataRequest.allowedPaymentMethods = [self.getCardPaymentObj(initData)];
             //paymentDataRequest.transactionInfo = self.getGoogleTransactionInfo(initData);
             paymentDataRequest.merchantInfo = {
                merchantName: initData.merchantName
             };
             return paymentDataRequest;
        },
        initGooglePay: function (initData) {           
            var self= this;   
            const paymentsClient = self.getGooglePaymentsClient(initData);
            const paymentDataRequest = self.getGooglePaymentDataRequest(initData);
            paymentDataRequest.transactionInfo = self.getGoogleTransactionInfo(initData);
           return  paymentsClient.loadPaymentData(paymentDataRequest);
        },
        getGooglePaymentsClient : function(initData){
            var self = this;
            if ( self.paymentsClient === null ) {
                self.paymentsClient = new google.payments.api.PaymentsClient({ 
                        environment: initData.env_mode
                    }); 
            }            
            return self.paymentsClient;
        },
        getGoogleTransactionInfo : function(initData){
            return {
                displayItems:[],
                currencyCode: initData.currencyCode,
                totalPriceStatus: $t('ESTIMATED'),
                totalPrice: parseFloat(initData.totalPrice).toFixed(2),
                totalPriceLabel: $t("Total")
            };
        },
        onPaymentAuthorized : function(){         
            return new Promise(function(resolve, reject){
                // handle the response
                processPayment(paymentData)
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
