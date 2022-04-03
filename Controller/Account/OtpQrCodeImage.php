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
use Magento\Framework\View\Result\PageFactory;

/**
 * Login form page. Accepts POST for backward compatibility reasons.
 */
class OtpQrCodeImage extends AbstractAccount implements HttpGetActionInterface
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
        if (
            !$this->session->isLoggedIn() ||
            !$this->authHelper->getQrCodeValidation() ||
            $this->authHelper->isOtpEnabled()
        ) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*');

            return $resultRedirect;
        }

        $this->authHelper->uninstallQrCodeValidation();

        $image = $this->authHelper->getQrCodeAsPng();
        header('Content-Type: image/png');
        die($image);
    }
}
