/*
 * Copyright (c) 2022-2024 Mastercard
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
define([
    'uiComponent',
    'Magento_Checkout/js/model/quote'

], function (Component,quote) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();
            // Observe shipping method changes
            quote.shippingMethod.subscribe(function () { 
              let selectedPaymentMethod = quote.paymentMethod();
              if ((selectedPaymentMethod) && (selectedPaymentMethod.method == "tns_hosted")) {
                      location.reload();
                }
            });

        }
    });
});

