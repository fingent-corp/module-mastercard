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

namespace Mastercard\Mastercard\Controller\Adminhtml\Link;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\App\Action\Context;
use Mastercard\Mastercard\Helper\Data;

class Revoke extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    
    /**
     * @var Data
     */
    protected $data;

    /**
     * Generate constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param Data $data
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderRepositoryInterface $orderRepository,
        Data $data
    ) {
        parent::__construct($context);
        $this->resultJsonFactory        = $resultJsonFactory;
        $this->orderRepository          = $orderRepository;
        $this->data                     = $data;
    }

    /**
     * Revoke Pay by link
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            return $result->setData(['success' => false, 'message' => 'Order ID is missing']);
        }
        try {
                $order = $this->orderRepository->get($orderId);
                $responseData = $this->data->revokeLink($order);
            if ($responseData['result'] == "SUCCESS") {
                    $order->getPayment()->unsAdditionalInformation('paybylink')->save();
                    $this->data->sendrevokeLinkEmail($order);
                    return $result->setData([
                            'success' => true
                    ]);
            }
        } catch (LocalizedException $e) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Command execution failed: ' . $e->getMessage()
                ]);
        }
    }
}
