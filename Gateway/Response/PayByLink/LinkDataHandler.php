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

namespace Mastercard\Mastercard\Gateway\Response\PayByLink;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;

class LinkDataHandler implements HandlerInterface
{
    public const SUCCESS_INDICATOR = 'successIndicator';
    public const CHECKOUT_MODE = 'checkoutMode';

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $link = $response['paymentLink'];
        $payment->setAdditionalInformation('paybylink', [
            'id' => $link['id'],
            'url' => $link['url'],
            'numberOfAllowedAttempts' => $link['numberOfAllowedAttempts'],
            'expiryDateTime' => $link['expiryDateTime']
        ]);
        $payment->setAdditionalInformation(static::SUCCESS_INDICATOR, $response[static::SUCCESS_INDICATOR]);
        $payment->setAdditionalInformation(static::CHECKOUT_MODE, $response[static::CHECKOUT_MODE]);
        $payment->save();
    }
}
