/*
 * Copyright (c) 2016-2019 Mastercard
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/modal/alert',
        'Mastercard_Mastercard/js/view/payment/hosted-adapter',
        'Magento_Checkout/js/action/set-payment-information',
        'Mastercard_Mastercard/js/action/create-session',
        'Magento_Checkout/js/action/place-order',
        'Magento_CheckoutAgreements/js/model/agreements-assigner',
        'Magento_CheckoutAgreements/js/model/agreement-validator',
        'mage/url',
        'mage/translate'
    ],
    function (Component, $, ko, quote, fullScreenLoader, alert, paymentAdapter, setPaymentInformationAction, createSessionAction,placeOrderAction,agreementsAssigner,agreementsValidator,url,$t) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Mastercard_Mastercard/payment/tns-hosted',
                adapterLoaded: false,
                active: false,
                isSessionCreated : false,
                buttonTitle: null,
                buttonTitleEnabled: $t('Place Order'),
                buttonTitleDisabled: $t('Please wait...'),
                isButtonVisible: ko.observable(false),  
                imports: {
                    onActiveChange: 'active'
                }
            },
            resultIndicator: null,
            sessionVersion: null,

            initObservable: function () {
                this._super()
                    .observe('active adapterLoaded buttonTitle');

                this.buttonTitle(this.buttonTitleDisabled);
                this.isPlaceOrderActionAllowed.subscribe($.proxy(this.buttonTitleHandler, this));
                this.adapterLoaded.subscribe($.proxy(this.buttonTitleHandler, this));
                let self = this;
                let agreementsInputPath = '.payment-method._active div.checkout-agreements input';
                $(document).on('change', agreementsInputPath, function () {
                 if (!$(this).prop('checked')) {
                    $('#embed-target').hide(); 
                    self.buttonTitle(self.buttonTitleEnabled);
                    self.isButtonVisible(true);
                 } 
            });
            

                return this;
            },

            buttonTitleHandler: function (isButtonEnabled) {
                if (isButtonEnabled && this.isActive()) {
                    this.buttonTitle(this.buttonTitleEnabled);
                }
            },
            onActiveChange: function (isActive) {
                let config = this.getConfig();
                $('#embed-target').hide();
                if (isActive && !this.adapterLoaded()) {
                    this.loadAdapter();
                }
                if((config.form_type != 1) && (this.isChecked() == 'tns_hosted') && (config.terms_conditions != 1)){
                    $("#embed-target").removeAttr("style").hide();
                    this.isButtonVisible(false);
                    this.savePaymentAndCheckout(); 
                    $('#embed-target').show();
                }else if((config.form_type != 1) && (this.isChecked() == 'tns_hosted') && (config.terms_conditions == 1)){
                    
                    this.isButtonVisible(true);
                    this.handleWithTermsConditions(config, this);
                }else if((config.form_type == 1) && (this.isChecked() == 'tns_hosted')) {
                    this.isButtonVisible(true);                 
                } 
                  
            },
           handleWithTermsConditions: function (config, context) {
            this.isButtonVisible(true);
            const requestUrl = url.build('tns/hosted/paypaltransaction');
            const quoteId = quote.getQuoteId();

            jQuery.ajax({
                url: requestUrl,
                type: 'POST',
                data: { id: quoteId },
                dataType: 'json',
                success: function(data) {
                    if (data.result === "Y") {
                        const agreementsInputPath = '.payment-method._active div.checkout-agreements input';
                        $(agreementsInputPath).prop('checked', true);

                        if (config.form_type !== 1 && context.isChecked() === 'tns_hosted') {
                            context.savePaymentAndCheckout();
                            $('#embed-target').show();
                        }
                    }
                }
            });
           },
            isActive: function () {
                let active = this.getCode() === this.isChecked();
                this.active(active);
                return active;
            },

            loadAdapter: function (sessionId) {
                let config = this.getConfig();
                paymentAdapter.loadApi(
                    config.component_url,
                    $.proxy(this.paymentAdapterLoaded, this),
                    $.proxy(this.errorCallback, this),
                    $.proxy(this.cancelCallback, this),
                    $.proxy(this.completedCallback, this)
                );
            },

            paymentAdapterLoaded: function (adapter) {
                this.adapterLoaded(true);
            },

            savePaymentAndCheckout: function () {  
              let config = this.getConfig();            
              if (window.checkoutConfig?.checkoutAgreements?.isEnabled)
               {             
                   if (!agreementsValidator.validate()) { 
                     return false; 
                   }
                }
                this.isPlaceOrderActionAllowed(false);
                this.buttonTitle(this.buttonTitleDisabled);
                let action = setPaymentInformationAction(this.messageContainer, this.getData());
                $.when(action).fail($.proxy(function () {
                    fullScreenLoader.stopLoader();
                    this.isPlaceOrderActionAllowed(true);
                }, this)).done(
                    this.createPaymentSession.bind(this)
                );
                if((config.form_type == 0) && (config.terms_conditions == 1)){              
                   $("#embed-target").removeAttr("style").hide();
                   $('#embed-target').show(); 
                   this.isButtonVisible(false);
                }
             },
            createPaymentSession: function () {

                if (this.isSessionCreated) {
                    return;
                }
                this.isSessionCreated = true;

                let action = createSessionAction(
                    this.getData(),
                    this.messageContainer
                );

                $.when(action).fail($.proxy(function () {
                    // Failed creating session
                    this.isPlaceOrderActionAllowed(true);
                }, this)).done($.proxy(function (session) {
                    // Session creation succeeded
                    if (this.active() && this.adapterLoaded()) {
                        fullScreenLoader.startLoader();

                        let config = this.getConfig();

                        paymentAdapter.configureApi(
                            config.merchant_username,
                            session[0],
                            session[1]
                        );

                        paymentAdapter.showPayment(config.form_type);
                        fullScreenLoader.stopLoader();

                    } else {
                        this.isPlaceOrderActionAllowed(true);
                        this.messageContainer.addErrorMessage({message: "Payment Adapter failed to load"});
                    }
                }, this));
            },

            isCheckoutDisabled: function () {
                return !this.adapterLoaded() || !this.isPlaceOrderActionAllowed();
            },

            getConfig: function () {
                return window.checkoutConfig.payment[this.getCode()];
            },

            errorCallback: function (error) {
                this.isPlaceOrderActionAllowed(true);
                fullScreenLoader.stopLoader();
                alert({
                    content: error.cause + ': ' + error.explanation
                });
            },

            cancelCallback: function () {
                this.isPlaceOrderActionAllowed(true);
                fullScreenLoader.stopLoader();
                alert({
                    content: 'Payment cancelled.'
                });
            },

            completedCallback: function(resultIndicator, sessionVersion) {
                this.resultIndicator = resultIndicator;
                this.sessionVersion = sessionVersion;
                this.isPlaceOrderActionAllowed(true);
                this.placeOrder();
                fullScreenLoader.stopLoader();
            },
            placeOrder: function () {
                let self = this;
                fullScreenLoader.startLoader();

                $.when(
                    placeOrderAction(this.getData(), self.messageContainer)
                ).done(function () {
                        fullScreenLoader.stopLoader();
                        self.getTransactiondetails();
                    })
                .fail(function () {
                    let cartUrl = url.build('checkout/cart');
                    window.location.href = cartUrl;
                    fullScreenLoader.stopLoader();
                });

                return false;
            },
            /**
            * Get transaction details
            */
            getTransactiondetails: function () {
                let requesturl   = url.build('tns/hosted/transactiondetails');
                let quoteId      = quote.getQuoteId();
                jQuery.ajax({
                    url: requesturl,
                    type: 'POST',
                    data: {id : quoteId},
                    dataType: 'json',
                    success: function(data) {
                        let successUrl = url.build('checkout/onepage/success'); 
                        window.location.href = successUrl;
                    }
                });

            },
            /**
             * Get payment method data
             */
            getData: function() {
                let data = this._super();
                data['additional_data'] = this.resultIndicator;
                return data;
            }
        });
    }
);
