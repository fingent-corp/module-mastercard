<?php
/**
 * Copyright (c) 2016-2019 Mastercard
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

namespace Mastercard\Mastercard\Model\Resolver;

use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Quote\Api\BillingAddressManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mastercard\Mastercard\Model\Ui\Hpf\ConfigProvider;

class Createsession implements ResolverInterface
{

    const CREATE_HOSTED_SESSION = 'create_session';

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var PaymentDataObjectFactory
     */
    protected $paymentDataObjectFactory;

    /**
     * @var BillingAddressManagementInterface
     */
    protected $billingAddressManagement;

    /**
     * @var GuestCartRepositoryInterface
     */
    private $cartRepository;
    
    /**
     * @var CommandPoolInterface
     */
    protected $commandPool;
    
    /**
     * @var PaymentInterface
     */
    protected $paymentMethod;
    
    /**
     * @var AddressInterface
     */
    protected $billingAddress;

    /**
     * SessionInformationManagement constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param BillingAddressManagementInterface $billingAddressManagement
     * @param GuestCartRepositoryInterface $cartRepository
     * @param CommandPoolInterface $commandPool
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        BillingAddressManagementInterface $billingAddressManagement,
        GuestCartRepositoryInterface $cartRepository,
        CommandPoolInterface $commandPool,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->billingAddressManagement = $billingAddressManagement;
        $this->cartRepository = $cartRepository;
        $this->commandPool = $commandPool;
        $this->paymentMethod = $paymentMethod;
        $this->billingAddress = $billingAddress;
    }

    /**
     * Create session
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface $billingAddress
     */
    public function createNewPaymentSession(
        $cartId,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ) {
        $cartId = (int) $cartId;

        try {

            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->quoteRepository->getActive($cartId);

            $quote->getPayment()->setQuote($quote);
            $quote->getPayment()->importData(
                $paymentMethod->getData()
            );

            $this->commandPool
                ->get(static::CREATE_HOSTED_SESSION)
                ->execute([
                    'payment' => $this->paymentDataObjectFactory->create($quote->getPayment())
                ]);

            $this->quoteRepository->save($quote);
            $session = $quote->getPayment()->getAdditionalInformation('session');

            if (ConfigProvider::METHOD_CODE == $paymentMethod->getMethod()) {
                $quote->getPayment()->setAdditionalInformation('session', $session['id'])->save();
                }
            return [
                'id' => (string) $session['id'],
                'version' => (string) $session['version']
            ];
        }catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $quote = $this->cartRepository->get($args["input"]["cart_id"]);
        $paymentMethod = $this->paymentMethod->setMethod($args["input"]["payment_method"]["code"]);
        return $this->createNewPaymentSession((string)$quote->getId(), $paymentMethod, $this->billingAddress);
    }
}
