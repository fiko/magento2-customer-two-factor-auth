<?php

/**
 * Copyright © Fiko Borizqy. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Controller\LoginSecurity;

use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Enable page and form controller.
 */
class Enable extends AbstractAccount implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var AuthHelper
     */
    protected $authHelper;

    /**
     * Constructor.
     *
     * @param Context     $context           Parent class purposes
     * @param AuthHelper  $authHelper        Current extension helper
     * @param PageFactory $resultPageFactory Magento page response for controller
     */
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
     * Enable page and form handler.
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if ($this->authHelper->isOtpEnabled()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*');

            return $resultRedirect;
        }

        // setup active menu
        $resultPage = $this->resultPageFactory->create();
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('customer/loginsecurity');
        }

        /*
         * if customer already confirm the code and the code were wrong, secret
         * key does not have to be re-generate on each refreshed page
         */
        if ($this->authHelper->session->getData(AuthHelper::ENABLING_2FA) !== true) {
            $customer = $this->authHelper->getCustomer();
            $customer->setCustomAttribute(AuthHelper::TOTP_SECRET, $this->authHelper->generateSecret());
            $this->authHelper->customerRepository->save($customer);
        } else {
            $this->authHelper->session->unsetData(AuthHelper::ENABLING_2FA);
        }

        $this->authHelper->session->setData(AuthHelper::QRCODE_VALIDATION, true);

        return $resultPage;
    }
}
