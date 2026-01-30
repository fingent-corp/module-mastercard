<?php
/**
 * Copyright (c) 2016-2025 Mastercard
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

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mastercard\Mastercard\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Sales\Api\OrderRepositoryInterface;

class RevokeOnOrderCancel implements ObserverInterface
{
     /**
      * @var Data
      */
     protected $data;
     
     /**
      * RevokeOnOrderCancel constructor
      *
      * @param Data $data
      */
     public function __construct(
         Data $data
     ) {
        $this->data = $data;
     }
    
    /**
     * Execute the observer for revoke link on cancel.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getPayment()->getMethod() !== 'pay_by_link') {
            return;
        }
        $info = $order->getPayment()->getAdditionalInformation();
        if (!empty($info['paybylink']['url'])) {
            $responseData = $this->data->revokeLink($order);
            if ($responseData['result'] == "SUCCESS") {
                    $order->getPayment()->unsAdditionalInformation('paybylink')->save();
                    $this->data->sendrevokeLinkEmail($order);
            }
        }
    }
}
