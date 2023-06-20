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

namespace Mastercard\Mastercard\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;

class PaymentOptionsInquiryValidator extends AbstractValidator
{
    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function validate(array $validationSubject)
    {
        $error = false;
        $message = "";

        if (!empty($validationSubject['response']['result'])) {
            $error = $validationSubject['response']['result'] === 'ERROR';
        }

        if (!empty($validationSubject['response']['error']['cause'])) {
            $message .= $validationSubject['response']['error']['cause'];
        }

        if (!empty($validationSubject['response']['error']['explanation'])) {
            $message .= ' (' . $validationSubject['response']['error']['explanation'] . ')';
        }

        if ($error) {
            if ($message === '') {
                $message = __('General Error');
            }
            // @codingStandardsIgnoreStart
            throw new \Exception($message);
            // @codingStandardsIgnoreEnd
        }

        return $this->createResult(true);
    }
}
