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

namespace Mastercard\Mastercard\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\Curl;
use Mastercard\Mastercard\Gateway\Http\TransferFactory;
use Mastercard\Mastercard\Gateway\Config\Config;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Payment\Helper\Data as PaymentHelper;
use Mastercard\Mastercard\Gateway\Config\ConfigFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Data extends AbstractHelper
{

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var TransferFactory
     */
    private $transferFactory;
    
    /**
     * @var Config
     */
    protected $config;
    
    /**
     * @var Curl
     */
    protected $curl;
    
    /**
     * @var Json
     */
    protected $json;
    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;
    
    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * Data Constructor
     *
     * @param Context $context
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     * @param TransferFactory $transferFactory
     * @param Config $config
     * @param Json $json
     * @param AddressRenderer $addressRenderer
     * @param PaymentHelper $paymentHelper
     * @param ConfigFactory $configFactory
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Context $context,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        TransferFactory $transferFactory,
        Config $config,
        Json $json,
        AddressRenderer $addressRenderer,
        PaymentHelper $paymentHelper,
        ConfigFactory $configFactory,
        TimezoneInterface $timezone
    ) {
        parent::__construct($context);
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig  = $scopeConfig;
        $this->curl         = $curl;
        $this->transferFactory   = $transferFactory;
        $this->config            = $config;
        $this->json              = $json;
        $this->addressRenderer   = $addressRenderer;
        $this->paymentHelper     = $paymentHelper;
        $this->configFactory     = $configFactory;
        $this->timezone          = $timezone;
    }

    /**
     * Send Pay by link email.
     *
     * @param object $order
     */
    public function sendPaybyLinkEmail($order)
    {
        $storeId = $order->getStoreId();
        $customerEmail = $order->getCustomerEmail();
        $customerName = $order->getCustomerName();
        $storeName = $this->merchantName($storeId);
        $paymentinfo = $order->getPayment()->getAdditionalInformation();
        $orderLink   = isset($paymentinfo['paybylink']['url']) ? $paymentinfo['paybylink']['url'] : '';
        $allowedAttempt = isset($paymentinfo['paybylink']['numberOfAllowedAttempts']) ?
         $paymentinfo['paybylink']['numberOfAllowedAttempts'] : '';
        $expiryDate = isset($paymentinfo['paybylink']['expiryDateTime']) ?
         $paymentinfo['paybylink']['expiryDateTime'] : '';
        if (!empty($expiryDate)) {
            $expiryDate = $this->timezone->date($expiryDate)->format('d-m-y');
        }
        $templateConfigPath = 'payment/pay_by_link/email_template';
        $templateId = $this->scopeConfig->getValue($templateConfigPath, ScopeInterface::SCOPE_STORE, $storeId);
        $senderIdentityConfigPath = 'payment/pay_by_link/email_identity';
        $senderIdentity = $this->senderInfo($senderIdentityConfigPath, $storeId);
        $sendermail = $this->scopeConfig->getValue(
            "trans_email/ident_{$senderIdentity}/email",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $sender = [
            'email' =>  $sendermail,
            'name'  => $this->scopeConfig->getValue(
                "trans_email/ident_{$senderIdentity}/name",
                ScopeInterface::SCOPE_STORE,
                $storeId
            ),
        ];
        if (is_numeric($templateId)) {
            $this->transportBuilder->setTemplateId((int)$templateId);
        } else {
            $this->transportBuilder->setTemplateIdentifier($templateId);
        }
        $this->transportBuilder->setTemplateOptions([
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId,
            ])
            ->setTemplateVars([
                'customer_name' => $customerName,
                'order_link' => $orderLink,
                'order'                     => $order,
                'order_id'                  => $order->getId(),
                'order_data'                => $order->getData(),
                'is_not_virtual' => !$order->getIsVirtual(),
                'merchant_name' => $storeName,
                'allowed_attempt' => $allowedAttempt,
                'expiry' => $expiryDate,
                'support_email' => $sendermail,
                'increment_id' => $order->getIncrementId(),
                'created_at' =>  $order->getCreatedAt(),
                'formattedBillingAddress'   => $this->addressRenderer->format(
                    $order->getBillingAddress(),
                    'html'
                ),
                'formattedShippingAddress'  => !$order->getIsVirtual() ?
                    $this->addressRenderer->format(
                        $order->getShippingAddress(),
                        'html'
                    ) : '',

                'payment_html' => $this->paymentHelper->getInfoBlockHtml(
                    $order->getPayment(),
                    $storeId
                )
            ])
            ->setFrom($sender)
            ->addTo($customerEmail, $customerName);
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
    }
    
    /**
     * Revoke pay by link.
     *
     * @param object $order
     */
    public function revokeLink($order)
    {
        try {
               $storeId = $order->getStoreId();
               $additionalInfo = $order->getPayment()->getAdditionalInformation();
               $this->config->setMethodCode("pay_by_link");
               $linkid = $additionalInfo['paybylink']['id'] ?? null;
               $url =  $this->getGatewayUri($storeId) .'link/'.$linkid;
               $username = 'merchant.'.$this->config->getMerchantId($storeId);
               $password = $this->config->getMerchantPassword($storeId);
               $authString = base64_encode($username . ':' . $password);
               $this->curl->addHeader("Content-Type", "application/json");
               $this->curl->addHeader("Authorization", 'Basic ' . $authString);
               $this->curl->setOption(CURLOPT_CUSTOMREQUEST, "DELETE");
               $this->curl->get($url);
               $response = $this->curl->getBody();
               return $this->json->unserialize($response);
        } catch (LocalizedException $e) {
                        return [
                          'success' => false,
                          'message' => $e->getMessage()
                        ];
        }
    }
    
    /**
     * Get gateway url
     *
     * @param int|null $storeId
     * @return mixed
     */
    protected function getGatewayUri($storeId = null)
    {
        return $this->config->getApiUrl($storeId) . $this->apiVersionUri($storeId) . $this->merchantUri($storeId);
    }
    
    /**
     * Get api version
     *
     * @param int|null $storeId
     * @return string
     */
    protected function apiVersionUri($storeId = null)
    {
        return 'version/' . $this->config->getValue('api_version', $storeId) . '/';
    }

    /**
     * Get merchant id
     *
     * @param int|null $storeId
     * @return string
     */
    protected function merchantUri($storeId = null)
    {
        return 'merchant/' . $this->config->getMerchantId($storeId) . '/';
    }
    
    /**
     * Send Pay by link email.
     *
     * @param object $order
     */
    public function sendrevokeLinkEmail($order)
    {
        $storeId = $order->getStoreId();
        $customerEmail = $order->getCustomerEmail();
        $customerName = $order->getCustomerName();
        $storeName = $this->merchantName($storeId);
        $templateConfigPath = 'payment/pay_by_link/revoke_template';
        $templateId = $this->scopeConfig->getValue($templateConfigPath, ScopeInterface::SCOPE_STORE, $storeId);
        $senderIdentityConfigPath = 'payment/pay_by_link/email_identity';
        $senderIdentity = $this->senderInfo($senderIdentityConfigPath, $storeId);
        $sendermail = $this->scopeConfig->getValue(
            "trans_email/ident_{$senderIdentity}/email",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $sender = [
            'email' =>  $sendermail,
            'name'  => $this->scopeConfig->getValue(
                "trans_email/ident_{$senderIdentity}/name",
                ScopeInterface::SCOPE_STORE,
                $storeId
            ),
        ];
        if (is_numeric($templateId)) {
            $this->transportBuilder->setTemplateId((int)$templateId);
        } else {
            $this->transportBuilder->setTemplateIdentifier($templateId);
        }
        $this->transportBuilder->setTemplateOptions([
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId,
            ])
            ->setTemplateVars([
                'customer_name' => $customerName,
                'order'                     => $order,
                'order_id'                  => $order->getId(),
                'order_data'                => $order->getData(),
                'is_not_virtual' => !$order->getIsVirtual(),
                'merchant_name' => $storeName,
                'increment_id' => $order->getIncrementId(),
                'created_at' =>  $order->getCreatedAt(),
                'support_email' => $sendermail,
                'formattedBillingAddress'   =>  $this->addressRenderer->format(
                    $order->getBillingAddress(),
                    'html'
                ),
                'formattedShippingAddress'  => !$order->getIsVirtual() ?
                    $this->addressRenderer->format(
                        $order->getShippingAddress(),
                        'html'
                    ) : '',
                'payment_html' => $this->paymentHelper->getInfoBlockHtml(
                    $order->getPayment(),
                    $storeId
                )
            ])
            ->setFrom($sender)
            ->addTo($customerEmail, $customerName);
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
    }

    /**
     * Get merchant name
     *
     * @param int|null $storeId
     * @return string
     */
    public function merchantName($storeId = null)
    {
        $storeName   = $this->storeManager->getStore()->getName();
        $config = $this->configFactory->create();
        $config->setMethodCode('tns_hosted');
        if ($config->getValue('enable_merchant_info', $storeId)) {
            $storeName = $config->getValue('merchant_name', $storeId);
        }
        return $storeName;
    }

    /**
     * Get Sender identity
     *
     * @param int|null $storeId
     * @return string
     */
    public function senderInfo($senderIdentityConfigPath, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $senderIdentityConfigPath,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
