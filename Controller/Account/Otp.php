<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Controller\Account;

use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * 2FA form page.
 */
class Otp extends AbstractAccount implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var AuthHelper
     */
    protected $authHelper;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Constructor.
     *
     * @param Context     $context           context class for parent class purpose
     * @param AuthHelper  $authHelper        This extension helper
     * @param PageFactory $resultPageFactory class to return magento UI
     */
    public function __construct(
        Context $context,
        AuthHelper $authHelper,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);

        $this->authHelper = $authHelper;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Main method of the current page.
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        // validate is the customer already login or not
        if ($this->authHelper->session->isLoggedIn()) {
            $resultRedirect->setPath('*/*/');

            return $resultRedirect;
        }

        // validate the OTP Session and checking is it a reloaded page or not
        if (($otpSession = $this->authHelper->getSessionOtpLogin()) === null || $otpSession['is_reload'] === true) {
            $resultRedirect->setPath('*/*/login');

            return $resultRedirect;
        }

        // set reload page so once the customer reload the page, it will redirect the customer to login page
        $this->authHelper->setReloadPage(true);

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getLayout()->getBlock('fiko-login-otp-form')
            ->setData('session', $this->authHelper->getSessionOtpLogin());

        return $resultPage;
    }
}
