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

namespace Mastercard\Mastercard\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Mastercard\Mastercard\Gateway\Config\Config;
use Mastercard\Mastercard\Gateway\Config\ConfigFactory;
use Mastercard\Mastercard\Model\SelectedStore;
use Psr\Log\LoggerInterface;
use Mastercard\Mastercard\Helper\DownloadCount;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Cache\TypeListInterface;

class ConfigSaveAfter implements ObserverInterface
{
    public const HTTPS_PREFIX = 'https://';

    /**
     * @var WebsiteRepositoryInterface
     */
    protected $websiteRepository;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var CommandPoolInterface
     */
    protected $commandPool;

    /**
     * @var SelectedStore
     */
    protected $selectedStore;

    /**
     * @var string[]
     */
    protected $methods;

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DownloadCount
     */
    protected $downloadCount;
    
    /**
     * @var configWriter
     */
    protected $configWriter;
    
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * ConfigSaveAfter constructor.
     *
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param GroupRepositoryInterface $groupRepository
     * @param ManagerInterface $messageManager
     * @param ConfigFactory $configFactory
     * @param CommandPoolInterface $commandPool
     * @param SelectedStore $selectedStore
     * @param ScopeConfigInterface $config
     * @param LoggerInterface $logger
     * @param DownloadCount $downloadCount
     * @param WriterInterface $configWriter
     * @param StoreManagerInterface $storeManager
     * @param TypeListInterface $cacheTypeList
     * @param array $methods
     */
    public function __construct(
        WebsiteRepositoryInterface $websiteRepository,
        GroupRepositoryInterface $groupRepository,
        ManagerInterface $messageManager,
        ConfigFactory $configFactory,
        CommandPoolInterface $commandPool,
        SelectedStore $selectedStore,
        ScopeConfigInterface $config,
        LoggerInterface $logger,
        DownloadCount $downloadCount,
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager,
        TypeListInterface $cacheTypeList,
        $methods = []
    ) {
        $this->websiteRepository  = $websiteRepository;
        $this->groupRepository    = $groupRepository;
        $this->messageManager     = $messageManager;
        $this->configFactory      = $configFactory;
        $this->commandPool        = $commandPool;
        $this->selectedStore      = $selectedStore;
        $this->config             = $config;
        $this->logger             = $logger;
        $this->methods            = $methods;
        $this->downloadCount      = $downloadCount;
        $this->configWriter       = $configWriter;
        $this->storeManager       = $storeManager;
        $this->cacheTypeList      = $cacheTypeList;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $request    = $observer->getRequest();
        $configData = $observer->getData('configData');
        
        try {
            if ($this->isInvalidConfigData($configData)) {
                return;
            }
       
            $storeId   = $this->getStoreId($request);
            $test      = 1;
            $this->selectedStore->setStoreId($storeId);
            foreach ($this->methods as $method => $label) {
                $config = $this->configFactory->create(['methodCode' => $method]);
                $test = $this->istestMethod($config, $storeId, $test);
                $this->isValidateUrl($method, $configData);
                $isCertificate = $config->isCertificateAutherntification($storeId);
                $validMethod   = $this->isValidMethod($method, $config, $storeId);
                if ($isCertificate) {
                    $sslPath     = $this->isValidCertificate($config, $storeId);
                    if (!$validMethod || !$sslPath) {
                        continue;
                    }
                } else {
                    $password = $config->getMerchantPassword($storeId);
                    if (!$validMethod || !$password) {
                        continue;
                    }
                }
                $this->checkGatewayconnection($method, $label);
            }
            $this->downloadCount->checkAndSaveDownload($storeId, $test);

        } catch (\Exception $e) {
            $this->logger->critical('Error occurred while testing MasterCard configuration: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }

    /**
     * Checking the configuration data
     *
     * @param array $configData
     * @return boolean
     */
    private function isInvalidConfigData($configData)
    {
        return empty($configData['section']) || $configData['section'] !== 'payment';
    }

    /**
     * For getting store id
     *
     * @param array $request
     * @return int
     */
    public function getStoreId($request)
    {
        $websiteId = $request->getParam('website');
        $storeId   = $request->getParam('store');

        if (empty($storeId) && !empty($websiteId)) {
            $website = $this->websiteRepository->getById($websiteId);
            $storeGroupId = $website->getDefaultGroupId();
            $group = $this->groupRepository->get($storeGroupId);
            $storeId = $group->getDefaultStoreId();
        }
        return $storeId;
    }
    
    /**
     * Checking the certificate validation
     *
     * @param object $config
     * @param int $storeId
     * @return boolean
     */
    private function isValidCertificate($config, $storeId)
    {
        $sslCertPath = $config->getSSLCertificatePath($storeId);
        $sslKeyPath  = $config->getSSLKeyPath($storeId);
        return !$sslKeyPath || !$sslCertPath ? false : true;
    }

    /**
     * Checking the method validation
     *
     * @param string $method
     * @param object $config
     * @param int $storeId
     * @return boolean
     */
    private function isValidMethod($method, $config, $storeId)
    {
        $merchantId = $config->getMerchantId($storeId);
        $apiUrl = $config->getApiUrl($storeId);

        $enabled = "1" === $this->config->getValue(
            sprintf('payment/%s/active', $method),
            ($storeId !== null)
            ? ScopeInterface::SCOPE_STORE
            : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $storeId
        );
        return !$enabled || !$apiUrl || !$merchantId ? false : true;
    }

    /**
     * Checking gateway connection
     *
     * @param string $method
     * @param string $label
     * @return boolean
     */
    private function checkGatewayconnection($method, $label)
    {
        try {
            $command = $this->commandPool->get(sprintf('check_gateway_%s', $method));
            $command->execute([]);
            $this->messageManager->addSuccessMessage(
                __('"%1" test was successful.', __($label))
            );
        } catch (\Exception $e) {
            $this->messageManager->addWarningMessage(
                __(
                    'There was a problem communicating with "%1": %2',
                    __($label),
                    $e->getMessage()
                )
            );
        }
        return true;
    }

    /**
     * Checking sandbox mode on live mode enabled.
     *
     * @param object $config
     * @param int $storeId
     * @param int $test
     *
     * @return boolean
     */
    public function istestMethod($config, $storeId, $test)
    {
        if (($config->isTestMode($storeId = null) != 1) && ($test == 1)) {
            return $config->isTestMode($storeId = null);
        } else {
            return $test;
        }
    }

    /**
     * For validating url
     *
     * @param string $method
     * @param array $configData
     * @return int
     */
    public function isValidateUrl($method, $configData)
    {
    
       $path  = 'payment/'.$method.'/api_gateway_other';
       $count = 0;
       if (!empty($configData['store'])) {
         $scope = ScopeInterface::SCOPE_STORES;
         $scopeId = $this->storeManager->getStore($configData['store'])->getId();
        } elseif (!empty($configData['website'])) {
         $scope = ScopeInterface::SCOPE_WEBSITES;
         $scopeId = $this->storeManager->getWebsite($configData['website'])->getId();
        } else {
         $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
         $scopeId = 0;
        }

        $gatewayUrl =  $this->config->getValue($path, $scope, $scopeId);
        if (empty($gatewayUrl)) {
            return;
        }
        $fixedUrl = trim($gatewayUrl);
        // Replace backslashes with forward slashes
        $fixedUrl = str_replace('\\', '/', $fixedUrl);

        // Fix common malformed schemes like "https//"
        $fixedUrl = preg_replace('#^(tps|htps|tp|hp|ttps)[;:/]+#i', self::HTTPS_PREFIX, $fixedUrl);
        $fixedUrl = preg_replace('#^(https?|ftp)(//)#i', '$1://', $fixedUrl);
        // Remove any invalid or malformed scheme (e.g., http:, https:, tps:)
        $fixedUrl = preg_replace('#^\p{L}+:/+#u', '', $fixedUrl);
        // Prepend correct scheme
        $fixedUrl = self::HTTPS_PREFIX . ltrim($fixedUrl, '/');
        // Parse URL to extract components
        $parts = parse_url($fixedUrl);
        // If scheme is missing, prepend https://
        if (empty($parts['scheme'])) {
            $fixedUrl = self::HTTPS_PREFIX . ltrim($fixedUrl, '/');
        }

        // Ensure it ends with a single slash
        $fixedUrl = rtrim($fixedUrl, '/') . '/';
            // Save back only if changed
            if ($fixedUrl !== $gatewayUrl) {
                $this->configWriter->save($path, $fixedUrl, $scope, $scopeId);
                $count = $count + 1;
                $this->clearCache();
            }
            return $count;
    }
    
    /**
     * For clearing cache on url change
     *
     * @return boolean
     */
    public function clearCache()
    {
        // Clear just the config cache type
        $this->cacheTypeList->cleanType('config');
        return true;
    }
}
