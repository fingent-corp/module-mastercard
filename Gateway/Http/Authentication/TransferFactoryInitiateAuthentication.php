<?php
/**
 * Copyright (c) 2016-2020 Mastercard
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

namespace Mastercard\Mastercard\Gateway\Http\Authentication;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Mastercard\Mastercard\Gateway\Http\TransferFactory;
use Mastercard\Mastercard\Helper\DownloadCount;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Mastercard\Mastercard\Gateway\Config\Config;

class TransferFactoryInitiateAuthentication extends TransferFactory
{

    /**
     * @var DownloadCount
     */
    protected $downloadCount;

    /**
     * @var Config
     */
    protected $config;
    
    /**
     * @var TransferBuilder
     */
    protected $transferBuilder;

    /**
     * @param DownloadCount $downloadCount
     * @param Config $config
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        DownloadCount $downloadCount,
        Config $config,
        TransferBuilder $transferBuilder
    ) {
        parent::__construct($config, $transferBuilder);
        $this->downloadCount  = $downloadCount;
    }
    
    /**
     * Get initiate authentication request url
     *
     * @param PaymentDataObjectInterface $payment
     * @return string
     */
    protected function getUri(PaymentDataObjectInterface $payment)
    {
        $storeId = $payment->getOrder()->getStoreId();
        $orderprefix =  $this->downloadCount->getOrderPrefix($storeId);
        $orderId = $orderprefix
                   ? $orderprefix.$payment->getOrder()->getOrderIncrementId()
                   : $payment->getOrder()->getOrderIncrementId();
        $transactionId = $this->request['transaction']['reference']
            ?? $payment->getPayment()->getAdditionalInformation('auth_init_transaction_id');
        if (!$transactionId || explode('-', $transactionId)[0] !== $orderId) {
            $transactionId = $this->createTxnId($payment);
        }

        return $this->getGatewayUri($storeId) . 'order/' . $orderId . '/transaction/' . $transactionId;
    }
}
