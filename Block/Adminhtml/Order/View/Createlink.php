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

namespace Mastercard\Mastercard\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Createlink extends \Magento\Backend\Block\Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * The template used to render the create payment link UI for the order view page.
     *
     * @var string
     */
    protected $template = 'Mastercard_Mastercard::order/view/create_link.phtml';

    /**
     * @var TimezoneInterface
     */
    protected $timezone;
    
    /**
     * Createlink constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param TimezoneInterface $timezone
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->timezone = $timezone;
        parent::__construct($context, $data);
    }

    /**
     * Get Order.
     *
     * @return \Magento\Sales\Model\Order|null
     */
    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }
    
    /**
     * Get Payment additional information.
     *
     * @return array
     */
    public function getpaymentAdditionalinfo()
    {
        
        $payment = $this->getOrder()->getPayment();
        $method = $payment->getMethod();
        $additionalInfo   = $payment->getAdditionalInformation();
        $response = [ 'method' => $method ];
        if (($method == 'pay_by_link') &&
            isset($additionalInfo['paybylink']) &&
            $additionalInfo['paybylink']['url']
        ) {
            $expiryDate = $additionalInfo['paybylink']['expiryDateTime'] ?? null;
            if (!empty($expiryDate)) {
               $expiryDate = $this->timezone->date($expiryDate)->format('d-m-Y');
            }
            $response =  [
                'method'=> $method,
                'paybylinkurl' => $additionalInfo['paybylink']['url'] ?? null,
                'txn_result' => $additionalInfo['txn_result'] ?? null,
                'expiry_datetime' => $expiryDate,
                'expiry_time' => $additionalInfo['paybylink']['expiryDateTime'] ?? null
            ];
        }
        return $response;
    }
}
