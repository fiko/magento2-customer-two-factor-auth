<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Controller\LoginSecurity;

use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Framework\View\Result\PageFactory;

/**
 * Disable 2fa action controller.
 */
class DisablePost extends AbstractAccount implements HttpPostActionInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var AuthenticationInterface
     */
    protected $authentication;

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
     * @param Context                 $context           Parent class purposes
     * @param PageFactory             $resultPageFactory Controller response
     * @param AuthenticationInterface $authentication    Class to authenticate customer
     * @param AuthHelper              $authHelper        Current extension helper
     * @param MessageManager          $messageManager    Message manager to send label information to customer
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        AuthenticationInterface $authentication,
        AuthHelper $authHelper,
        MessageManager $messageManager
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->authentication = $authentication;
        $this->messageManager = $messageManager;
        $this->authHelper = $authHelper;
    }

    /**
     * Disable 2fa action handler.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $customerId = $this->authHelper->session->getCustomer()->getId();
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
    }
}
