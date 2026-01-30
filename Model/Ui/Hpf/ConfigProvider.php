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

namespace Mastercard\Mastercard\Model\Ui\Hpf;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Mastercard\Mastercard\Gateway\Config\Config;

class ConfigProvider implements ConfigProviderInterface
{
    public const METHOD_CODE = 'tns_hpf';
    public const CC_VAULT_CODE = 'tns_hpf_vault';
    public const METHOD_VERIFY = 'order';
    public const URL_SECURE = '_secure';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param Config $config
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Config $config,
        UrlInterface $urlBuilder
    ) {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $paymentaction = $this->config->getValue('payment_action');
        $threedsecureVersion = ($paymentaction == static::METHOD_VERIFY)
            ? 0
            : (int) $this->config->getValue('three_d_secure');
        $cardtypes            = $this->config->getValue('supported_cards');
        $supportedCards      = $cardtypes ? explode(",", $cardtypes) : null;

        return [
            'payment' => [
                self::METHOD_CODE => [
                    'merchant_username' => $this->config->getMerchantId(),
                    'component_url' => $this->config->getComponentUrl(),
                    'debug' => (bool)$this->config->getValue('debug'),
                    'three_d_secure_version' => $threedsecureVersion,
                    'ccVaultCode' => static::CC_VAULT_CODE,
                    'supported_cardtypes' => $supportedCards,
                    'check_url' => $this->urlBuilder->getUrl(
                        'tns/threedsecure/check',
                        [
                            'method' => 'hpf',
                            static::URL_SECURE => 1,
                        ]
                    ),
                    'threedsecure_v2_initiate_authentication_url' => $this->urlBuilder->getUrl(
                        'tns/threedsecureV2/initiateAuthentication',
                        [static::URL_SECURE => 1]
                    ),
                    'threedsecure_v2_authenticate_payer_url' => $this->urlBuilder->getUrl(
                        'tns/threedsecureV2/authenticatePayer',
                        [static::URL_SECURE => 1]
                    ),
                ],
            ],
        ];
    }
}
