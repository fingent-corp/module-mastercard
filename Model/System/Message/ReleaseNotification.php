<?php
/**
 * Copyright (c) 2016-2024 Mastercard
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
declare(strict_types=1);

namespace Mastercard\Mastercard\Model\System\Message;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Phrase;
use Magento\Backend\Model\Session;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class ReleaseNotification
 *
 * @package Mastercard\Mastercard\Model\System\Message
 */
class ReleaseNotification implements MessageInterface
{

    /**
    * @var Session
    */
    protected $session;
    
    /**
    * @var Curl
    */
    protected $curl;
    
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
     * ReleaseNotification constructor.
     *
     * @param Session $session
     * @param Curl $curl
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param ReadFactory $readFactory
     */
    public function __construct(
        Session $session,
        Curl $curl,
        ComponentRegistrarInterface $componentRegistrar,
        ReadFactory $readFactory,
        Json $json
    ) {
        $this->session = $session;
        $this->curl = $curl;
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory = $readFactory;
        $this->json = $json;
    }

    /**
     * Message identity
     */
    const MESSAGE_IDENTITY = 'release_notification';

    /**
     * API Endpoint for checking latest github release
     */
    const API_ENDPOINT = 'https://api.github.com/repos/fingent-corp/module-mastercard/releases/latest';

    /**
     * Get MPGS Module name
     */
    const MODULE_FULL_NAME = 'Mastercard_Mastercard';

    /**
     * Release notes url
     */
    const RELEASE_NOTES_URL =
    'https://mpgs.fingent.wiki/enterprise/magento-2-mastercard-gateway/release-notes/';


    /**
     * Retrieve unique system message identity
     *
     * @return string
     */
    public function getIdentity():string
    {
        return self::MESSAGE_IDENTITY;
    }

    /**
     * Check whether the release notification should be shown
     *
     * @return bool
     */
    public function isDisplayed():bool
    {
        try {
            return $this->isNewReleaseAvailable();
        } catch (FileSystemException $e) {
            throw new LocalizedException(__('Something went wrong while checking the MPGS release information.'));
        } catch (ValidatorException $e) {
            throw new LocalizedException(__('Something went wrong while checking the MPGS version information.'));
        }
    }

    /**
     * Retrieve message content
     *
     * @return Phrase
     * @throws FileSystemException
     * @throws ValidatorException
     */
    public function getText()
    {
        return __(
            sprintf(
                'A new version (%s) of the Mastercard Gateway plugin is now available! Please refer to the
                <a href="%s" target="_blank">Release Notes</a>
                section for information about its compatibility and features.',
                $this->getReleaseVersion(),
                self::RELEASE_NOTES_URL
            )
        );
    }

    /**
     * Check if a new release is available in Github
     *
     * @return bool
     * @throws FileSystemException
     * @throws ValidatorException
     */
    private function isNewReleaseAvailable():bool
    {
        return $this->getReleaseVersion() > $this->getPluginVersion();
    }

    /**
     * Get latest version information of the release from Github
     *
     * @return mixed
     * @throws FileSystemException
     * @throws ValidatorException
     */
    private function getReleaseVersion()
    {
        if ($this->session->getTagName() !== null) {
            return $this->session->getTagName();
        }
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->addHeader("User-Agent", 'Github Api Curl');
        $this->curl->get(self::API_ENDPOINT);
        $body = $this->curl->getBody();
        $bodyData = $this->json->unserialize($body);

        return $this->session->setTagName($bodyData ? $bodyData['tag_name'] : $this->getPluginVersion());
    }

    /**
     * Get plugin's version number by reading the composer.json
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    private function getPluginVersion():string
    {
        $path = $this->componentRegistrar->getPath(
            ComponentRegistrar::MODULE,
            self::MODULE_FULL_NAME
        );
        $directoryRead = $this->readFactory->create($path);
        $composerJsonData = '';
        if ($directoryRead->isFile('composer.json')) {
            $composerJsonData = $directoryRead->readFile('composer.json');
        }
        return $composerJsonData ? $this->json->unserialize($composerJsonData)['version'] : '';
    }

    /**
     * Retrieve system message severity
     *
     * @return int
     */
    public function getSeverity():int
    {
        return self::SEVERITY_NOTICE;
    }

}
