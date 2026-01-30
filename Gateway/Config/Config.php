<?php
/**
 * Copyright (c) 2016-2022 Mastercard
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

namespace Mastercard\Mastercard\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Helper\Data as PaymentDataHelper;
use Magento\Store\Model\StoreManagerInterface;
use Mastercard\Mastercard\Model\CertFactory;
use Magento\Store\Model\ScopeInterface;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    public const WEB_HOOK_RESPONSE_URL = 'tns/webhook/response';
    public const API_EUROPE = 'api_eu';
    public const API_AMERICA = 'api_na';
    public const API_ASIA = 'api_as';
    public const API_INDIA = 'api_in';
    public const API_OTHER = 'api_other';
    public const TEST_PREFIX = 'TEST';
    public const AUTHENTICATION_TYPE_PASSWORD = 'password';
    public const AUTHENTICATION_TYPE_CERTIFICATE = 'certificate';
    public const HTTPS_PREFIX = 'https://';
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Data
     */
    protected $paymentDataHelper;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var CertFactory
     */
    protected $certFactory;

    /**
     * @var string
     */
    protected $methodCode;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var string
     */
    protected $pathPattern;

    /**
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param CertFactory $certFactory
     * @param PaymentDataHelper $paymentDataHelper
     * @param string $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        CertFactory $certFactory,
        PaymentDataHelper $paymentDataHelper,
        $methodCode = '',
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->urlBuilder   = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->certFactory  = $certFactory;
        $this->methodCode   = $methodCode;
        $this->pathPattern  = $pathPattern;
        $this->scopeConfig  = $scopeConfig;
        $this->paymentDataHelper = $paymentDataHelper;
    }

    /**
     * For getting payment method.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * For getting merchant id.
     *
     * @param int $storeId
     * @return string
     */
    public function getMerchantId($storeId = null)
    {
        return $this->getValue('api_username', $storeId);
    }

    /**
     * Checking is it test mode.
     *
     * @param int $storeId
     * @return mixed|null
     */
    public function isTestMode($storeId = null)
    {
        return $this->getValue('test', $storeId);
    }

    /**
     * For getting merchant password.
     *
     * @param string|int|null $storeId
     * @return string
     */
    public function getMerchantPassword($storeId = null)
    {
        return $this->getValue('api_password', $storeId);
    }

    /**
     * Checking ssl authentication is enabled or not.
     *
     * @param string|int|null $storeId
     *
     * @return bool
     */
    public function isCertificateAutherntification($storeId = null)
    {
        return $this->getValue('authentication_type', $storeId) === self::AUTHENTICATION_TYPE_CERTIFICATE;
    }

    /**
     * For getting frontend url.
     *
     * @param string|int|null $storeId
     * @return string
     */
    public function getFrontendAreaUrl($storeId = null)
    {
        $url = $this->getValue('api_gateway_other', $storeId);
        if (empty($url)) {
            return '';
        }
        if (!str_starts_with($url, self::HTTPS_PREFIX)) {
            $url = self::HTTPS_PREFIX . ltrim($url, '/');
        }
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        return $url;
    }

    /**
     * For getting api area url.
     *
     * @param string|int|null $storeId
     *
     * @return string
     */
    public function getApiAreaUrl($storeId = null)
    {
        $pkiPostfix = $this->isCertificateAutherntification($storeId) ? '_pki' : '';
        $url = $this->getValue('api_gateway_other' . $pkiPostfix, $storeId);
        if (empty($url)) {
            return '';
        }
        if (!str_starts_with($url, self::HTTPS_PREFIX)) {
            $url = self::HTTPS_PREFIX . ltrim($url, '/');
        }
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }
        return $url;
    }

    /**
     * For getting api url.
     *
     * @param string|int|null $storeId
     *
     * @return string
     */
    public function getApiUrl($storeId = null)
    {
        return $this->getApiAreaUrl($storeId) . 'api/rest/';
    }

    /**
     * For getting webhook secret.
     *
     * @param string|int|null $storeId
     *
     * @return string
     */
    public function getWebhookSecret($storeId = null)
    {
        return $this->getValue('webhook_secret', $storeId);
    }

    /**
     * For getting webhook notification url.
     *
     * @param null|int $storeId
     *
     * @return mixed|null|string
     */
    public function getWebhookNotificationUrl($storeId = null)
    {
        if ($this->getWebhookSecret($storeId) && $this->getWebhookSecret($storeId) === "") {
            return null;
        }
        if ($this->getValue('webhook_url', $storeId) && $this->getValue('webhook_url', $storeId) !== "") {
            return $this->getValue('webhook_url', $storeId);
        }

        return $this->urlBuilder->getUrl(static::WEB_HOOK_RESPONSE_URL, ['_secure' => true]);
    }

    /**
     * Checking need to send  line items in request or not.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isSendLineItems($storeId = null)
    {
        return (bool)$this->getValue('send_line_items', $storeId);
    }

    /**
     * For getting ssl certification path.
     *
     * @param int|string|null $storeId
     *
     * @return string|null
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getSSLCertificatePath($storeId = null)
    {
        if (!$this->getValue('api_cert', $storeId)) {
            return null;
        }

        $path = sprintf($this->pathPattern, $this->methodCode, 'api_cert');
        $websiteId = $this->getWebsiteId($storeId);

        return $this->certFactory
            ->create()
            ->loadByPathAndWebsite($path, $websiteId, false)
            ->getCertPath();
    }

    /**
     * For getting ssl key path.
     *
     * @param int|string|null $storeId
     *
     * @return string|null
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getSSLKeyPath($storeId = null)
    {
        if (!$this->getValue('api_key', $storeId)) {
            return null;
        }

        $path = sprintf($this->pathPattern, $this->methodCode, 'api_key');
        $websiteId = $this->getWebsiteId($storeId);

        return $this->certFactory
            ->create()
            ->loadByPathAndWebsite($path, $websiteId, false)
            ->getCertPath();
    }

    /**
     * For getting website id.
     *
     * @param int|string|null $storeId
     *
     * @return int
     *
     * @throws NoSuchEntityException
     */
    protected function getWebsiteId($storeId)
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }

    /**
     * Checkignthe hosted checkout form type.
     *
     * @param int|string|null $storeId
     *
     * @return int
     *
     * @throws NoSuchEntityException
     */
    public function getFormType($storeId = null)
    {
        return $this->getValue('form_type', $storeId);
    }

    /**
     * Checking terms and conditions enbaled or not in checkout.
     *
     * @param int|string|null $storeId
     *
     * @return int
     *
     * @throws NoSuchEntityException
     */
    public function enabledTermsAndConditions($storeId = null)
    {
   
        return $this->scopeConfig->getValue(
            'checkout/options/enable_agreements',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
