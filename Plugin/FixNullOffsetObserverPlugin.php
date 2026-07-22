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

use Magento\Framework\Event\Observer;
use Magento\Downloadable\Observer\SetLinkStatusObserver;

class FixNullOffsetObserverPlugin
{
    /**
     * Around plugin to fix null array offset errors specifically for Mastercard Hosted Checkout
     */
    public function aroundExecute(
        SetLinkStatusObserver $subject,
        \Closure $proceed,
        Observer $observer
    ) {
        $order = $observer->getEvent()->getOrder();
        if (!$order) {
            return $proceed($observer);
        }

        $payment = $order->getPayment();
        $paymentMethod = $payment ? $payment->getMethod() : null;
        if ($paymentMethod === 'tns_hosted') {
            foreach ($order->getAllItems() as $item) {
                if ($item->getId() === null) {
                    $item->setId(''); 
                }
            }
        }
        return $proceed($observer);
    }
}
