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

namespace Mastercard\Mastercard\Gateway\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Mastercard\Mastercard\Gateway\Response\ThreeDSecure\CheckHandler;
use Mastercard\Mastercard\Gateway\Config\ConfigInterface;

class VerificationStrategyCommand implements CommandInterface
{
    const PROCESS_3DS_RESULT = '3ds_process';
    const CREATE_TOKEN = 'create_token';
    const CREATE_ORDER_TOKEN = 'tokenize';
    const METHOD_VERIFY = 'order';

    /**
     * @var Command\CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var string
     */
    private $successCommand;

    /**
     * @var State
     */
    private $state;

    /**
     * @param State $state
     * @param Command\CommandPoolInterface $commandPool
     * @param ConfigInterface $config
     * @param string $successCommand
     */
    public function __construct(
        State $state,
        Command\CommandPoolInterface $commandPool,
        ConfigInterface $config,
        $successCommand = ''
    ) {
        $this->state = $state;
        $this->commandPool = $commandPool;
        $this->config = $config;
        $this->successCommand = $successCommand;
    }

    /**
     * @param PaymentDataObjectInterface $paymentDO
     * @return bool
     */
    public function isThreeDSSupported(PaymentDataObjectInterface $paymentDO)
    {
        /** @var Payment $paymentInfo */
        $paymentInfo = $paymentDO->getPayment();
        
        $payment_action = $this->config->getValue('payment_action');
        
        //Don't use 3DS for verify payment action
        if($payment_action == static::METHOD_VERIFY){
             return false;
         }

        // Don't use 3DS in admin
        if ($this->state->getAreaCode() === Area::AREA_ADMINHTML) {
            return false;
        }

        // Don't use 3DS with pre-authorized transactions
        if ($paymentInfo->getAuthorizationTransaction()) {
            return false;
        }

        $isEnabled = $this->config->getValue('three_d_secure') === '1';
        if (!$isEnabled) {
            return false;
        }

        $data = $paymentInfo->getAdditionalInformation(CheckHandler::THREEDSECURE_CHECK);

        if (isset($data['veResEnrolled'])) {
            if ($data['veResEnrolled'] == "N") {
                return false;
            }
            if ($data['veResEnrolled'] == "Y") {
                return true;
            }
        }

        return true;
    }

    /**
     * Executes command basing on business object
     *
     * @param array $commandSubject
     * @return null|Command\ResultInterface
     */
    public function execute(array $commandSubject)
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($commandSubject);

        /** @var Payment $paymentInfo */
        $paymentInfo = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($paymentInfo);

        $extensionAttributes = $paymentInfo->getExtensionAttributes();
        $paymentToken        = $extensionAttributes->getVaultPaymentToken();

        $order       = $paymentInfo->getOrder();
        $order_token = $order->getMastercardPaymentToken() ? $order->getMastercardPaymentToken() : NULL;

        if ($this->isThreeDSSupported($paymentDO)) {
            $this->commandPool
                ->get(static::PROCESS_3DS_RESULT)
                ->execute($commandSubject);
        }

        // Vault enabled from configuration
        // 'Save for later use' checked on frontend
        if ($this->config->isVaultEnabled() &&
            $paymentInfo->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE) && (!$this->getVaultToken($paymentInfo))) {
            $this->commandPool
                ->get(static::CREATE_TOKEN)
                ->execute($commandSubject);
        }

        if ($this->config->isOrderTokenizationEnabled() && !$this->getVaultToken($paymentInfo) && !$order_token) {
            $this->commandPool
                ->get(static::CREATE_ORDER_TOKEN)
                ->execute($commandSubject);
        }

        $this->commandPool
            ->get($this->successCommand)
            ->execute($commandSubject);

        return null;
    }
    
    /**
    * Returns paymenttoken
    *
    * @param $paymentInfo
    * @return string 
    */
    public function getVaultToken($paymentInfo){

        $extensionAttributes = $paymentInfo->getExtensionAttributes();
        $paymentToken        = $extensionAttributes->getVaultPaymentToken() ? $extensionAttributes->getVaultPaymentToken():NULL ;
        return $paymentToken;

    }
}
