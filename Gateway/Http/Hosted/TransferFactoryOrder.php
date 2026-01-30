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

namespace Mastercard\Mastercard\Gateway\Http\Hosted;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Mastercard\Mastercard\Gateway\Http\Client\Rest;
use Mastercard\Mastercard\Gateway\Http\TransferFactory;
use Mastercard\Mastercard\Helper\DownloadCount;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Mastercard\Mastercard\Gateway\Config\Config;

class TransferFactoryOrder extends TransferFactory
{
    /**
     * @var string
     */
    protected $httpMethod = Rest::GET;

    /**
     * @var Config
     */
    protected $config;
    
    /**
     * @var TransferBuilder
     */
    protected $transferBuilder;
    
    /**
     * @var DownloadCount
     */
    protected $downloadCount;

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
        $this->downloadCount = $downloadCount;
    }

    /**
     * Get request url
     *
     * @param PaymentDataObjectInterface $payment
     * @return string
     */
    protected function getUri(PaymentDataObjectInterface $payment)
    {
        $order   = $payment->getOrder();
        $method  = $payment->getPayment()->getMethod();
        $storeId = $order->getStoreId();
        $orderId = $order->getOrderIncrementId();
        $orderprefix = $this->downloadCount->getOrderPrefix($storeId);
        $orderId   = $orderprefix
                   ? $orderprefix.$orderId
                   : $orderId;
        return $this->getGatewayUri($storeId) . 'order/' . $orderId;
    }
}
