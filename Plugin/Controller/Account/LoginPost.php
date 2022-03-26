<?php

namespace Fiko\CustomerTwoFactorAuth\Plugin\Controller\Account;

use Exception;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Controller\Account\LoginPost as Subject;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Handle customer login process to be redirected to OTP page.
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

    public function __construct(
        Context $context,
        AccountManagementInterface $customerAccountManagement,
        Session $session,
        Validator $formKeyValidator
    ) {
        $this->messageManager = $context->getMessageManager();
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->customerAccountManagement = $customerAccountManagement;
        $this->session = $session;
        $this->formKeyValidator = $formKeyValidator;
    }

    public function aroundExecute(Subject $subject, callable $proceed)
    {
        // DELETE IT >>>>>>>
        return $proceed();
        // <<<<<<<

        if ($this->session->isLoggedIn() || !$this->formKeyValidator->validate($subject->getRequest())) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');

            return $resultRedirect;
        }

        if ($subject->getRequest()->isPost()) {
            $login = $subject->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                    $this->session->setOtpCustomerId($customer->getId());
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setPath('*/*/otp');

                    return $resultRedirect;
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
            } else {
                $this->messageManager->addErrorMessage(__('A login and a password are required.'));
            }
        }

        return $proceed();
    }
}
