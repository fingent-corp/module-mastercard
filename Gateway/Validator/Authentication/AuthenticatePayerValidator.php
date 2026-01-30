<?php
/**
 * Copyright (c) 2016-2020 Mastercard
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

namespace Mastercard\Mastercard\Gateway\Validator\Authentication;

use Magento\Framework\Stdlib\ArrayManager;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Mastercard\Mastercard\Exception\SystemBusy;

class AuthenticatePayerValidator extends AbstractValidator
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * InitiateAuthValidator constructor.
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ArrayManager $arrayManager
    ) {
        parent::__construct($resultFactory);
        $this->arrayManager = $arrayManager;
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     *
     * @return ResultInterface
     *
     * @throws SystemBusy
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);

        if ($this->isServerBusyResponse($response)) {
            $this->throwServerBusyError($response);
        }

        $error = $this->arrayManager->get('error', $response);
        $result = $this->arrayManager->get('result', $response);
        $gatewayRecommendation = $this->arrayManager->get('response/gatewayRecommendation', $response);
        $transactionId = $this->arrayManager->get('transaction/id', $response);
        $version = $this->arrayManager->get('authentication/version', $response);

        // Initialize a variable to store the validation result
        $validationResult = null;
        $validationMessages = [];

        if (isset($error)) {
            // Set the error message if present
            $validationResult = false;
            // map errors on correct errors for customers,need to implement
            $validationMessages[] = 'Error';
        } elseif ($version === 'NONE' && $transactionId && $gatewayRecommendation === 'PROCEED') {
            // Handle specific case where version is 'NONE' and gateway recommendation is 'PROCEED'
            $validationResult = true;
        } elseif ($version !== '3DS1' && $version !== '3DS2') {
            // Unsupported 3DS version
            $validationResult = false;
            $validationMessages[] = 'Unsupported version of 3DS';
        } elseif (!in_array($result, ['SUCCESS', 'PROCEED', 'PENDING']) || $gatewayRecommendation !== 'PROCEED') {
            // Handle declined transaction
            $validationResult = false;
            $validationMessages[] = 'Transaction declined';
        } else {
            // Default to success if no errors
            $validationResult = true;
        }

        // Return the validation result with appropriate messages
        return $this->createResult($validationResult, $validationMessages);
    }

    /**
     * Checking for server busy response
     *
     * @param array $response
     *
     * @return bool
     */
    private function isServerBusyResponse(array $response): bool
    {
        $cause = $response['cause'] ?? null;
        if (!$cause) {
            return false;
        }

        return 'SERVER_BUSY' === $cause;
    }

    /**
     * For throwing server busy error
     *
     * @param array $response
     *
     * @return void
     *
     * @throws SystemBusy
     */
    private function throwServerBusyError(array $response): void
    {
        $explanation = $response['explanation'] ?? null;
        $explanation = $explanation ? __($explanation) : __('System Busy');

        throw new SystemBusy($explanation);
    }
}
