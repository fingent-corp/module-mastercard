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

declare(strict_types=1);

namespace Mastercard\Mastercard\Model\Adminhtml\Source\Email;

use Magento\Config\Model\Config\Source\Email\Template as MagentoTemplate;
use Magento\Framework\Registry;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory;

class Template extends MagentoTemplate
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var CollectionFactory
     */
    protected $templatesFactory;

    /**
     * @param Registry $coreRegistry
     * @param CollectionFactory $templatesFactory
     */
    public function __construct(
        Registry $coreRegistry,
        CollectionFactory $templatesFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->templatesFactory = $templatesFactory;
    }

    /**
     * For getting pay by link mail templates
     *
     * {@inheritdoc}
     */
    public function toOptionArray()
    {

        /** @var \Magento\Email\Model\ResourceModel\Template\Collection $collection */
        $collection = $this->coreRegistry->registry('config_system_email_template');
        if (!$collection) {
            $collection = $this->templatesFactory->create();
            $collection->load();
            $this->coreRegistry->register('config_system_email_template', $collection);
        }
        $options = $collection->toOptionArray();
        $customTemplateId = 'payment_pay_by_link_email_template';
        $customTemplateLabel = __('Pay By Link (Default)');
        array_unshift($options, [
            'value' => $customTemplateId,
            'label' => $customTemplateLabel
        ]);
        return $options;
    }
}

