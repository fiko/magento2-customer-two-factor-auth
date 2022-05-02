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

/**
 * print QR Code image for customer controller.
 */
class OtpQrCodeImage extends AbstractAccount implements HttpGetActionInterface
{
    /**
     * @var AuthHelper
     */
    protected $authHelper;

    /**
     * Constructor.
     *
     * @param Context    $context    Class for parent class purpose
     * @param AuthHelper $authHelper Helper of the current extension
     */
    public function __construct(
        Context $context,
        AuthHelper $authHelper
    ) {
        parent::__construct($context);

        $this->authHelper = $authHelper;
    }

    /**
     * Print QR Code image for customer handler.
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\App\Response\Http
     */
    public function execute()
    {
        if (!$this->authHelper->session->isLoggedIn() ||
            $this->authHelper->session->getData(AuthHelper::QRCODE_VALIDATION) !== true ||
            $this->authHelper->isOtpEnabled()
        ) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*');

            return $resultRedirect;
        }

        $this->authHelper->session->unsetData(AuthHelper::QRCODE_VALIDATION);

        return $this->getResponse()->setHeader('Content-Type', 'image/png')
            ->setBody($this->authHelper->getQrCodeAsPng());
    }
}
