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

namespace Mastercard\Mastercard\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Mastercard\Mastercard\Helper\DownloadCount;

class QuoteReferencesBuilder implements BuilderInterface
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
        $this->downloadCount      = $downloadCount;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var Quote $quote */
        $quote   = $paymentDO->getPayment()->getQuote();
        $storeId = $quote->getStoreId();
        if ($quote->getReservedOrderId() == null) {
            $this->refreshOrderIdReservation($quote);
        }
        $orderprefix =  $this->downloadCount->getOrderPrefix($storeId);
        $orderId     = $orderprefix ? $orderprefix.$quote->getReservedOrderId(): $quote->getReservedOrderId();
        $txnId       = uniqid(sprintf('%s-', $orderId));

        return [
            'order' => [
                'id' => $orderId,
                'reference' => $orderId,
            ],
            'transaction' => [
                'reference' => $txnId,
            ]
        ];
    }

    /**
     * Refresh Order Id Reservation
     *
     * If quote already used in payment gateway and failed
     * then new session should contain another order id
     *
     * @param Quote $quote
     */
    private function refreshOrderIdReservation(Quote $quote)
    {
        $quote->setReservedOrderId('');
        $quote->reserveOrderId();
    }
}
