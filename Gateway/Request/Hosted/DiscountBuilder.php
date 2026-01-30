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

namespace Mastercard\Mastercard\Gateway\Request\Hosted;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Mastercard\Mastercard\Gateway\Config\ConfigFactory;

class DiscountBuilder implements BuilderInterface
{
    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * DiscountBuilder constructor.
     * @param ConfigFactory $configFactory
     */
    public function __construct(ConfigFactory $configFactory)
    {
        $this->configFactory = $configFactory;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment   = $paymentDO->getPayment();
        $order     = $paymentDO->getOrder();

        $config    = $this->configFactory->create();
        $config->setMethodCode($payment->getMethodInstance()->getCode());

        if ($config->isSendLineItems($order->getStoreId())) {
            $discountAmount = ($payment->getQuote()->getSubtotal() - $payment->getQuote()->getSubtotalWithDiscount());

            if ($discountAmount > 0) {
                return [
                    'order' => [
                        'discount' => [
                            'amount' => sprintf('%.2F', $discountAmount)
                        ]
                    ]
                ];
            } else {
                return [];
            }
        } else {
            return [];
        }
    }
}
