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
	'samsungPay'
], function ($, ko,storage,urlBuilder,$t, samsungPay){
    'use strict';  

    return {
        /**
         * @return {Array}
         */
        fetchShippingRates : function(checkoutObj){
            var self = this,apiUrl=null;
            if (checkoutObj.isCustomerLoggedIn) { //Api for logged inb customer
                apiUrl = 'rest/'+checkoutObj.store_code+'/V1/carts/mine/estimate-shipping-methods';
            } else { // Api for guest customer
                apiUrl = 'rest/'+checkoutObj.store_code+'/V1/guest-carts/' + checkoutObj.guest_masked_quote_id + '/estimate-shipping-methods';
            }

            return storage.post(
                    apiUrl, 
                    JSON.stringify(checkoutObj.payload)
                )

        },
        fetchTotals : function(checkoutObj){
            var self= this;            
            var req = {
                'addressInformation' : checkoutObj.addressInformation
            }
            var jsonReq = req;
            var totalsUrl;
            if (checkoutObj.isLoggedin) { //Api for logged inb customer
                totalsUrl = BASE_URL + 'rest/'+checkoutObj.store_code+'/V1/carts/mine/totals-information';
            } else { // Api for guest customer
                totalsUrl = BASE_URL + 'rest/'+checkoutObj.store_code+'/V1/guest-carts/' + 
                checkoutObj.quote_masked_id + '/totals-information';
            }
            return storage.post(
                totalsUrl, 
                JSON.stringify(jsonReq)
            );
        },
        placeorder : function(checkoutObj){
            var self = this;
            var guestMaskedQuoteId = checkoutObj.guest_masked_quote_id;
            var apiUrl;
            var orderResponse;
            var dfReferenceId = "";
            var type="POST";
            if (checkoutObj.isCustomerLoggedIn) { //Api for logged in customer

                apiUrl = BASE_URL + 'rest/'+checkoutObj.storecode+'/V1/carts/mine/shipping-information';
               
            } else { // Api for guest customer
                apiUrl = BASE_URL + 'rest/'+checkoutObj.storecode+'/V1/guest-carts/' + 
                        guestMaskedQuoteId + '/shipping-information';
               
            }
            

            var firstName = checkoutObj.billingAddress.firstName;
            var lastName = checkoutObj.billingAddress.lastName;
            
            var customerDetails = {  
                "addressInformation": {
                    "shipping_address": checkoutObj.shippingAddress ,
                    "billing_address": checkoutObj.billingAddress,
                    "shipping_carrier_code": checkoutObj.shippingMethod.carrier_code,
                    "shipping_method_code": checkoutObj.shippingMethod.method_code
             }
            }
            
            $("body").trigger('processStart');

            if(checkoutObj.isRequiredShipping){
            self.sendRequest(
                type,
                apiUrl,
                JSON.stringify(customerDetails)
            ).done(
                function(apiresponse){                    
                            var response = (apiresponse);
                            //Add payment information and place the order
                            var paymentDetails = {
                                "paymentMethod": checkoutObj.paymentDetails,
                                "billing_address": checkoutObj.billingAddress
                            };
                            var orderApiUrl;
                            if (checkoutObj.isCustomerLoggedIn) { //Api for logged in customer
                                orderApiUrl = BASE_URL + 'rest/'+checkoutObj.storecode+'/V1/carts/mine/payment-information';
                                type = 'POST';
                            } else { // Api for guest customer
                                orderApiUrl = BASE_URL + 'rest/'+checkoutObj.storecode+'/V1/guest-carts/' + guestMaskedQuoteId + '/order';
                                type = 'PUT';
                            }
            
                            self.sendRequest(
                                type,
                                orderApiUrl,
                                JSON.stringify(paymentDetails)
                            ).done(
                                function (apiresponse){
                                    var orderResponse  = (apiresponse);
                                    
                                    //$("body").trigger('processStop');
                                        if(isNaN(orderResponse)){
                                            if(orderResponse.hasOwnProperty("message") && orderResponse['message'].indexOf('3DS2')!==-1) {
                                                //window.location.href = baseUrl + 'checkout/cart?error=true&message=error';
                                                $(".error-message").html(orderResponse['message']);
                                            }                                
                                            window.location.href = BASE_URL + 'checkout/cart?error=true';                                
                                        }
                                        if (orderResponse){
                                            // window.location.href = baseUrl + 'checkout/onepage/success';
                                            window.location.href = BASE_URL + 'worldpay/savedcard/redirect';
                                        }else{
                                            window.location.href = BASE_URL + 'checkout/cart?error=true&message=error';
                                             //$(".error-message").html(orderResponse['message']);
                                        }
                                }
                            ).fail(
                                function (response) {
                                    var orderResponse = JSON.parse(response.responseText);
                                    $("body").trigger('processStop');
                                    $(".error-message").html(orderResponse['message']);
                                    console.log("Error:", response); 
                                }
                            )                        
                }
            ).fail(
                function (response) {
                    var orderResponse = JSON.parse(response.responseText);
                    $("body").trigger('processStop');
                    $(".error-message").html(orderResponse['message']);
                }
            );    
            
            }else{
                // for virtual and downlodable products
                var customerDetails = {  
                    "address": checkoutObj.billingAddress
                 }
                
                 var apiUrl = null;
                 if (checkoutObj.isCustomerLoggedIn) { //Api for logged in customer

                    apiUrl = BASE_URL + 'rest/'+checkoutObj.storecode+'/V1/carts/mine/billing-address';
                   
                } else { // Api for guest customer
                    apiUrl = BASE_URL + 'rest/'+checkoutObj.storecode+'/V1/guest-carts/' + 
                            guestMaskedQuoteId + '/billing-address';                   
                }

                self.sendRequest(
                    'POST',
                    apiUrl,
                    JSON.stringify(customerDetails)
                ).done(
                    function(apiresponse){ 
                        var paymentDetails = {
                            "paymentMethod": checkoutObj.paymentDetails,
                            "billing_address": checkoutObj.billingAddress
                        };
                        var orderApiUrl;
                        if (checkoutObj.isCustomerLoggedIn) { //Api for logged in customer
                            orderApiUrl = BASE_URL + 'rest/'+checkoutObj.storecode+'/V1/carts/mine/payment-information';
                            type = 'POST';
                        } else { // Api for guest customer
                            orderApiUrl = BASE_URL + 'rest/'+checkoutObj.storecode+'/V1/guest-carts/' + guestMaskedQuoteId + '/order';
                            type = 'PUT';
                        }
        
                        self.sendRequest(
                            type,
                            orderApiUrl,
                            JSON.stringify(paymentDetails)
                        ).done(
                            function (apiresponse){
                                var orderResponse  = (apiresponse);   
                                //$("body").trigger('processStop');
                                    if(isNaN(orderResponse)){
                                        if(orderResponse.hasOwnProperty("message") && orderResponse['message'].indexOf('3DS2')!==-1) {
                                            //window.location.href = baseUrl + 'checkout/cart?error=true&message=error';
                                            $(".error-message").html(orderResponse['message']);
                                        }                                
                                        window.location.href = BASE_URL + 'checkout/cart?error=true';                                
                                    }                                    
                                    if (orderResponse){
                                        // window.location.href = baseUrl + 'checkout/onepage/success';
                                        window.location.href = BASE_URL + 'worldpay/savedcard/redirect';
                                    }else{
                                        window.location.href = BASE_URL + 'checkout/cart?error=true&message=error';
                                         //$(".error-message").html(orderResponse['message']);
                                    }
                            }
                        ).fail(
                            function (response) {
                                var orderResponse = JSON.parse(response.responseText);
                                $("body").trigger('processStop');
                                $(".error-message").html(orderResponse['message']);
                                console.log("Error:", response); 
                            }
                        )                        

                }).fail(function(response){
                    var orderResponse = JSON.parse(response.responseText);
                    $("body").trigger('processStop');
                    $(".error-message").html(orderResponse['message']);
                });
            }
        },
        setPaymentInformationAndPlaceOrder : function(checkoutObj, samsungResponse = null){
            var paymentDetails = {
                "paymentMethod": checkoutObj.paymentDetails,
                "billing_address": checkoutObj.billingAddress
            };
            var self=this;
            var type="POST";
            var guestMaskedQuoteId = checkoutObj.guest_masked_quote_id;
            var orderApiUrl;
            var cc_type = checkoutObj.paymentDetails.additional_data.cc_type;
            if (checkoutObj.isCustomerLoggedIn) { //Api for logged in customer
                orderApiUrl = BASE_URL + 'rest/'+checkoutObj.storecode+'/V1/carts/mine/payment-information';
                type = 'POST';
            } else { // Api for guest customer
                orderApiUrl = BASE_URL + 'rest/'+checkoutObj.storecode+'/V1/guest-carts/' + guestMaskedQuoteId + '/payment-information';
                paymentDetails.cartId = checkoutObj.guest_masked_quote_id;
                paymentDetails.email = checkoutObj.billingAddress.email;
                
            
            }
            $("body").trigger('processStart');
            self.sendRequest(
                type,
                orderApiUrl,
                JSON.stringify(paymentDetails)
            ).done(
                function (apiresponse){
                    var orderResponse  = (apiresponse);   
                    //$("body").trigger('processStop');
                        if(isNaN(orderResponse)){
                            if(orderResponse.hasOwnProperty("message") && orderResponse['message'].indexOf('3DS2')!==-1) {
                                //window.location.href = baseUrl + 'checkout/cart?error=true&message=error';
                                $(".error-message").html(orderResponse['message']);
                            }                                
                            window.location.href = BASE_URL + 'checkout/cart?error=true';                                
                        }                        
                        if(cc_type == 'SAMSUNGPAY-SSL'){
                            var cancel = urlBuilder.build('worldpay/samsungpay/CallBack');
                            var serviceId = window.checkoutConfig.payment.ccform.samsungServiceId;
                            var callback = urlBuilder.build('worldpay/samsungpay/CallBack');
                            var countryCode = window.checkoutConfig.defaultCountryId;
                            console.log('Authentication is success, Redirecting to Samsung Payment Page......');
                            SamsungPay.connect(
                                samsungResponse.id, samsungResponse.href, serviceId, callback, cancel, countryCode,
                                samsungResponse.encInfo.mod, samsungResponse.encInfo.exp, samsungResponse.encInfo.keyId
                            );
                            return false;
                        }
                        if (orderResponse){

                            if(typeof checkoutObj.paymentDetails.method !='undefined'){

                                if(checkoutObj.paymentDetails.method == 'worldpay_paybylink'){
                                    window.location.href = BASE_URL + 'worldpay/paybylink/orderplaced';
                                    return;
                                }
                            }

                            // window.location.href = baseUrl + 'checkout/onepage/success';
                            window.location.href = BASE_URL + 'worldpay/savedcard/redirect';
                        }else{
                            window.location.href = BASE_URL + 'checkout/cart?error=true&message=error';
                            //$(".error-message").html(orderResponse['message']);
                        }
                        $("body").trigger('processStop');
                }
            ).fail(
                function (response) {
                    var orderResponse = JSON.parse(response.responseText);
                    $("body").trigger('processStop');
                    $(".error-message").html(orderResponse['message']);
                    console.log("Error:", response); 
                }
            )
                

        },    
        applyCoupon : function(quoteObj,couponCode){          
            var apiUrl = "",self=this,
            message = $t('Your coupon was successfully applied.'),
            data = {},
            headers = {};

            if (quoteObj.isCustomerLoggedIn) { 
                apiUrl = BASE_URL+'rest/'+quoteObj.storecode+'/V1/carts/mine/coupons/' + encodeURIComponent(couponCode)
            } else { // Api for guest customer
                apiUrl = BASE_URL+'rest/'+quoteObj.storecode+'/V1/guest-carts/' + quoteObj.quoteId + '/coupons/' + encodeURIComponent(couponCode);
               
            }

            return storage.put(
                    apiUrl,
                    data,
                    false,
                    null,
                    headers
                )
        },
        cancelCoupon : function(quoteObj){
            var apiUrl = "",self=this,
            message = $t('Your coupon was successfully removed.');           

            if (quoteObj.isCustomerLoggedIn) { 
                apiUrl = BASE_URL+'rest/'+quoteObj.storecode+'/V1/carts/mine/coupons/';
            } else { // Api for guest customer
                apiUrl = BASE_URL+'rest/'+quoteObj.storecode+'/V1/guest-carts/' + quoteObj.quoteId + '/coupons/';
               
            }

            return storage.delete(
                apiUrl,
                false
            )
        },
        sendRequest : function(type, url, data, global, contentType, headers){
            headers = headers || {};
            global = global === undefined ? true : global;
            contentType = contentType || 'application/json';

            return $.ajax({
                url: urlBuilder.build(url),
                type: type,
                data: data,
                global: global,
                contentType: contentType,
                headers: headers
            });
        },
        fetchValuesFromAddressForm: function(formId){
            var streetAddress = [],
            formData = $(formId).serializeArray().reduce(function(obj, item) {
                obj[item.name] = item.value;
                if(item.name == 'street[0]' || item.name == 'street[1]' || item.name == 'street[2]' ){
                    streetAddress.push(item.value);
                }

                return obj;
            }, {});
            formData.street = streetAddress;
            formData.regionId = 0; // for M2 validation 
			if(typeof formData.region_id != 'undefined'){
				formData.regionId = formData.region_id;
			}
            if(typeof formData.billing_country_id!='undefined'){
                formData.country_id = formData.billing_country_id;
                delete formData.billing_country_id;
            }
            if(typeof formData.billing_region_id!='undefined'){
                formData.region_id = formData.billing_region_id;
                delete formData.billing_region_id;
            }
            if(typeof formData.billing_region!='undefined'){
                formData.region = formData.billing_region;
                delete formData.billing_region;
            }

            if(typeof formData['street[0]']!='undefined'){
                delete formData['street[0]'];
            }

            if(typeof formData['street[1]']!='undefined'){
                delete formData['street[1]'];
            }

            if(typeof formData['street[2]']!='undefined'){
                delete formData['street[2]'];
            }
            return formData;
        }
    };
});