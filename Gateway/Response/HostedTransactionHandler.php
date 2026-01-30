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
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction as dbTransaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

/**
 * Class HostedTransactionHandler
 *
 * For handling transaction details
 *
 */
class HostedTransactionHandler implements HandlerInterface
{

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
     * HostedTransactionHandler constructor.
     *
     * @param OrderFactory $orderFactory
     * @param InvoiceService $invoiceService
     * @param dbTransaction $transaction
     * @param InvoiceSender $invoiceSender
     */
    public function __construct(
        OrderFactory $orderFactory,
        InvoiceService $invoiceService,
        dbTransaction $transaction,
        InvoiceSender $invoiceSender
    ) {
        $this->orderFactory = $orderFactory;
        $this->invoiceService = $invoiceService;
        $this->transaction   = $transaction;
        $this->invoiceSender = $invoiceSender;
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
        $payment->save();
        $order->save();
        if (isset($responsedata['response']['cardSecurityCode'])) {
            $payment->setAdditionalInformation(
                'cvv_validation',
                $responsedata['response']['cardSecurityCode']['gatewayCode']
            );
        }
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
     * @return \Magento\Sales\Model\Order|null
     */
    public function getOrderByIncrementId($incrementId)
    {
        return $this->orderFactory->create()->loadByIncrementId($incrementId);
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
