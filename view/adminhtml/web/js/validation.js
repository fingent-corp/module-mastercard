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
/*jshint jquery:true browser:true*/
require([
    'jquery',
    'mage/backend/validation'
], function ($) {
    'use strict';
    $.validator.addMethod('validate-json', function (value) {
        try {
            JSON.parse(value);
        } catch (err) {
            console.error('Invalid JSON:', err); 
            return false;
        }
        return true
    }, 'Invalid JSON string.');
    $.validator.addMethod('validate-expiry-limit', function (value, element) {
        var unitField = $('[name$="[expiry_unit][value]"]');
        var unit = unitField.val(); 
        var val = parseInt(value);
        if (unit === 'months' && val > 3) {
                $.validator.messages['validate-expiry-limit'] = $.mage.__('For Months, the value cannot exceed 3.');
                return false;
        }
        if (unit === 'days' && val > 90) {
            $.validator.messages['validate-expiry-limit'] = $.mage.__('For Days, the value cannot exceed 90.');
            return false;
        }
        if (unit === 'hours' && val > 2160) {
            $.validator.messages['validate-expiry-limit'] = $.mage.__('For hours, the value cannot exceed 2160.');
            return false;
        }
        return true;
    }, $.mage.__('The value exceeds the allowed limit for the selected unit.'));
});
