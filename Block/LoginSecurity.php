<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Block;

use Exception;
use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Psr\Log\LoggerInterface;

class LoginSecurity extends Template
{
    public function __construct(
        Context $context,
        Session $session,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        RequestInterface $request,
        UrlInterface $urlInterface,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->session = $session;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->request = $request;
        $this->urlInterface = $urlInterface;
    }

    public function getCustomer()
    {
        try {
            return $this->customerRepository->getById($this->session->getCustomerId());
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    public function isOtpEnabled()
    {
        $attr = $this->getCustomer()->getCustomAttribute(AuthHelper::IS_ENABLE);

        return $attr ? (bool) $attr->getValue() : false;
    }
}
