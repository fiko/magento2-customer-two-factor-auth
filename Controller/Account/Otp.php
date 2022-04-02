<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Controller\Account;

use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Login form page. Accepts POST for backward compatibility reasons.
 */
class Otp extends AbstractAccount implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        Session $customerSession,
        AuthHelper $authHelper,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);

        $this->session = $customerSession;
        $this->authHelper = $authHelper;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Customer login form page.
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if ($this->session->isLoggedIn()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');

            return $resultRedirect;
        }

        if (($otpSession = $this->authHelper->getSessionOtpLogin()) === null) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/login');

            return $resultRedirect;
        }

        if ($otpSession['is_reload'] === true) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/login');

            return $resultRedirect;
        }

        $this->authHelper->setReloadPage(true);

        // UNCOMMENT THIS >>>>>>>
        // if ($this->sesion->hasOtpOpened()) {
        //     //
        // }
        // <<<<<<<

        // UNCOMMENT THIS >>>>>>>
        // // unset data otp_customer_id
        // $this->session->unsOtpCustomerId();
        // <<<<<<<

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getLayout()->getBlock('fiko-login-otp-form')
            ->setData('session', $this->authHelper->getSessionOtpLogin());

        return $resultPage;
    }
}
