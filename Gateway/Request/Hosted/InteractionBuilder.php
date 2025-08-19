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
use Magento\Store\Model\StoreManagerInterface;

class InteractionBuilder implements BuilderInterface
{

    const CHECKOUT_CART_URL = 'checkout/cart';
    const CALLBACK_URL      = 'tns/hosted/callback';
    const AUTHORIZE         = 'AUTHORIZE';
    const PURCHASE          = 'PURCHASE';
    const URL_SECURE = '_secure';
    /**
    * @var ConfigFactory
    */
    protected $configFactory;
    
    /**
    * @var UrlInterface
    */
    protected $urlInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * InteractionBuilder constructor.
     * @param ConfigFactory $configFactory
     * @param UrlInterface $urlInterface
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConfigFactory $configFactory,
        UrlInterface $urlInterface,
        StoreManagerInterface $storeManager
    ) {
        $this->configFactory = $configFactory;
        $this->urlInterface  = $urlInterface;
        $this->storeManager  = $storeManager;
    }

    /**
    * @inheritDoc
    */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        $storeName   = $this->storeManager->getStore()->getName();

        $storeId = $order->getStoreId();

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $config = $this->configFactory->create();
        $config->setMethodCode($payment->getMethod());

        $paymentAction = $config->getValue('payment_action');
        if ($paymentAction == "authorize") {
            
            $operation = static::AUTHORIZE;
            
        }else {
            $operation = static::PURCHASE;

        }
        
        $returnData =  [
            'interaction' => [
                   'merchant' => [
                         'name' => $storeName
                    ],
                    'displayControl' => [
                         'customerEmail' => 'HIDE',
                         'billingAddress' => 'HIDE',
                         'shipping' => 'HIDE',
                    ],
                    'operation' => $operation             ]
        ];

        if (($config->getValue('form_type', $storeId) == 1)&&($config->getValue('enable_merchant_info', $storeId))) {

            $merchantInfo=[
                'name' => $config->getValue('merchant_name', $storeId),
                'address' =>[
                            'line1'    => $config->getValue('address_line1', $storeId),
                            'line2'    => $config->getValue('address_line2', $storeId),
                            'line3'    => $config->getValue('postcode', $storeId),
                            'line4'    => $config->getValue('country', $storeId),
                         ]
            ];

            if ($config->getValue('email', $storeId)) {
               $merchantInfo['email'] = $config->getValue('email', $storeId);
            }

            if ($config->getValue('phone', $storeId)) {
               $merchantInfo['phone'] = $config->getValue('phone', $storeId);
            }

            if ($config->getValue('logo_file', $storeId)) {

                $merchantInfo['logo'] = $this->getMediaUrl($config->getValue('logo_file', $storeId));

            }

            $returnData = array_replace_recursive($returnData, [
                         'interaction' =>['merchant' => $merchantInfo]]);

        }
        
        if ($config->getValue('form_type', $storeId) == 1) {
            $returnData = array_replace_recursive($returnData, [
                         'interaction' =>  [
                'returnUrl' => $this->urlInterface->getUrl(static::CALLBACK_URL, [static::URL_SECURE => true]),
                'cancelUrl' => $this->urlInterface->getUrl(static::CHECKOUT_CART_URL, [static::URL_SECURE => true]),
                'timeoutUrl' => $this->urlInterface->getUrl(static::CHECKOUT_CART_URL, [static::URL_SECURE => true])
            
                         ]
                      ]);
        }
        
        return $returnData;
    }

    /**
     * Get full media URL for a given file path.
     *
     * @param string $filePath
     * @return string
     */
    private function getMediaUrl($filePath)
    {
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $filePath = 'mastercard/logs/' . ltrim($filePath, '/');

        return $mediaUrl . $filePath;
    }

}
