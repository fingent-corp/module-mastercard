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

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutFactory;
use Magento\Checkout\Model\Session;
use Mastercard\Mastercard\Gateway\Response\ThreeDSecure\CheckHandler;

/**
* Class Vaultform
* For paasing required information to iframe
* @package Mastercard\Mastercard\Controller\Threedsecure
*/
class Vaultform  extends Action
{
    /**
    * @var RawFactory
    */
    protected $rawFactory;

    /**
    * @var LayoutFactory
    */
    protected $layoutFactory;

    /**
    * @var Session
    */
    protected $session;

    /**
    * Acs constructor.
    * @param Context $context
    * @param RawFactory $pageFactory
    * @param LayoutFactory $layoutFactory
    * @param Session $session
    */
    public function __construct(
        Context $context,
        RawFactory $rawFactory,
        LayoutFactory $layoutFactory,
        Session $session
    ) {
        parent::__construct($context);
        $this->rawFactory       = $rawFactory;
        $this->layoutFactory    = $layoutFactory;
        $this->session          = $session;
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
        /* @var Template $block */

        $payment = $this->session->getQuote()->getPayment();
        $this->getRequest();

        // Retrieve parameters from the URL
        $request = $this->getRequest();

        $acsUrl = $request->getParam('acsUrl');
        $paReq = $request->getParam('paReq');
        $data = [];
        $data = array_merge($data, [
                'acsurl' => $acsUrl,
                'pareq' => $paReq,
           ]);
            
        $payment->setAdditionalInformation(CheckHandler::THREEDSECURE_CHECK, $data);
        $payment->save();
       $block = $this->layoutFactory
            ->create()
            ->createBlock(\Mastercard\Mastercard\Block\Threedsecure\Vaultform::class);

        $block
            ->setTemplate('Mastercard_Mastercard::threedsecure/vaultform.phtml')
            ->setData($data);

        $resultRaw = $this->rawFactory->create();
        return $resultRaw->setContents(
            $block->toHtml()
        );
    }
}
