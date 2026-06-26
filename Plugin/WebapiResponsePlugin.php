<?php
/**
 * Copyright (c) 2025-2026 Mastercard
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
namespace Mastercard\Mastercard\Plugin;

use Magento\Framework\Webapi\Rest\Response;
use Magento\Framework\App\Request\Http as HttpRequest;

class WebapiResponsePlugin
{
    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @param HttpRequest $request
     */
    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

   /**
    * Plugin around sendResponse to inject headers right before output
    */
    public function beforeSendResponse(Response $subject)
    {
        $requestUri = $this->request->getRequestUri();

	    if (!str_contains($requestUri, '/rest/default/V1/tns/hc/session/create')) {
           return;
        }
        $headers = [
            'X-Frame-Options'           => 'DENY',
            'X-Content-Type-Options'    => 'nosniff',
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
            'Content-Security-Policy'   => "default-src 'self';",
        ];

        foreach ($headers as $name => $value) {
            $subject->setHeader($name, $value, true);
        }
    }
}
