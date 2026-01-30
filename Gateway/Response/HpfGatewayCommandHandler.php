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
use Magento\Framework\App\Config\Storage\WriterInterface;

class HpfGatewayCommandHandler implements HandlerInterface
{

    /**
     * @var configWriter
     */
    protected $configWriter;

    /**
     * HpfGatewayCommandHandler constructor.
     *
     * @param WriterInterface $configWriter
     */
    public function __construct(WriterInterface $configWriter)
    {
        $this->configWriter = $configWriter;
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
 
        if (isset($response['paymentTypes']['card']['cardTypes'])) {
            $cards =  $response['paymentTypes']['card']['cardTypes'];
            $cardTypes = array_column($cards, 'cardType');
            $cardTypesString = $cardTypes ? implode(',', $cardTypes) : null;
            $this->configWriter->save(
                'payment/tns_hpf/supported_cards',
                $cardTypesString
            );
        }
    }
}
