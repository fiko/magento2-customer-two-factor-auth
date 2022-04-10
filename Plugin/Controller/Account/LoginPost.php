<?php

/**
 * Copyright © Fiko Borizqy. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Plugin\Controller\Account;

use Exception;
use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Controller\Account\LoginPost as Subject;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * customer login process to be redirected to OTP page Plugin.
 */
class LoginPost
{
    /**
     * @var Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Magento\Customer\Api\AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var Fiko\CustomerTwoFactorAuth\Helper\Data
     */
    protected $authHelper;

    /**
     * @var Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    private $otpSucceed = false;

    public function __construct(
        Context $context,
        AccountManagementInterface $customerAccountManagement,
        Session $session,
        AuthHelper $authHelper,
        CustomerRepository $customerRepository,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator
    ) {
        $this->messageManager = $context->getMessageManager();
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->customerAccountManagement = $customerAccountManagement;
        $this->session = $session;
        $this->authHelper = $authHelper;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->formKeyValidator = $formKeyValidator;
    }

    /**
     * * customer login process to be redirected to OTP page Handler.
     *
     * @param \Magento\Customer\Controller\Account\LoginPost $subject Main class
     * @param callable                                       $proceed main method
     *
     * @return void
     */
    public function aroundExecute(Subject $subject, callable $proceed)
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($this->session->isLoggedIn() || !$this->formKeyValidator->validate($subject->getRequest())) {
            $resultRedirect->setPath('*/*/');

            return $resultRedirect;
        }

        if ($subject->getRequest()->isPost()) {
            /*
             * OTP Process
             */
            try {
                $this->executeValidateOtp($subject);
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $resultRedirect->setPath('*/*/otp');

                return $resultRedirect;
            }

            $login = $subject->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password']) && $this->otpSucceed === false) {
                try {
                    $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);

                    if ($this->authHelper->isCustomerOtpEnable($customer) === true) {
                        $this->authHelper->setSessionOtpLogin(
                            $customer->getId(),
                            $login['username'],
                            $login['password']
                        );
                        $resultRedirect->setPath('*/*/otp', $this->getRedirectReferer($subject));

                        return $resultRedirect;
                    }
                } catch (EmailNotConfirmedException $e) {
                    $this->messageManager->addComplexErrorMessage(
                        'confirmAccountErrorMessage',
                        ['url' => $this->customerUrl->getEmailConfirmationUrl($login['username'])]
                    );
                    $this->session->setUsername($login['username']);
                } catch (AuthenticationException $e) {
                    $message = __(
                        'The account sign-in was incorrect or your account is disabled temporarily. '
                        .'Please wait and try again later.'
                    );
                } catch (LocalizedException $e) {
                    $message = $e->getMessage();
                } catch (Exception $e) {
                    // PA DSS violation: throwing or logging an exception here can disclose customer password
                    $this->messageManager->addErrorMessage(
                        __('An unspecified error occurred. Please contact us for assistance.')
                    );
                } finally {
                    if (isset($message)) {
                        $this->messageManager->addErrorMessage($message);
                        $this->session->setUsername($login['username']);
                    }
                }
            }
        }

        return $proceed();
    }

    /**
     * Set redirect page if only there is any referer exists.
     *
     * @param Magento\Customer\Controller\Account\LoginPost $subject
     */
    private function getRedirectReferer($subject): array
    {
        if (empty($subject->getRequest()->getParam('referer'))) {
            return [];
        }

        return [
            'referer' => $subject->getRequest()->getParam('referer'),
        ];
    }

    /**
     * Execution of validating OTP token sent by customer.
     *
     * @param Subject $subject Main class
     *
     * @return void
     */
    private function executeValidateOtp($subject)
    {
        // validate session
        $otpSession = $this->authHelper->getSessionOtpLogin();
        if (!isset($otpSession['customer_id']) || !isset($otpSession['username'])) {
            return;
        }

        // validate otp_code
        $login = $subject->getRequest()->getPost('login');
        if (!isset($login['otp_code']) || !isset($login['customer_id'])) {
            return;
        }

        // validate customer
        if ((int) $login['customer_id'] !== (int) $otpSession['customer_id']) {
            return;
        }

        // load customer
        $customer = $this->customerRepository->getById($otpSession['customer_id']);

        // validating otp code
        if (!$this->authHelper->verifyCustomerOtp($login['otp_code'], $customer)) {
            $this->authHelper->setReloadPage(false);
            throw new Exception('Wrong authentication code.');
        }

        // update post value
        $login['username'] = $otpSession['username'];
        $login['password'] = $otpSession['password'];
        $subject->getRequest()->setPostValue('login', $login);
        $this->otpSucceed = true;

        // remove OTP session
        $this->authHelper->unsetSessionOtpLogin();
    }
}
