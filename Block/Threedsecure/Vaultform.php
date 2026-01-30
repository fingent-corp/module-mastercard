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

namespace Mastercard\Mastercard\Block\Threedsecure;

use Magento\Framework\Url;
use Magento\Framework\View\Element\Template;
use Mastercard\Mastercard\Gateway\Request\ThreeDSecure\CheckDataBuilder;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;

class Vaultform extends Template
{

    /**
     * @var Session
     */
    private $session;

    /**
     * Vault constructor.
     *
     * @param Context $context
     * @param Session $session
     */
    public function __construct(
        Context $context,
        Session $session
    ) {
        parent::__construct($context);
        $this->session = $session;
    }
    
    /**
     * For getting return url
     *
     * @return string
     */
    public function getReturnUrl()
    {
        /** @var Url $urlBuilder */
        $urlBuilder = $this->_urlBuilder;

        return $urlBuilder->setUseSession(true)->getUrl(
            CheckDataBuilder::RESPONSE_URL,
            [
                '_secure' => true,
                '_query' => [
                    CheckDataBuilder::RESPONSE_SID_PARAMETER => $this->_session->getSessionId(),
                ],
            ]
        );
    }
    
    /**
     * For getting acs url
     *
     * @return string
     */
    public function getacsUrl()
    {

        $payment  = $this->session->getQuote()->getPayment();
        $paymentd = $payment->getAdditionalInformation('3DSecureEnrollment');
        return $paymentd['acsurl'] ;
    }
    
    /**
     * For getting pareq
     *
     * @return string
     */
    public function getpaReq()
    {
        $payment  = $this->session->getQuote()->getPayment();
        $paymentd = $payment->getAdditionalInformation('3DSecureEnrollment');
        return $paymentd['pareq'];
    }
}
