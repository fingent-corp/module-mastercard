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

namespace Mastercard\Mastercard\Gateway\Http\ThreeDSecure;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Mastercard\Mastercard\Gateway\Http\Client\Rest;
use Mastercard\Mastercard\Gateway\Http\TransferFactory;

class TransferFactoryProcess extends TransferFactory
{
    /**
     * @var string
     */
    protected $httpMethod = Rest::POST;

    /**
     * @param PaymentDataObjectInterface $payment
     * @return string
     */
    protected function getUri(PaymentDataObjectInterface $payment)
    {
        $threeDSId = $payment->getPayment()->getAdditionalInformation('3DSecureId');
        $storeId = $payment->getOrder()->getStoreId();
        if (!$threeDSId) {
            throw new \InvalidArgumentException("3D-Secure ID not provided");
        }
        return $this->getGatewayUri($storeId) . '3DSecureId/' . $threeDSId;
    }
}
