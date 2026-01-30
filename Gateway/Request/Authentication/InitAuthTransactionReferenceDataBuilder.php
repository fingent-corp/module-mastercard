<?php
/**
 * Copyright (c) 2016-2021 Mastercard
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

namespace Mastercard\Mastercard\Gateway\Request\Authentication;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Mastercard\Mastercard\Helper\DownloadCount;

class InitAuthTransactionReferenceDataBuilder implements BuilderInterface
{

    /**
     * @var DownloadCount
     */
    protected $downloadCount;
    
     /**
     * @param DownloadCount $downloadCount
     */
    public function __construct(
         DownloadCount $downloadCount
    ) {
        $this->downloadCount  = $downloadCount;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $order     = $paymentDO->getOrder();
        $payment   = $paymentDO->getPayment();
        $storeId   = $order->getStoreId();
        $orderprefix =  $this->downloadCount->getOrderPrefix($storeId);
        $orderId =   $orderprefix ? $orderprefix.$order->getOrderIncrementId(): $order->getOrderIncrementId();
        $txnId = $payment->getAdditionalInformation('auth_init_transaction_id');
        if (!$txnId || explode('-', $txnId)[0] !== $orderId) {
            $txnId = uniqid(sprintf('%s-', $orderId));
        }

        return [
            'transaction' => [
                'reference' => $txnId
            ]
        ];
    }
}
