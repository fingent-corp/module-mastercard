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
        'mage/url',
        'mage/translate'
    ],
    function (Component, $, ko, quote, fullScreenLoader, alert, paymentAdapter, setPaymentInformationAction, createSessionAction,placeOrderAction,url,$t) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Mastercard_Mastercard/payment/tns-hosted',
                adapterLoaded: false,
                active: false,
                buttonTitle: null,
                buttonTitleEnabled: $t('Pay'),
                buttonTitleDisabled: $t('Please wait...'),
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

                return this;
            },

            buttonTitleHandler: function (isButtonEnabled) {
                if (isButtonEnabled && this.isActive()) {
                    this.buttonTitle(this.buttonTitleEnabled);
                }
            },

            onActiveChange: function (isActive) {
                $("#embed-target").removeAttr("style").hide();
                if (isActive && !this.adapterLoaded()) {
                    this.loadAdapter();
                    this.savePaymentAndCheckout();
                }
                if(this.isChecked() == 'tns_hosted') {
                  $('#embed-target').show();
                }
            },

            isActive: function () {
                var active = this.getCode() === this.isChecked();
                this.active(active);
                return active;
            },

            loadAdapter: function (sessionId) {
                var config = this.getConfig();
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
                this.isPlaceOrderActionAllowed(false);
                this.buttonTitle(this.buttonTitleDisabled);

                var action = setPaymentInformationAction(this.messageContainer, this.getData());

                $.when(action).fail($.proxy(function () {
                    fullScreenLoader.stopLoader();
                    this.isPlaceOrderActionAllowed(true);
                }, this)).done(
                    this.createPaymentSession.bind(this)
                );
            },

            createPaymentSession: function () {
                var action = createSessionAction(
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

                        var config = this.getConfig();

                        paymentAdapter.configureApi(
                            config.merchant_username,
                            session[0],
                            session[1]
                        );

                        paymentAdapter.showPayment();
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
                var self = this;
                fullScreenLoader.startLoader();

                $.when(
                    placeOrderAction(this.getData(), self.messageContainer)
                ).done(function () {
                        fullScreenLoader.stopLoader();
                        var successUrl = url.build('checkout/onepage/success'); 
                        window.location.href = successUrl;
                    })
                .fail(function () {
                    var cartUrl = url.build('checkout/cart');
                    window.location.href = cartUrl;
                    fullScreenLoader.stopLoader();
                });

                return false;
            },

            /**
             * Get payment method data
             */
            getData: function() {
                var data = this._super();
                data['additional_data'] = this.resultIndicator;
                return data;
            }
        });
    }
);
