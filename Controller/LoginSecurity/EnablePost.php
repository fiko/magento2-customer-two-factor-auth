<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Controller\LoginSecurity;

use Exception;
use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Framework\View\Result\PageFactory;

/**
 * Enable 2fa action controller.
 */
class EnablePost extends AbstractAccount implements HttpPostActionInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var MessageManager
     */
    protected $messageManager;

    /**
     * @var AuthHelper
     */
    protected $authHelper;

    /**
     * Constructor.
     *
     * @param Context        $context           Parent class purposes
     * @param PageFactory    $resultPageFactory Magento page response for controller
     * @param AuthHelper     $authHelper        Current extension helper
     * @param MessageManager $messageManager    Message manager to send information box to customer
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        AuthHelper $authHelper,
        MessageManager $messageManager
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->messageManager = $messageManager;
        $this->authHelper = $authHelper;
    }

    /**
     * Enable 2fa action handler.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $otpToken = $this->getRequest()->getPost('otp-token');
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        // if the OTP already enabled, then redirect the customer to my account
        if ($this->authHelper->isOtpEnabled()) {
            $resultRedirect->setPath('*/*');

            return $resultRedirect;
        }

        // validate the token parameter
        if (is_null($otpToken)) {
            $resultRedirect->setPath('*/*/enable');

            return $resultRedirect;
        }

        // if verification is failed, then redirect back to the previous page (enable OTP page)
        if (!$this->authHelper->verifyCustomerOtp($otpToken)) {
            $resultRedirect->setPath('*/*/enable');
            $this->authHelper->session->setData(AuthHelper::ENABLING_2FA, true);
            $this->messageManager->addErrorMessage(
                __('Wrong Confirmation Code, please try again with the latest code.')
            );

            return $resultRedirect;
        }

        try {
            $customer = $this->authHelper->getCustomer();
            $customer->setCustomAttribute(AuthHelper::IS_ENABLE, 1);
            $this->authHelper->customerRepository->save($customer);

            $this->messageManager->addSuccessMessage(__('Two Factor Authentication has been enabled.'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } finally {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*');
        }

        return $resultRedirect;
    }
}
