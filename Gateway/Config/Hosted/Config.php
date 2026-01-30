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

namespace Mastercard\Mastercard\Gateway\Config\Hosted;

use Mastercard\Mastercard\Gateway\Config\ConfigInterface;

class Config extends \Mastercard\Mastercard\Gateway\Config\Config implements ConfigInterface
{
    public const COMPONENT_URI = '%sstatic/checkout/checkout.min.js';

    /**
     * @var string
     */
    protected $method = 'tns_hosted';

    /**
     * For getting component url
     *
     * @return string
     */
    public function getComponentUrl()
    {
        return sprintf(
            static::COMPONENT_URI,
            $this->getFrontendAreaUrl(),
            $this->getValue('api_version')
        );
    }

    /**
     * @inheritDoc
     */
    public function isVaultEnabled(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isOrderTokenizationEnabled(): bool
    {
        return false;
    }
}
