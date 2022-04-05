<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Block;

use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class LoginSecurity extends Template
{
    /**
     * @var AuthHelper
     */
    public $authHelper;

    /**
     * Constructor.
     *
     * @param Context    $context    Parent class purposes
     * @param AuthHelper $authHelper Current extension helper
     * @param array      $data       Parent class purposes
     */
    public function __construct(
        Context $context,
        AuthHelper $authHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->authHelper = $authHelper;
    }

    /**
     * Checking whether the customer OTP is enabled or not.
     *
     * @return bool 
     */
    public function isOtpEnabled()
    {
        return $this->authHelper->isOtpEnabled();
    }
}
