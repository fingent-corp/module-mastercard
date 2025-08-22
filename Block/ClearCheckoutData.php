<?php
/**
 * Copyright (c) 2024-2025 Mastercard
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
namespace Mastercard\Mastercard\Block;

use Magento\Framework\View\Element\Template;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
* Class ClearCheckoutData
* @package Mastercard\Mastercard\Block
*/
class ClearCheckoutData extends Template
{

    /**
    * @var CheckoutSession
    */
    protected $checkoutSession;

    /**
    * ClearCheckoutData constructor.
    * @param Context $context
    * @param CheckoutSession $checkoutSession
    */
    public function __construct(
        Template\Context $context,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    /**
    * for getting payment method
    * @return string
    */
    public function getPaymentMethodCode()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        return $order ? $order->getPayment()->getMethod() : null;
    }
}

