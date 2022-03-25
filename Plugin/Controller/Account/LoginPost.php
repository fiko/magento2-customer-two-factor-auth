<?php

namespace Fiko\CustomerTwoFactorAuth\Plugin\Controller\Account;

use Magento\Customer\Controller\Account\LoginPost as Subject;

/**
 * Handle customer login process to be redirected to OTP page.
 */
class LoginPost
{
    public function aroundExecute(Subject $subject, callable $proceed)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Initial log');
        $logger->info('app/code/Fiko/CustomerTwoFactorAuth/Plugin/Controller/Account/LoginPost.php');

        return $proceed();
    }
}
