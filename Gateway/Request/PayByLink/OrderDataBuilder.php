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

namespace Mastercard\Mastercard\Gateway\Request\PayByLink;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use Mastercard\Mastercard\Gateway\Config\ConfigFactory;
use Magento\Framework\UrlInterface;

class OrderDataBuilder implements BuilderInterface
{

    public const WEB_HOOK_RESPONSE_URL = 'tns/webhook/response';
    
    /**
     * @var ConfigFactory
     */
    protected $configFactory;
    
    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * OrderDataBuilder constructor.
     * @param ConfigFactory $configFactory
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        ConfigFactory $configFactory,
        UrlInterface $urlInterface
    )
    {
        $this->configFactory = $configFactory;
        $this->urlInterface  = $urlInterface;
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

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $config = $this->configFactory->create();
        $config->setMethodCode($payment->getMethod());
        $order   = $payment->getOrder();
        $total   = $order->getBaseGrandTotal();
        $orderId = $paymentDO->getOrder()->getOrderIncrementId();
        $url     =  $this->urlInterface->getBaseUrl();

        return [
            'order' => [
                'amount' => sprintf('%.2F', $total),
                'currency' => $order->getOrderCurrencyCode(),
                'id' => $orderId,
                'notificationUrl' => $url.static::WEB_HOOK_RESPONSE_URL,
                'description'=> "Ordered goods"

            ]
        ];
    }
}
