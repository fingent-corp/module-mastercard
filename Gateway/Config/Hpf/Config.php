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

namespace Mastercard\Mastercard\Gateway\Config\Hpf;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Mastercard\Mastercard\Gateway\Config\ConfigInterface;
use Mastercard\Mastercard\Model\Ui\Hpf\ConfigProvider;

class Config extends \Mastercard\Mastercard\Gateway\Config\Config implements ConfigInterface
{
    public const COMPONENT_URI = '%sform/version/%s/merchant/%s/session.js';

    /**
     * @var string
     */
    protected $method = 'tns_hpf';

    /**
     * Checking if the vaukt is enabled or not
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isVaultEnabled(): bool
    {
        $storeId = $this->storeManager->getStore()->getId();
        $vaultPayment = $this->getVaultPayment();
        return $vaultPayment->isActive($storeId);
    }

    /**
     * Checking tokenization is enabled or not
     *
     * @return bool
     *
     * @throws NoSuchEntityException
     */
    public function isOrderTokenizationEnabled(): bool
    {
        $storeId = $this->storeManager->getStore()->getId();
        $paymentAction = $this->getValue('payment_action', $storeId);
        if ($paymentAction === MethodInterface::ACTION_ORDER) {
            return true;
        }

        return (bool)$this->getValue('add_token_to_order');
    }

    /**
     * For getting the vault payment
     *
     * @return MethodInterface
     * @throws LocalizedException
     */
    protected function getVaultPayment()
    {
        return $this->paymentDataHelper->getMethodInstance(ConfigProvider::CC_VAULT_CODE);
    }

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
            $this->getValue('api_version'),
            $this->getMerchantId()
        );
    }

    /**
     * Getting vault config values
     *
     * @param int $storeId
     * @return array
     */
    public function getVaultConfig($storeId = null)
    {
        return [
            'component_url' => $this->getComponentUrl(),
            'useCcv' => (bool) $this->getValue('vault_ccv', $storeId),
        ];
    }

    /**
     * For getting vault components
     *
     * @return string
     */
    public function getVaultComponent()
    {
        return 'Mastercard_Mastercard/js/view/payment/method-renderer/hpf-vault';
    }
}
