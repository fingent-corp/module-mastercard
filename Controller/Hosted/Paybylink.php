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

namespace Mastercard\Mastercard\Controller\Hosted;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Quote\Model\QuoteFactory;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Command\CommandPool;
use Magento\Framework\Controller\Result\RedirectFactory;

/**
 * Class Paybylink
 * call back controller for creating Magento order
 * Used for off site redirect payment
 */
class Paybylink extends Action
{
    public const CHECKOUT_CART_URL = 'checkout/cart';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_PROCESSING = 'processing';
    public const AUTHORIZED = 'authorized';
    public const CAPTURED = 'captured';
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderSender
     */
    private $orderSender;
    
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;
    
    /**
     * @var OrderInterface
     */
    private $order;
    
    /**
     * @var QuoteManagement
     */
    private $quoteManagement;
    
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CommandPool
     */
    private $commandPool;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;
    
    /**
     * @var RedirectFactory
     */
    private $redirectFactory;
    
    /**
     * Paybylink constructor.
     *
     * @param Session $checkoutSession
     * @param JsonFactory $jsonFactory
     * @param Context $context
     * @param LoggerInterface $logger
     * @param QuoteManagement $quoteManagement
     * @param OrderInterface $order
     * @param QuoteFactory $quoteFactory
     * @param OrderSender $orderSender
     * @param CustomerSession $customerSession
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param CommandPool $commandPool
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        Session $checkoutSession,
        JsonFactory $jsonFactory,
        Context $context,
        LoggerInterface $logger,
        QuoteManagement $quoteManagement,
        OrderInterface $order,
        QuoteFactory $quoteFactory,
        OrderSender $orderSender,
        CustomerSession $customerSession,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        CommandPool $commandPool,
        RedirectFactory $redirectFactory
    ) {
        parent::__construct($context);
        $this->checkoutSession          = $checkoutSession;
        $this->jsonFactory              = $jsonFactory;
        $this->logger                   = $logger;
        $this->quoteManagement          = $quoteManagement;
        $this->order                    = $order;
        $this->quoteFactory             = $quoteFactory;
        $this->orderSender              = $orderSender;
        $this->customerSession          = $customerSession;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->commandPool              = $commandPool;
        $this->redirectFactory          = $redirectFactory;
    }

    /**
     * Redirect Callback
     *
     * Creating magento order after successful payment
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
    
        $params = $this->getRequest()->getParams();
        try {
            
            if ($params['resultIndicator'] && $params['orderId']) {
                $orderId = $params['orderId'];
                if (empty($orderId) === true) {
                    $this->messageManager->addError(__('Payment Failed, As no active cart ID found.'));
                }
                $order  = $this->order->load($orderId);
                // Set session data required by success page
                $this->checkoutSession->setLastOrderId($order->getId());
                $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
                $this->checkoutSession->setLastOrderStatus($order->getStatus());

                $order->setState(static ::STATUS_PROCESSING)
                      ->setStatus(static ::STATUS_PROCESSING)->save();

                $paymentDataObject = $this->paymentDataObjectFactory->create($order->getPayment());
                try {
                    $command = $this->commandPool->get("hosted_order");
                    $command->execute([
                         'payment' => $paymentDataObject]);
                } catch (Exception $e) {
                    $this->logger->error((string)$e);
                    $this->messageManager->addError(__('Unable to fetch order details.'));
                }
                $this->checkoutSession
                     ->setLastSuccessQuoteId($order->getQuoteId())
                     ->setLastQuoteId($order->getQuoteId())
                     ->clearHelperData();
                if (empty($order) === false) {
                    $this->checkoutSession
                         ->setLastOrderId($order->getId())
                         ->setLastRealOrderId($order->getIncrementId())
                         ->setLastOrderStatus($order->getStatus());
                }

                return $this->_redirect('checkout/onepage/success');

            } else {
                $this->messageManager->addError(__('Payment Failed.'));
                return $this->_redirect(static::CHECKOUT_CART_URL);
            }

        } catch (Exception $e) {
            $this->logger->error((string)$e);
            $this->messageManager->addError(__('Transaction has been declined.'));
            return $this->_redirect(static::CHECKOUT_CART_URL);
        }
    }
}
