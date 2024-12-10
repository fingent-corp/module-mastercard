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

namespace Mastercard\Mastercard\Gateway\Request\Hosted;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Mastercard\Mastercard\Gateway\Config\ConfigFactory;
use Magento\Framework\UrlInterface;

class InteractionBuilder implements BuilderInterface
{

    const CHECKOUT_CART_URL = 'checkout/cart';
    const CALLBACK_URL      = 'tns/hosted/callback';
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
    public function __construct(ConfigFactory $configFactory,UrlInterface $urlInterface)
    {
        $this->configFactory = $configFactory;
        $this->urlInterface    = $urlInterface;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $config = $this->configFactory->create();
        $config->setMethodCode($payment->getMethod());
        
        $returnData =  [
            'interaction' => [
                   'merchant' => [
                         'name' => $config->getValue('form_title', $storeId)
                    ],
                    'displayControl' => [
                         'customerEmail' => 'HIDE',
                         'billingAddress' => 'HIDE',
                         'shipping' => 'HIDE',
                    ],
                    'operation' => 'NONE'
             ]
        ];
        
        if ($config->getValue('form_type', $storeId) == 1) {
            $returnData = array_replace_recursive($returnData, [
                         'interaction' =>  [
				'returnUrl' => $this->urlInterface->getUrl(static::CALLBACK_URL, ['_secure' => true]),
				'cancelUrl' => $this->urlInterface->getUrl(static::CHECKOUT_CART_URL, ['_secure' => true]),
				'timeoutUrl' => $this->urlInterface->getUrl(static::CHECKOUT_CART_URL, ['_secure' => true])
            
                         ]
                      ]);
        }
        
        return $returnData;
    }
}
