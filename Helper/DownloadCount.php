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

namespace Mastercard\Mastercard\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Directory\Model\CountryFactory;

class DownloadCount extends AbstractHelper
{
    
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;
    
    /**
     * @var ComponentRegistrarInterface
     */
    protected $componentRegistrar;
    
    /**
     * @var ReadFactory
     */
    protected $readFactory;
    
    /**
     * @var Json
     */
    protected $json;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CountryFactory
     */
    protected $countryFactory;
    
    public const BEARER_TOKEN = 'd8eafdaefe991251fa9386cc13533f43a2d277183b4bd3a04cdaf0882a2f6058';
    public const CHECK_URL    = 'https://mpgs.fingent.wiki/wp-json/mpgs/v2/update-repo-status';
    public const LATEST_RELEASE    = 1;
    public const REPO_NAME         = 'module-mastercard';
    public const PLUGIN_TYPE       = 'enterprise';

    /**
     * Downloadcount constructor.
     *
     * @param Context $context
     * @param Curl $curl
     * @param ProductMetadataInterface $productMetadata
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param ReadFactory $readFactory
     * @param WriterInterface $configWriter
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $config
     * @param LoggerInterface $logger
     * @param Json $json
     * @param CountryFactory $countryFactory
     */
    public function __construct(
        Context $context,
        Curl $curl,
        ProductMetadataInterface $productMetadata,
        ComponentRegistrarInterface $componentRegistrar,
        ReadFactory $readFactory,
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $config,
        LoggerInterface $logger,
        Json $json,
        CountryFactory $countryFactory
    ) {
        parent::__construct($context);
        $this->curl               = $curl;
        $this->productMetadata    = $productMetadata;
        $this->configWriter       = $configWriter;
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory        = $readFactory;
        $this->storeManager       = $storeManager;
        $this->json               = $json;
        $this->logger             = $logger;
        $this->config             = $config;
        $this->countryFactory    = $countryFactory;
    }
    
    /**
     * Send the download count for a specific store.
     *
     * @param int $storeId
     * @return boolean
     */
    public function sendDownloadCount($storeId)
    {
    
        $store     = $this->storeManager->getStore();
        $tagName   = $this->getModuleversion();
        $this->configWriter->save(
            'payment/tns/version_info',
            $tagName
        );
        $countryCode = $this->config->getValue(
            'general/country/default',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $country = $this->countryFactory->create()->loadByCode($countryCode);

        $data = [
                    'repo_name' => static::REPO_NAME,
                    'plugin_type' => static::PLUGIN_TYPE,
                    'tag_name'  => $tagName,
                    'latest_release' => static::LATEST_RELEASE,
                    'country_code' =>  $countryCode,
                    'country' =>  $country->getName(),
                    'shop_name' => $store->getName(),
                    'shop_url' => $store->getBaseUrl()
                ];
        $url = static::CHECK_URL;
        try {
                $this->curl->addHeader("Content-Type", "application/json");
                $this->curl->addHeader("Authorization", 'Bearer ' . static::BEARER_TOKEN);
                $this->curl->post($url, json_encode($data));
                $this->curl->getBody();
                return true;

        } catch (\Exception $e) {
                return false;
        }
    }

    /**
     * For getting module version
     *
     * @return string
     * @throws LocalizedException
     */
    public function getModuleversion()
    {

        $data = [];
        $path = $this->componentRegistrar->getPath(
            ComponentRegistrar::MODULE,
            'Mastercard_Mastercard'
        );
        $dir = $this->readFactory->create($path);
        try {
            $jsonData = $dir->readFile('composer.json');
            $data = $this->json->unserialize($jsonData);
        } catch (\Exception $e) {
            $this->logger->critical('Error occurred while taking module version ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
        return isset($data['version']) ? $data['version'] : 'unknown';
    }

    /**
     * Checking and saving module download count.
     *
     * @param int $storeId
     * @param int $test
     * @return boolean
     */
    public function checkAndSaveDownload($storeId, $test)
    {
     
        try {
              $isDownloaded = $this->config->getValue(
                  'payment/tns/is_downloaded',
                  ($storeId !== null) ? ScopeInterface::SCOPE_STORE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                  $storeId
              );
              $moduleVersion = $this->config->getValue(
                  'payment/tns/version_info',
                  ($storeId !== null) ? ScopeInterface::SCOPE_STORE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                  $storeId
              );
              $latestVersion = $this->getModuleversion();
            if (((!$isDownloaded) || ($latestVersion != $moduleVersion)) && ($test != 1)) {
                    $this->sendDownloadCount($storeId);
                    $this->configWriter->save('payment/tns/is_downloaded', 1);
            }
        } catch (\Exception $e) {
            $this->logger->critical('Error occurred while saving download count ' . $e->getMessage(), [
               'exception' => $e,
            ]);
        }
        return true;
    }

    /**
    * Checking and saving module download count.
    * @param int $storeId
    * @param int $test
    *
    * @return boolean
    */
    public function getOrderPrefix($storeId)
    {
    
     return $this->config->getValue(
         'payment/tns/order_prefix',
         ($storeId !== null) ? ScopeInterface::SCOPE_STORE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
         $storeId
        );
    }
}
