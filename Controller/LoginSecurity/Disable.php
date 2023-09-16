<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Controller\LoginSecurity;

use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Disable page and form controller.
 */
class Disable extends AbstractAccount implements HttpGetActionInterface
{
    /** @var PageFactory */
    protected $resultPageFactory;

    /** @var AuthHelper */
    protected $authHelper;

    /**
     * Constructor.
     *
     * @param Context     $context           Parent class purposes
     * @param PageFactory $resultPageFactory Magento page response for controller
     * @param AuthHelper  $authHelper        Current extension helper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        AuthHelper $authHelper
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->authHelper = $authHelper;
    }

    /**
     * Disable page and form handler.
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if (!$this->authHelper->isOtpEnabled()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*');

            return $resultRedirect;
        }

        $resultPage = $this->resultPageFactory->create();
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('customer/loginsecurity');
        }

        return $resultPage;
    }
}
