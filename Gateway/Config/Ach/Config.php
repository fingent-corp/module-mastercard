<?php
/**
 * Copyright (c) 2016-2022 Mastercard
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

namespace Mastercard\Mastercard\Gateway\Config\Ach;

use Magento\Framework\Exception\NoSuchEntityException;
use Mastercard\Mastercard\Api\MethodInterface;
use Mastercard\Mastercard\Gateway\Config\ConfigInterface;

class Config extends \Mastercard\Mastercard\Gateway\Config\Config implements ConfigInterface
{
    /**
     * @var string
     */
    protected $method = 'mpgs_ach';

    /**
     * Checking if the vault enabled or not.
     *
     * @return bool
     */
    public function isVaultEnabled(): bool
    {
        return false;
    }

    /**
     * Checking if the order token is enabled or not.
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isOrderTokenizationEnabled(): bool
    {
        $storeId = $this->storeManager->getStore()->getId();
        $paymentAction = $this->getValue('mapped_payment_action', $storeId);
        if ($paymentAction === MethodInterface::MAPPED_ACTION_ORDER_VERIFY) {
            return true;
        }

        return (bool)$this->getValue('add_token_to_order');
    }
}
