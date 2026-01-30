<?php
/**
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

namespace Mastercard\Mastercard\Model\Ui;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Framework\UrlInterface;
use Mastercard\Mastercard\Gateway\Config\ConfigFactory;
use Mastercard\Mastercard\Gateway\Config\Config;
use Mastercard\Mastercard\Gateway\Config\VaultConfigProvider;

class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    protected $componentFactory;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var VaultConfigProvider
     */
    protected $vaultConfigProvider;

    /**
     * @var Json
     */
    private $json;

    /**
     * @param ConfigFactory $configFactory
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param UrlInterface $urlBuilder
     * @param VaultConfigProvider $vaultConfigProvider
     * @param Json $json
     */
    public function __construct(
        ConfigFactory $configFactory,
        TokenUiComponentInterfaceFactory $componentFactory,
        UrlInterface $urlBuilder,
        VaultConfigProvider $vaultConfigProvider,
        Json $json
    ) {
        $this->componentFactory = $componentFactory;
        $this->urlBuilder = $urlBuilder;
        $this->config = $configFactory->create();
        $this->vaultConfigProvider = $vaultConfigProvider;
        $this->json = $json;
    }

    /**
     * Get UI component for token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     * @throws \InvalidArgumentException
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        $this->config->setMethodCode($paymentToken->getPaymentMethodCode());
        $jsonDetails = $this->json->unserialize($paymentToken->getTokenDetails() ?: '{}');

        // Check for merchant ID, if the token merchant ID does not match the payment extension merchant ID
        // then do not render the vault method in hand.
        // Not the best way to decide if a token payment needs to be rendered, need to refactor it
        if (!isset($jsonDetails['merchant_id']) || $this->config->getMerchantId() !== $jsonDetails['merchant_id']) {
            return $this->componentFactory->create([
                'config' => [],
                'name' => null
            ]);
        }

        $vaultConfig = $this->vaultConfigProvider->getConfig($paymentToken->getPaymentMethodCode());

        $config = [
            'code' => $paymentToken->getPaymentMethodCode() . '_vault',
            TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
            TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
        ];

        return $this->componentFactory->create(
            [
                'config' => array_merge($config, $vaultConfig->getVaultConfig()),
                'name' => $vaultConfig->getVaultComponent()
            ]
        );
    }
}
