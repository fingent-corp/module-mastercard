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

/**
 * Class Callback
 * call back controller for creating Magento order
 * Used for off site redirect payment
 * @package Mastercard\Mastercard\Controller\Hosted
 */
class Callback extends Action
{

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
     * Callback constructor.
     * @param Session $checkoutSession
     * @param QuoteManagement $quoteManagement
     * @param JsonFactory $jsonFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param Context $context
     * @param LoggerInterface $logger
     * @param OrderInterface $order
     * @param QuoteFactory $quoteFactory
     * @param OrderSender $orderSender
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Session $checkoutSession,
        JsonFactory $jsonFactory,
        Context $context,
        LoggerInterface $logger,
        QuoteManagement $quoteManagement,
        OrderRepositoryInterface $orderRepository,
        OrderInterface $order,
        QuoteFactory $quoteFactory,
        OrderSender $orderSender,
        CustomerSession $customerSession
        
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

    }

    /**
     * Redirect Callback
     * Creating magento order after successful payment 
     *
     * @return ResultInterface|ResponseInterface
    */
    public function execute()
    {
    
        $params = $this->getRequest()->getParams();
        try {
            if($params['resultIndicator']){
                $quote = $this->checkoutSession->getQuote();
                if(!$this->customerSession->isLoggedIn()) {  
                  $quote->setCustomerEmail($quote->getBillingAddress()->getEmail()); 
                  $quote->setCustomerFirstname($quote->getBillingAddress()->getFirstname());  
                  $quote->setCustomerLastname($quote->getBillingAddress()->getLastname());               
                }
                $quote->collectTotals()->save();
                $orders  = $this->quoteManagement->submit($quote);
                $orderId = $orders->getEntityId();
        
                $order  = $this->order->load($orderId);   
                $quotes = $this->quoteFactory->create()
                               ->load($order->getQuoteId());
                $quotes->setIsActive(0)->save();
                if (empty($orderId) === true)
                {
                    $this->messageManager->addError(__('Payment Failed, As no active cart ID found.'));
                    return $this->_redirect('checkout/cart');
                }
                $this->checkoutSession
                    ->setLastSuccessQuoteId($order->getQuoteId())
                    ->setLastQuoteId($order->getQuoteId())
                    ->clearHelperData();
                if(empty($order) === false)
                {
                    $this->checkoutSession
                         ->setLastOrderId($order->getId())
                         ->setLastRealOrderId($order->getIncrementId())
                         ->setLastOrderStatus($order->getStatus());
                }                                                                                                                    
                $this->orderSender->send($order);
                $this->checkoutSession->replaceQuote($quotes);
                return $this->_redirect('checkout/onepage/success');
            } else{
                $quote = $this->checkoutSession->getQuote();
                $quote->setIsActive(1)
                    ->setReservedOrderId(null)
                    ->save();
                $this->checkoutSession->replaceQuote($quote);
                $this->messageManager->addError(__('Payment Failed.'));
                return $this->_redirect('checkout/cart');
            }

        } catch (Exception $e) {                         
            $this->logger->error((string)$e);
            $this->messageManager->addError(__('Transaction has been declined.'));
            return $this->_redirect('checkout/cart');
        }
    }
}
