<?php
/**
 * Copyright (c) 2016-2024 Mastercard
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

namespace Mastercard\Mastercard\Rewrite\Magento\PaymentServicesPaypal\Plugin\Vault;

use Magento\PaymentServicesPaypal\Model\HostedFieldsConfigProvider;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Vault\Model\Method\Vault as VaultMethod;
use Magento\Vault\Model\PaymentTokenManagement;

class Method extends \Magento\PaymentServicesPaypal\Plugin\Vault\Method
{

    protected $tokenManagement;


    /**
     * Hide stored cards payment option on admin checkout page when the customer is new or doesn't have any stored cards
     *
     * @param VaultMethod $subject
     * @param bool $result
     * @param CartInterface $quote
     * @return bool
     */
    public function afterIsAvailable(VaultMethod $subject, bool $result, CartInterface $quote = null ): bool
    {
        if ($subject->getCode() === HostedFieldsConfigProvider::CC_VAULT_CODE) {
            if ($customerId = $quote->getCustomerId()) {
                return !empty($this->tokenManagement->getVisibleAvailableTokens($customerId));
            }
            return false;
        }
        return $result;
    }

}
