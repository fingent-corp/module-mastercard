<?php
/**
 * Copyright (c) 2016-2021 Mastercard
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
use Mastercard\Mastercard\Model\Operation\WebhookNotificationOperation;
use Magento\Payment\Gateway\Helper\SubjectReader;

class AchWebookNotify implements HandlerInterface
{
    /**
     * @var WebhookNotificationOperation
     */
    protected $notificationOperation;

    /**
     * AchWebookNotify constructor.
     * @param WebhookNotificationOperation $notificationOperation
     */
    public function __construct(
        WebhookNotificationOperation $notificationOperation
    ) {
        $this->notificationOperation = $notificationOperation;
    }

    /**
     * @inheridoc
     * multistep_ach not supported
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        if ($payment->getAmountPaid() == 0) {
            $payment->accept();
            $this->notificationOperation->execute($payment);
            $payment->getOrder()->save();
        }
    }
}
