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

namespace Mastercard\Mastercard\Controller\ThreedsecureV2;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Payment\Gateway\Command\CommandPool;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Psr\Log\LoggerInterface;
use Mastercard\Mastercard\Model\SelectedStore;
use Magento\Framework\HTTP\Client\Curl;
use Mastercard\Mastercard\Gateway\Config\Config;
use Mastercard\Mastercard\Gateway\Config\ConfigFactory;

/**
* Class VaultAuthentication
* For vault initiate authentication check
* @package Mastercard\Mastercard\Controller\ThreedsecureV2
*/
class VaultAuthentication extends Action
{
    const COMMAND_NAME = 'vault_initiate_authentication';
    /**
    * @var Session
    */
    private $checkoutSession;

    /**
    * @var PaymentDataObjectFactory
    */
    private $paymentDataObjectFactory;

    /**
    * @var JsonFactory
    */
    private $jsonFactory;

    /**
    * @var CommandPool
    */
    private $commandPool;

    /**
    * @var LoggerInterface
    */
    private $logger;

    /**
    * Check constructor.
    * @param Session $checkoutSession
    * @param PaymentDataObjectFactory $paymentDataObjectFactory
    * @param JsonFactory $jsonFactory
    * @param CommandPool $commandPool
    * @param Context $context
    * @param LoggerInterface $logger
    */
    public function __construct(
        Session $checkoutSession,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        JsonFactory $jsonFactory,
        CommandPool $commandPool,
        Context $context,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->checkoutSession          = $checkoutSession;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->jsonFactory              = $jsonFactory;
        $this->commandPool              = $commandPool;
        $this->logger                   = $logger;
    }

    /**
    * Dispatch request
    *
    * @return ResultInterface|ResponseInterface
    */
    public function execute()
    {

       $jsonResult = $this->jsonFactory->create();
       try {
        $quote = $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();

        $publichash = $payment->getAdditionalInformation('public_hash')
            ? $payment->getAdditionalInformation('public_hash')
            : $this->getRequest()->getParam('token');

        $customerid = $payment->getAdditionalInformation('customer_id')
            ? $payment->getAdditionalInformation('customer_id')
            : $quote->getCustomerId();
           
        $payment->setAdditionalInformation('public_hash', $publichash);
        $payment->setAdditionalInformation('customer_id', $customerid);
        $payment->save();
        
        $paymentDataObject = $this->paymentDataObjectFactory->create($payment);

        $quote->setReservedOrderId('')->reserveOrderId();
        $quote->save();

        $this->commandPool
             ->get(self::COMMAND_NAME)
             ->execute([
                'payment' => $paymentDataObject
            ]);

        $payment->save();


        $html                  = $payment->getAdditionalInformation('auth_redirect_html');
        $authenticationstatus = $payment->getAdditionalInformation('auth_threeds_status');
        $jsonResult->setData(['html' => $html , 'status' => $authenticationstatus]);

        } catch (Exception $e) {
            $this->logger->error((string)$e);
            $jsonResult
                ->setHttpResponseCode(400)
                ->setData([
                    'message' => __('An error occurred while processing your transaction')
                ]);
        }

        return $jsonResult;
    }
}
