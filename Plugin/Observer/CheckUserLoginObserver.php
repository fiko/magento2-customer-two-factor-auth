<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Plugin\Observer;

use Magento\Captcha\Observer\CheckUserLoginObserver as Subject;
use Magento\Framework\Event\Observer;

/**
 * Plugin class for captcha validation.
 */
class CheckUserLoginObserver
{
    /**
     * Around method for bypassing captcha on OTP page.
     *
     * @param Subject $subject
     * @param callable $proceed
     * @param Observer $observer
     * @return callable|Subject
     */
    public function aroundExecute(Subject $subject, callable $proceed, Observer $observer)
    {
        $loginParams = $observer->getControllerAction()->getRequest()->getPost('login');
        $customerId = $loginParams['customer_id'] ?? null;
        $otpCode = $loginParams['otp_code'] ?? null;

        if (!empty($customerId) && !empty($otpCode)) {
            return $subject;
        }

        return $proceed($observer);
    }
}
