<?php
/**
 * Copyright (c) 2022-2024 Mastercard
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
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Vault\Api\PaymentTokenManagementInterface;

class TokenDataBuilder implements BuilderInterface
{

    /**
     * @var PaymentTokenManagementInterface
     */
    protected $tokenManagement;

    /**
     * TokenDataBuilder constructor.
     *
     * @param PaymentTokenManagementInterface $tokenManagement
     */
    public function __construct(
        PaymentTokenManagementInterface $tokenManagement
    ) {
        $this->tokenManagement = $tokenManagement;
    }

    /**
     * Token data builder
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment   = $paymentDO->getPayment();
        $publichash   = $payment->getAdditionalInformation('public_hash');
        $customerid   = $payment->getAdditionalInformation('customer_id');
        $paymentToken = $this->tokenManagement->getByPublicHash($publichash, $customerid);
        return [
            'sourceOfFunds' => [
                'token' => $paymentToken->getGatewayToken()
            ],
        ];
    }
}
