<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Controller\LoginSecurity;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Message\Manager as MessageManager;
use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;

class DisablePost extends AbstractAccount implements HttpPostActionInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        AccountManagementInterface $accountManagement,
        AuthenticationInterface $authentication,
        AuthHelper $authHelper,
        MessageManager $messageManager,
        Session $session
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->accountManagement = $accountManagement;
        $this->session = $session;
        $this->authentication = $authentication;
        $this->messageManager = $messageManager;
        $this->authHelper = $authHelper;
    }

    /**
     * Default customer account page.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $customerId = $this->session->getCustomer()->getId();
        $currentPassword = $this->getRequest()->getPost('current-password');

        try {
            $this->authentication->authenticate($customerId, $currentPassword);
        } catch (InvalidEmailOrPasswordException $e) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addErrorMessage(__('Password is incorrect.'));
            $resultRedirect->setPath('*/*/disable');

            return $resultRedirect;
        }
        
        if (!$this->authHelper->isOtpEnabled()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*');

            return $resultRedirect;
        }

        $customer = $this->authHelper->getCustomer();
        $customer->setCustomAttribute(AuthHelper::IS_ENABLE, 0);
        $this->authHelper->customerRepository->save($customer);

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*');
        $this->messageManager->addSuccessMessage(__('Two Factor Authentication has been disabled.'));

        return $resultRedirect;

        // // $tmp = $this->authentication->authenticate($email, $currentPassword);
        // header('Content-Type: application/json;');
        // die(json_encode(is_object($tmp) ? get_class_methods($tmp) : $tmp));
        // $resultPage = $this->resultPageFactory->create();
        // die('welcome');

        // return $resultPage;
    }
}
