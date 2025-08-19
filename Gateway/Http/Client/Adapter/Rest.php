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

namespace Mastercard\Mastercard\Gateway\Http\Client\Adapter;

use Magento\Framework\HTTP\Adapter\Curl;

class Rest extends Curl
{

    private const HEADER_CONTENT_LENGTH = 'Content-Length: ';

    /**
     * Send request to the remote server
     *
     * @param string $method
     * @param string $httpVer
     * @param array $headers
     * @param string $body
     * @return string Request as text
     **/
    public function write($method, $url, $httpVer = '1.1', $headers = [], $body = '')
    {
        if (class_exists('\Zend_Uri_Http')) {
            if ($url instanceof Zend_Uri_Http) {
                $url = $url->getUri();
            }
            $this->_applyConfig();
            $body = $this->executeZend($url, $method, $body, $headers);
           
        }else {
            
            if ($url instanceof Laminas\Uri\Http) {
                $url = $url->parse($url);
            }
            $this->_applyConfig();
            $body = $this->executeLaminas($url, $method, $body, $headers);
        }

        return $body;
            
    }

    /**
     * Execute Zend
     *
     * @param string $url
     * @param string $method
     * @param array $headers
     * @param string $body
     * @return string Request as text
    **/
    public function executeZend($url, $method, $body= '', $headers = [])
    {
        // set url to post to
        curl_setopt($this->_getResource(), CURLOPT_URL, $url);
        curl_setopt($this->_getResource(), CURLOPT_RETURNTRANSFER, true);

        if ($method == \Zend_Http_Client::POST) {
            curl_setopt($this->_getResource(), CURLOPT_POST, true);
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
            $headers[] = self::HEADER_CONTENT_LENGTH. strlen($body);
        } elseif ($method == \Zend_Http_Client::PUT) {
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, \Zend_Http_Client::PUT);
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
            $headers[] = self::HEADER_CONTENT_LENGTH. strlen($body);
        } elseif ($method == \Zend_Http_Client::GET) {
            curl_setopt($this->_getResource(), CURLOPT_HTTPGET, true);
        }else {
            curl_setopt($this->_getResource(), CURLOPT_HTTPGET, true);
        }
        if (is_array($headers)) {
            curl_setopt($this->_getResource(), CURLOPT_HTTPHEADER, $headers);
        }
        /**
         * @internal Curl options setter have to be re-factored
         */
        $header = isset($this->_config['header']) ? $this->_config['header'] : true;
        curl_setopt($this->_getResource(), CURLOPT_HEADER, $header);
        curl_setopt($this->_getResource(), CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($this->_getResource(), CURLOPT_SSL_VERIFYHOST, 2);
        // @codingStandardsIgnoreStop

        return $body;
    }

    /**
     * Execute Laminas
     *
     * @param string $url
     * @param string $method
     * @param array $headers
     * @param string $body
     * @return string Request as text
    **/
    public function executeLaminas($url, $method, $body= '', $headers = [])
    {
        curl_setopt($this->_getResource(), CURLOPT_URL, $url);
        curl_setopt($this->_getResource(), CURLOPT_RETURNTRANSFER, true);

        if ($method == \Laminas\Http\Request::METHOD_POST) {
            curl_setopt($this->_getResource(), CURLOPT_POST, true);
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
            $headers[] = self::HEADER_CONTENT_LENGTH. strlen($body);
        } elseif ($method == \Laminas\Http\Request::METHOD_PUT) {
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, \Laminas\Http\Request::METHOD_PUT);
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
            $headers[] = self::HEADER_CONTENT_LENGTH. strlen($body);
        } elseif ($method == \Laminas\Http\Request::METHOD_GET) {
            curl_setopt($this->_getResource(), CURLOPT_HTTPGET, true);
        } else {
            curl_setopt($this->_getResource(), CURLOPT_HTTPGET, true);
        }
        if (is_array($headers)) {
           curl_setopt($this->_getResource(), CURLOPT_HTTPHEADER, $headers);
        }
        /**
         * @internal Curl options setter have to be re-factored
         */
        $header = isset($this->_config['header']) ? $this->_config['header'] : true;
        curl_setopt($this->_getResource(), CURLOPT_HEADER, $header);
        curl_setopt($this->_getResource(), CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($this->_getResource(), CURLOPT_SSL_VERIFYHOST, 2);
        // @codingStandardsIgnoreStop

        return $body;
    }

}
