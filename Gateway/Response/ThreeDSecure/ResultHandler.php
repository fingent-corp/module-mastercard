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

namespace Mastercard\Mastercard\Gateway\Response\ThreeDSecure;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

class ResultHandler implements HandlerInterface
{
    const THREEDSECURE_RESULT = '3DSecureResult';

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        // @todo: remove these params when done with them
        $payment->setAdditionalInformation(static::THREEDSECURE_RESULT, [
            'acsEci' => $response['3DSecure']['acsEci'],
            'authenticationToken' => $response['3DSecure']['authenticationToken'],
            'veResEnrolled' => $response['3DSecure']['veResEnrolled'],
            'xid' => $response['3DSecure']['xid'],
        ]);

        $payment->unsAdditionalInformation('PaRes');
    }
}
