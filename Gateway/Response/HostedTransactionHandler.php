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

namespace Mastercard\Mastercard\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction as dbTransaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class HostedTransactionHandler
 *
 * For handling transaction details
 *
 */
class HostedTransactionHandler implements HandlerInterface
{

    public const IRIS_PAY_TYPE = 'IRIS_PAY';

    public const FAILED_ORDER_STATUSES = [
        'FAILED',
        'CANCELLED',
        'CANCELED'
    ];

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;
    
    /**
     * @var dbTransaction
     */
    protected $transaction;
    
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * HostedTransactionHandler constructor.
     *
     * @param OrderFactory $orderFactory
     * @param InvoiceService $invoiceService
     * @param dbTransaction $transaction
     * @param InvoiceSender $invoiceSender
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderFactory $orderFactory,
        InvoiceService $invoiceService,
        dbTransaction $transaction,
        InvoiceSender $invoiceSender,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderFactory = $orderFactory;
        $this->invoiceService = $invoiceService;
        $this->transaction   = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->orderRepository = $orderRepository;
    }
    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        
        SubjectReader::readPayment($handlingSubject);
        $orderId  = $response['transaction'][0]['order']['id'];
        $order    = $this->getOrderByIncrementId($orderId);
        $responsedata = end($response['transaction']);
        if ($order) {
            $payment = $order->getPayment();
            ContextHelper::assertOrderPayment($payment);
        }
        if ($this->isIrisPay($responsedata) && $this->shouldCancelIrisPayOrder($responsedata)) {
            $this->cancelIrisPayOrder($order, $responsedata);
            return;
        }
        $payment->setTransactionId($responsedata['transaction']['id']);
        $payment->setAdditionalInformation('gateway_code', $responsedata['response']['gatewayCode']);
        $payment->setAdditionalInformation('txn_result', $responsedata['result']);

        if (isset($responsedata['transaction'])) {
            $payment->setAdditionalInformation('transaction', $responsedata['transaction']);
            if (isset($responsedata['authorizationCode'])) {
                $payment->setAdditionalInformation('auth_code', $responsedata['transaction']['authorizationCode']);
            }
            if (isset($responsedata['transaction']['funding']['status'])) {
                $payment->setAdditionalInformation('funding_status', $responsedata['transaction']['funding']['status']);
            }
        }
        if (isset($responsedata['risk'])) {
            $payment->setAdditionalInformation('risk', $responsedata['risk']);
        }
        if (isset($responsedata['sourceOfFunds']) && isset($responsedata['sourceOfFunds']['provided']['card'])) {
            $cardDetails = $responsedata['sourceOfFunds']['provided']['card'];
            $this->setCardDetails($payment, $cardDetails);

        }
        if (isset($responsedata['response']['cardSecurityCode'])) {
            $payment->setAdditionalInformation(
                'cvv_validation',
                $responsedata['response']['cardSecurityCode']['gatewayCode']
            );
        }
        $payment->save();
        $order->save();
        if ($responsedata['transaction']['type'] == "VOID_AUTHORIZATION") {
            $order = $payment->getOrder();
            $orderState = Order::STATE_CANCELED;
            $order->setState($orderState)->setStatus($orderState);
            $order->save();
        }
        if ($responsedata['transaction']['type'] == "PAYMENT") {
            $this->createInvoice($order, $responsedata['transaction']['id']);
        }
        $this->createPaymentTransaction($payment, $responsedata, $response['amount']);
        $this->updateInvoiceTransactionId($order, $responsedata['transaction']['id']);
    }
    
    /**
     * Load order by increment ID using OrderFactory
     *
     * @param string $incrementId
     * @return Order
     */
    public function getOrderByIncrementId($incrementId)
    {
        return $this->orderFactory->create()->loadByIncrementId($incrementId);
    }
    
    /**
     * Checking for Iris Pay
     *
     * @param array $responsedata
     * @return bool
     */
    private function isIrisPay(array $responsedata): bool
    {
        $type = $responsedata['sourceOfFunds']['browserPayment']['type'] ?? '';
        return strtoupper((string)$type) === self::IRIS_PAY_TYPE;
    }

    /**
     * Checking is the order need to be cancelled
     *
     * @param array $responsedata
     * @return bool
     */
    private function shouldCancelIrisPayOrder(array $responsedata): bool
    {
        $orderStatus = strtoupper((string)($responsedata['order']['status'] ?? ''));
        if (in_array($orderStatus, self::FAILED_ORDER_STATUSES, true)) {
            return true;
        }
        return false;
    }

    /**
     * Cancel Iris Pay order
     *
     * @param Order $order
     * @param array $responsedata
     * @return void
     */
    private function cancelIrisPayOrder(Order $order, array $responsedata): void
    {
        if ($order->getState() === Order::STATE_CANCELED) {
            return;
        }

        $orderStatus = $responsedata['order']['status'] ?? 'UNKNOWN';
        $comment = __(
            'Iris Pay payment failed. Gateway order status: %1.',
            $orderStatus
        );

        if ($order->canCancel()) {
            $order->cancel();
        } else {
            $order->setState(Order::STATE_CANCELED);
            $order->setStatus(Order::STATE_CANCELED);
        }

        $order->addStatusHistoryComment($comment)->setIsCustomerNotified(false);
        $this->orderRepository->save($order);
    }

    /**
     * For getting safe value
     *
     * @param array $data
     * @param string $field
     * @return string|null
     */
    public function safeValue($data, $field)
    {
        return isset($data[$field]) ? $data[$field] : null;
    }

    /**
     * Create Invoice
     *
     * @param object $order
     * @param int $transactionId
     * @return boolean
     */
    public function createInvoice($order, $transactionId)
    {
        $invoiceExist = $order->getInvoiceCollection()->getFirstItem();
        if ($invoiceExist->getId()) {
            return true;
        }
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        $invoice->setTransactionId($transactionId);
        $invoice->register();
                
        $this->transaction
             ->addObject($invoice)
             ->addObject($invoice->getOrder())
             ->save();

        $this->invoiceSender->send($invoice);
        $order->addStatusHistoryComment(__('Notified customer about invoice #%1.', $invoice->getId()))
              ->setIsCustomerNotified(true)
              ->save();
        return true;
    }

    /**
     * Update Invoice transaction Id
     *
     * @param object $order
     * @param int $transactionId
     * @return boolean
     */
    public function updateInvoiceTransactionId($order, $transactionId)
    {
        $invoice = $order->getInvoiceCollection()->getFirstItem();
        if ($invoice->getId()) {
            $invoice->setTransactionId($transactionId);
            $invoice->save();
        }
        return true;
    }

    /**
     * Create and save a payment transaction based on the response data.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param array $responsedata
     * @param float $amount
     * @return Mage_Sales_Model_Order_Payment_Transaction The created transaction.
     */
    public function createPaymentTransaction($payment, $responsedata, $amount)
    {
        if ($responsedata['transaction']['type'] == "AUTHORIZATION") {
            $type =  Transaction::TYPE_AUTH;
        } else {
            $type = Transaction::TYPE_CAPTURE;
        }
        $transaction = $payment->addTransaction($type, null, true);
        $transaction->setIsClosed(false);
        $transaction->setTxnId($responsedata['transaction']['id']);
        $transaction->setAmount($amount);
        $transaction->save();
        return $transaction;
    }

    /**
     * Set card details on the payment object based on the response data.
     *
     * @param Mage_Sales_Model_Order_Payment $payment The payment object.
     * @param array $cardDetails containing card information.
     * @return boolean
     */
    public function setCardDetails($payment, $cardDetails)
    {
        $payment->setAdditionalInformation('card_scheme', $cardDetails['scheme']);
        $payment->setAdditionalInformation(
            'card_number',
            'XXXX-' . substr($cardDetails['number'], -4)
        );
        $payment->setAdditionalInformation(
            'card_expiry_date',
            sprintf(
                '%s/%s',
                $cardDetails['expiry']['month'],
                $cardDetails['expiry']['year']
            )
        );
        if (isset($cardDetails['fundingMethod'])) {
            $payment->setAdditionalInformation('fundingMethod', $this->safeValue($cardDetails, 'fundingMethod'));
        }
        if (isset($cardDetails['issuer'])) {
            $payment->setAdditionalInformation('issuer', $this->safeValue($cardDetails, 'issuer'));
        }
        if (isset($cardDetails['nameOnCard'])) {
            $payment->setAdditionalInformation('nameOnCard', $this->safeValue($cardDetails, 'nameOnCard'));
        }
        return true;
    }
}
