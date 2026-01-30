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

namespace Mastercard\Mastercard\Model\Adminhtml\Source\Hosted;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Model\MethodInterface;

class FormType implements OptionSourceInterface
{
    /**
     * For getting different form types
     *
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' =>0,
                'label' => __('Embedded Form'),
            ],
            [
                'value' =>1,
                'label' => __('Redirect To Payment Page'),
            ],
            
        ];
    }
}
