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
use Mastercard\Mastercard\Helper\DownloadCount;
use Magento\Checkout\Model\Session;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderPrefixObserver implements ObserverInterface
{

     /**
     * @var DownloadCount
     */
     protected $downloadCount;
     /**
      * @var Session
      */
      protected $checkoutSession;

     /**
      * @var OrderRepositoryInterface
      */
      protected $orderRepository;

     /**
     * Orderprefix constructor
     *
     * @param DownloadCount $downloadCount
     * @param Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     */
     public function __construct(
         DownloadCount $downloadCount,
         Session $checkoutSession,
         OrderRepositoryInterface $orderRepository
     ) {
        $this->downloadCount      = $downloadCount;
        $this->checkoutSession    = $checkoutSession;
        $this->orderRepository    = $orderRepository;
     }
    
    /**
     * Execute the observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {

        $order = $observer->getEvent()->getOrder();
        if ($order instanceof OrderInterface) {
            $payment    = $order->getPayment();
            $methodCode = $payment->getMethod();
            if (in_array($methodCode, ['tns_hosted', 'tns_hpf', 'mpgs_ach','pay_by_link', 'tns_hpf_vault'])) {
                $storeId     = $order->getStoreId();
                $orderprefix =  $this->downloadCount->getOrderPrefix($storeId);
                $newIncrementId =  $orderprefix != null ?  $orderprefix. $order->getIncrementId() : '';
                if ($newIncrementId) {
                    $order->setIncrementId($newIncrementId);
                    $this->orderRepository->save($order);
                  }
          }
        }
    }
}
