<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Controller\Account;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\View\Result\PageFactory;
use OTPHP\TOTP;

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
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);

        $this->session = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Customer login form page.
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // CURRENT PROGRESS >>>>>>>
        // $totp = TOTP::create('G4XYHSH55AQHHT7B');
        // echo "secret: {$totp->now()}";
        // die;
        // <<<<<<<

        if ($this->session->isLoggedIn()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');

            return $resultRedirect;
        }

        if (!$this->session->hasOtpCustomerId()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/login');

            return $resultRedirect;
        }

        // unset data otp_customer_id
        $this->session->unsOtpCustomerId();

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        return $resultPage;
    }
}
