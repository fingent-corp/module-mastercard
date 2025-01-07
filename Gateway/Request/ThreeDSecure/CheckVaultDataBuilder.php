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

namespace Mastercard\Mastercard\Gateway\Request\ThreeDSecure;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Framework\UrlInterface;
use Mastercard\Mastercard\Model\Ui\Hpf\ConfigProvider;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenManagement;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Class CheckVaultDataBuilder
 * @package Mastercard\Mastercard\Gateway\Request\ThreeDSecure
 */
class CheckVaultDataBuilder implements BuilderInterface
{
    public const PAGE_GENERATION_MODE = 'CUSTOMIZED';
    public const RESPONSE_URL = 'tns/threedsecure/response';
    public const RESPONSE_SID_PARAMETER = 'tns_sid';

    /**
     * @var UrlInterface
    */
    protected $urlHelper;

    /**
     * @var PaymentTokenManagementInterface
    */
    protected $tokenManagement;

    /**
     * checkdatabuilder constructor.
     * @param UrlInterface $urlHelper
     * @param PaymentTokenManagementInterface $tokenManagement
    */

    public function __construct(
       UrlInterface $urlHelper,
       PaymentTokenManagementInterface $tokenManagement
    )
    {
        $this->urlHelper       = $urlHelper;
        $this->tokenManagement = $tokenManagement;

    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     *
     * @throws LocalizedException
     */
    public function build(array $buildSubject)
    {
        $paymentDO    = SubjectReader::readPayment($buildSubject);
        $order        = $paymentDO->getOrder();
        $payment      = $paymentDO->getPayment();
        $publicHash   = $payment->getAdditionalInformation('public_hash');
        $customerId   = $payment->getAdditionalInformation('customer_id');
        $paymentToken = $this->tokenManagement->getByPublicHash($publicHash, $customerId);
        $tokenValue   = $paymentToken->getGatewayToken();
     
        $data = [
            '3DSecure' => [
                'authenticationRedirect' => [
                    'pageGenerationMode' => static::PAGE_GENERATION_MODE,
                    'responseUrl' => $this->urlHelper->getUrl(static::RESPONSE_URL),
                ]
            ],
            'order' => [
                'amount' => sprintf('%.2F', SubjectReader::readAmount($buildSubject)),
                'currency' => $order->getCurrencyCode(),
            ],
        ];

        $data = array_merge($data, [
                'sourceOfFunds' => [
                    'token' => $tokenValue
                ]
            ]);
    

        return $data;
    }
}
