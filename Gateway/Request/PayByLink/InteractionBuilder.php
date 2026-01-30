<?php
/**
 * Copyright (c) 2016-2025 Mastercard
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

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Mastercard\Mastercard\Gateway\Config\ConfigFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class InteractionBuilder implements BuilderInterface
{

    public const CHECKOUT_CART_URL = 'checkout/cart';
    public const CALLBACK_URL      = 'tns/hosted/paybylink';
    public const AUTHORIZE         = 'AUTHORIZE';
    public const PURCHASE          = 'PURCHASE';
    public const PAYBYLINK         = 'PAYMENT_LINK';
    public const URL_SECURE        = '_secure';

    /**
     * @var DateTime
     */
    protected $dateTime;

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
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * InteractionBuilder constructor.
     *
     * @param ConfigFactory $configFactory
     * @param UrlInterface $urlInterface
     * @param StoreManagerInterface $storeManager
     * @param DateTime $dateTime
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ConfigFactory $configFactory,
        UrlInterface $urlInterface,
        StoreManagerInterface $storeManager,
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->configFactory = $configFactory;
        $this->urlInterface  = $urlInterface;
        $this->storeManager  = $storeManager;
        $this->dateTime      = $dateTime;
        $this->scopeConfig   = $scopeConfig;
    }

    /**
     * Interaction Builder
     *
     * @param array $buildSubject
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $storeName   = $this->storeManager->getStore()->getName();
        $storeId = $order->getStoreId();
        $orderId = $order->getId();

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $config = $this->configFactory->create();
        $config->setMethodCode($payment->getMethod());
        $url =  $this->urlInterface->getBaseUrl();
        $paymentAction = $config->getValue('gateway_action');
        if ($paymentAction == "authorize") {
            $operation = static::AUTHORIZE;
        } else {
            $operation = static::PURCHASE;
        }
        
        $returnData =  [
            'interaction' => [
                   'merchant' => [
                         'name' => $storeName,
                         'url' => $url
                    ],
                    'operation' => $operation
                    ],
                  'checkoutMode' => static::PAYBYLINK,
                  'paymentLink'=> [
                  'expiryDateTime' =>  $this->getExpiryDate($storeId),
                  'numberOfAllowedAttempts' => $this->getAllowedattempt($storeId)
                               ]
        ];
        if ($config->getValue('enable_merchant_info', $storeId)) {
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
        if ($config->getValue('timeout', $storeId)) {
            $duration   = $config->getValue('timeout_value');
            $returnData = array_replace_recursive($returnData, [
                         'interaction' =>['timeout' => $duration]]);
        }
        $returnData = array_replace_recursive($returnData, [
                     'interaction' =>  [
                        'returnUrl' =>  $url.static::CALLBACK_URL. '?orderId='.$orderId,
                        'cancelUrl' => $url.static::CHECKOUT_CART_URL,
                        'timeoutUrl' =>$url.static::CHECKOUT_CART_URL
                        ]
                     ]);
        
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
    
    /**
     * Get Expiry Date.
     *
     * @param int $storeId
     * @return string
     */
    public function getExpiryDate($storeId = null): string
    {
        // Get expiry configuration values
        $count = (int) $this->scopeConfig->getValue(
            'payment/pay_by_link/expiry_value',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $unit = $this->scopeConfig->getValue(
            'payment/pay_by_link/expiry_unit',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        $utc = new \DateTimeZone('UTC');

        // Absolute max permitted by Mastercard: 3 months
        $maxDate = new \DateTime('now', $utc);
        $maxDate->add(new \DateInterval('P90D'));
        $maxDate->setTime(23, 59, 59);

        $expiryDate = new \DateTime('now', $utc);
        $expiryDate->modify("+{$count} {$unit}");
        if ($expiryDate > $maxDate) {
            $expiryDate = $maxDate;
        }
        return $expiryDate->format('Y-m-d\TH:i:s.000\Z');
    }

    /**
     * Get Allowed Attempt.
     *
     * @param int|string|null $storeId
     *
     * @return int
     */
    public function getAllowedattempt($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/pay_by_link/allowed_attempt',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
