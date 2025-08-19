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
/*browser:true*/
/*global define*/
define([
    'jquery',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'Magento_Checkout/js/action/set-payment-information',
    'mage/url',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, VaultComponent, $t, alert,setPaymentInformationAction,url,modal, fullScreenLoader) {
    'use strict';

     let options = {
        type: 'slide',
        title: $.mage.__('Process Secure Payment'),
        buttons: [],
        clickableOverlay: false,
    };
    
    return VaultComponent.extend({
        defaults: {
            template: 'Mastercard_Mastercard/payment/hpf-vault',
            active: false,
            isConfigured: false,
            session: {},
            imports: {
                onActiveChange: 'active'
            }
        },

        initObservable: function () {
            this._super()
                .observe([
                    'active',
                    'isConfigured',
                    'session',
                    'useCcv'
                ]);
            return this;
        },

        /**
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.cc_number;
        },

        /**
         * Get expiration date
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.details.cc_expr_month + '/' + this.details.cc_expr_year;
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details.type;
        },

        /**
         * @returns {String}
         */
        getToken: function () {
            return this.publicHash;
        },

        getCvvImageHtml: function() {
            return '<img src="' + this.getCvvImageUrl()
                + '" alt="' + $t('Card Verification Number Visual Reference')
                + '" title="' + $t('Card Verification Number Visual Reference')
                + '" />';
        },

        getCvvImageUrl: function() {
            return window.checkoutConfig.payment.ccform.cvvImageUrl[this.getCode()];
        },

        onActiveChange: function (isActive) {
            if (isActive && this.useCcv()) {
                this.loadAdapter();
            }
        },

        errorMap: function () {
            return {
                'securityCode': $t('Invalid security code'),
            };
        },

        paymentAdapterLoaded: function () {
            PaymentSession.configure({
                fields: {
                    card: {
                        securityCode: '#' + this.getId() + '_cvv',
                    }
                },
                frameEmbeddingMitigation: ['x-frame-options'],
                callbacks: {
                    initialized: function () {
                        this.isConfigured(true);
                        this.isPlaceOrderActionAllowed(true);
                    }.bind(this),
                    formSessionUpdate: function (response) {
                        if (response.status === "fields_in_error") {
                            this.handleFieldErrors(response.errors);
                            
                        }
                        if (response.status === "ok") {
                          this.handleValidSession(response.session);
                        }
                    }.bind(this)
                },
                interaction: {
                    displayControl: {
                        formatCard: "EMBOSSED",
                        invalidFieldCharacters: "REJECT"
                    }
                }
            }, this.getId());
        },
        handleFieldErrors: function (errors) {
            if (response.errors) {
                let errors = this.errorMap(),
                    message = "";
                for (let err in response.errors) {
                    if (!response.errors.hasOwnProperty(err)) {
                        continue;
                    }
                    message += '<p>' + errors[err] + '</p>';
                }
                alert({
                    content: message,
                    closed: $.proxy(function () {
                        this.isPlaceOrderActionAllowed(true);
                    }, this)
                });
                this.isPlaceOrderActionAllowed(true);
            }
        },
        handleValidSession: function (session) {
            this.session(session);
            const token = this.getToken();
            if (this.is3DsEnabled()) {
                setPaymentInformationAction(this.messageContainer, this.getData());
                fullScreenLoader.startLoader();
                this.ThreedsVaultCheck(token);
            } else if (this.is3Ds2Enabled()) {
                fullScreenLoader.startLoader();
                this.Threeds2VaultCheck(token);
            } else {
                this.isPlaceOrderActionAllowed(true);
                this.placeOrder();
            }
    },

        loadAdapter: function () {
            if (this.isConfigured()) {
                return;
            }
            this.isPlaceOrderActionAllowed(false);
            require([this.component_url], this.paymentAdapterLoaded.bind(this));
        },
        ThreedsVaultCheck: function (token) {
            let vaultcheckurl = url.build('tns/threedsecure/vaultcheck');
            jQuery.ajax({
                url: vaultcheckurl,
                type: 'POST',
                data: {"token": token},
                dataType: 'json',
                success: function(data) {
                if(data.result == "Y"){
                 let urlWithParams = url.build('tns/threedsecure/vaultform') + '?acsUrl=' + encodeURIComponent(data.acsurl) + '&paReq=' + encodeURIComponent(data.pareq);
                 this.modal        = $("div[data-role='tns-threedsecure-modal']");
                 this.modal.css({
                    height: '100vh'                
                    });
                    $("iframe[data-role='tns-threedsecure-iframe']").attr('src',urlWithParams); 
                    $("iframe[data-role='tns-threedsecure-iframe']").css({
                     'width': '100%',
                     'height': '100vh'                               
                 }); 
                 modal(options,this.modal);
                 this.modal.modal('openModal');
                 fullScreenLoader.stopLoader();
                 window.tnsThreeDSecureClose =  $.proxy(function () {
                    this.modal.modal('closeModal');
                    this.onComplete();
                 }, this);
              }else{
                this.onComplete();
              }
         }.bind(this),
            error: function (data) {
                this.isPlaceOrderActionAllowed(false);
                this.modal.modal('closeModal');
            },
          });
        },
        Threeds2VaultCheck: function (token) {
            let threedsurl = url.build('tns/threedsecurev2/vaultauthentication');
            jQuery.ajax({
            url: threedsurl,
            type: 'POST',
            data: {"token": token},
            dataType: 'json',
            success: function(data) {
                if(data.status == "AUTHENTICATION_AVAILABLE"){
                    $("div[data-role='tns-threedsecure-v2-container']").html(data.html);
                eval($('#initiate-authentication-script').text());
                jQuery.ajax({
                    url: url.build('tns/threedsecurev2/vaultauthenticatepayer'),
                    type: 'POST',
                    data: {token: token , 
                            browserDetails: {
                                javaEnabled: navigator.javaEnabled(),
                                language: navigator.language,
                                screenHeight: window.screen.height,
                                screenWidth: window.screen.width,
                                timeZone: new Date().getTimezoneOffset(),
                                colorDepth: screen.colorDepth,
                                acceptHeaders: 'application/json',
                                '3DSecureChallengeWindowSize': 'FULL_SCREEN'
                                }},
                            dataType: 'json',
                            success: function(res) {
                                if(res.html){
                                this.modal =  $("div[data-role='tns-threedsecure-v2-modal']");
                                this.modal.css({
                                    height: '100%',
                                    width: '100%'
                                });
                                modal(options,this.modal);
                                this.modal.html(res.html);
                                eval($('#authenticate-payer-script').text());
                                this.modal.modal('openModal');
                                fullScreenLoader.stopLoader();
                                window.treeDS2Completed =  $.proxy(function () {
                                    this.modal.modal('closeModal');
                                    this.onComplete();
                                  }, this);
                                }else{
                                    this.onComplete();
                                }
                    }.bind(this),
                    error: function (data) {
                        this.isPlaceOrderActionAllowed(false);
                    },
                  });
               }else{
                    this.onComplete();

                }
               
                }.bind(this),
                error: function (data) {
                    this.isPlaceOrderActionAllowed(false);
                    this.modal.modal('closeModal');
                },
            });

        },
        isActive: function () {
            let active = this.getId() === this.isChecked();
            this.active(active);
            return active;
        },
        onComplete: function () {
            this.isPlaceOrderActionAllowed(true);
            this.placeOrder();
        },
        savePayment: function () {
            if (this.useCcv()) {
                this.isPlaceOrderActionAllowed(false);
                PaymentSession.updateSessionFromForm('card', undefined, this.getId());
                return this;
            } else {
                this.placeOrder();
            }
        },
        getConfig: function () {
                return window.checkoutConfig.payment['tns_hpf'];
            },

        is3DsEnabled: function () {
                return this.getConfig()['three_d_secure_version'] === 1;
            },
        is3Ds2Enabled: function () {
                return this.getConfig()['three_d_secure_version'] === 2;
            },
        getData: function () {
            let data = this._super();

            let session = this.session();
            data['additional_data']['session'] = session['id'];

            return data;
        }
    });
});
