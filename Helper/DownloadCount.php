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

/**
* Class DownloadCount
* @package Mastercard\Mastercard\Helper
*/
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
    
    protected $curlOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        ];
    
        const BEARER_TOKEN = 'd8eafdaefe991251fa9386cc13533f43a2d277183b4bd3a04cdaf0882a2f6058';
        const CHECK_URL    = 'https://mpgs.fingent.wiki/wp-json/mpgs/v2/update-repo-status';
        const LATEST_RELEASE    = 1;
        const REPO_NAME         = 'module-mastercard';
        const PLUGIN_TYPE       = 'enterprise';



    /**
     * Downloadcount constructor.
     *
     * @param Curl $curl
     * @param ProductMetadataInterface $productMetadata
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param ReadFactory $readFactory
     * @param WriterInterface $configWriter
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $config
     * @param LoggerInterface $logger

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

    )
    {
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
    * @param int $storeId
    *
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

            $this->curl->setOptions($this->curlOptions);
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => static::CHECK_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' .static::BEARER_TOKEN
                ),
            ]);
            curl_exec($curl);
            curl_error($curl);
            curl_close($curl);
            return true;
    
    }

    /**
    * For getting module version
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
    * @param int $storeId
    * @param int $test
    *
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
    
}
