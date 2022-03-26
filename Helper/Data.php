<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    const TOTP_SECRET = 'totp_secret';
    const IS_ENABLE = 'is_totp_enable';

    public function __construct(
        Context $context,
        array $data
    ) {
        parent::__construct($context);

        $this->data = $data;
    }
}
