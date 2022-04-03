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

class Enable extends AbstractAccount implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        AuthHelper $authHelper,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->authHelper = $authHelper;
    }

    /**
     * Default customer account page.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('customer/loginsecurity');
        }

        $customer = $this->authHelper->getCustomer();
        $customer->setCustomAttribute(AuthHelper::TOTP_SECRET, $this->authHelper->generateSecret());
        $this->authHelper->customerRepository->save($customer);

        $this->authHelper->installQrCodeValidation();

        return $resultPage;
    }
}
