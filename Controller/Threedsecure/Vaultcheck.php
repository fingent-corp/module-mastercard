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

namespace Mastercard\Mastercard\Controller\Threedsecure;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandPoolFactory;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Mastercard\Mastercard\Gateway\Response\ThreeDSecure\CheckHandler;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;

/**
* Class Vaultcheck
* For vault 3ds entrollment check
* @package Mastercard\Mastercard\Controller\Threedsecure
*/
class Vaultcheck extends Action
{
    const CHECK_ENROLMENT = '3ds_enrollment';
    const CHECK_ENROLMENT_TYPE_HPF = 'TnsHpfVaultThreeDSecureEnrollmentCommand';

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
    * @var CommandPoolFactory
    */
    private $commandPoolFactory;

    /**
    * Check constructor.
    * @param CommandPoolFactory $commandPoolFactory
    * @param Session $checkoutSession
    * @param PaymentDataObjectFactory $paymentDataObjectFactory
    * @param JsonFactory $jsonFactory
    * @param Context $context
    */
    public function __construct(
        CommandPoolFactory $commandPoolFactory,
        Session $checkoutSession,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        JsonFactory $jsonFactory,
        Context $context
    ) {
        parent::__construct($context);
        $this->commandPoolFactory       = $commandPoolFactory;
        $this->checkoutSession          = $checkoutSession;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->jsonFactory              = $jsonFactory;
    }

    /**
    * Dispatch request
    *
    * @return ResultInterface|ResponseInterface
    * @throws LocalizedException
    * @throws NoSuchEntityException
    */
    public function execute()
    {

        $quote = $this->checkoutSession->getQuote();
        $jsonResult = $this->jsonFactory->create();
        try {
            // reserve new order increment id to avoid a situation
            // when old order id has invalid state in the payment gateway
            $quote->setReservedOrderId('')->reserveOrderId();
            $quote->save();

            $payment = $quote->getPayment();   
            $public_hash = $payment->getAdditionalInformation('public_hash') ? $payment->getAdditionalInformation('public_hash') : $this->getRequest()->getParam('token');      
            $customerid = $payment->getAdditionalInformation('customer_id') ? $payment->getAdditionalInformation('customer_id') : $quote->getCustomerId(); 

            $payment->setAdditionalInformation('public_hash', $public_hash);
            $payment->setAdditionalInformation('customer_id',$customerid);
            $payment->save();  
                    
            $commandPool = $this->commandPoolFactory->create([
                'commands' => [
                    'hpf' => static::CHECK_ENROLMENT_TYPE_HPF,
                ]
            ]);
            $paymentDataObject = $this->paymentDataObjectFactory->create($quote->getPayment());

            $commandPool
                ->get("hpf")
                ->execute([
                    'payment' => $paymentDataObject,
                    'amount' => $quote->getBaseGrandTotal(),
                ]);

            $checkData = $paymentDataObject
                ->getPayment()
                ->getAdditionalInformation(CheckHandler::THREEDSECURE_CHECK);    
                
            $data['result'] = $checkData['veResEnrolled'];
            
            if($checkData['veResEnrolled'] == "Y"){
                $data = array_merge($data, [
                    'acsurl' => $checkData['acsUrl'],
                    'pareq'  => $checkData['paReq'],
                ]);
            }                              
            $jsonResult->setData($data);
        } catch (Exception $e) {
            $jsonResult
                ->setHttpResponseCode(400)
                ->setData([
                    'message' => $e->getMessage()
                ]);
        }
        return $jsonResult;
    }
    
}
