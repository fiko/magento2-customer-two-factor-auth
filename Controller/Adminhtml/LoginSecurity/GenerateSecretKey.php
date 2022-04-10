<?php

/**
 * Copyright © Fiko Borizqy. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Controller\Adminhtml\LoginSecurity;

use Exception;
use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Controller\Adminhtml\Index;
use Magento\Customer\Helper\View;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer\Mapper as CustomerMapper;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Math\Random;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\LayoutFactory as ResultLayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Newsletter\Model\SubscriberFactory;

/**
 * Reset password controller.
 */
class GenerateSecretKey extends Index implements HttpGetActionInterface
{
    /**
     * @var AuthHelper
     */
    protected $authHelper;

    /**
     * Constructor.
     *
     * @param Context                       $context                       parent class purposes
     * @param Registry                      $coreRegistry                  parent class purposes
     * @param FileFactory                   $fileFactory                   parent class purposes
     * @param CustomerFactory               $customerFactory               parent class purposes
     * @param AddressFactory                $addressFactory                parent class purposes
     * @param FormFactory                   $formFactory                   parent class purposes
     * @param SubscriberFactory             $subscriberFactory             parent class purposes
     * @param View                          $viewHelper                    parent class purposes
     * @param Random                        $random                        parent class purposes
     * @param CustomerRepositoryInterface   $customerRepository            parent class purposes
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter parent class purposes
     * @param Mapper                        $addressMapper                 parent class purposes
     * @param AccountManagementInterface    $customerAccountManagement     parent class purposes
     * @param AddressRepositoryInterface    $addressRepository             parent class purposes
     * @param CustomerInterfaceFactory      $customerDataFactory           parent class purposes
     * @param AddressInterfaceFactory       $addressDataFactory            parent class purposes
     * @param CustomerMapper                $customerMapper                parent class purposes
     * @param DataObjectProcessor           $dataObjectProcessor           parent class purposes
     * @param DataObjectHelper              $dataObjectHelper              parent class purposes
     * @param ObjectFactory                 $objectFactory                 parent class purposes
     * @param LayoutFactory                 $layoutFactory                 parent class purposes
     * @param ResultLayoutFactory           $resultLayoutFactory           parent class purposes
     * @param PageFactory                   $resultPageFactory             parent class purposes
     * @param ForwardFactory                $resultForwardFactory          parent class purposes
     * @param JsonFactory                   $resultJsonFactory             parent class purposes
     * @param AuthHelper                    $authHelper                    Helper of the current extension
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        FileFactory $fileFactory,
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory,
        FormFactory $formFactory,
        SubscriberFactory $subscriberFactory,
        View $viewHelper,
        Random $random,
        CustomerRepositoryInterface $customerRepository,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        Mapper $addressMapper,
        AccountManagementInterface $customerAccountManagement,
        AddressRepositoryInterface $addressRepository,
        CustomerInterfaceFactory $customerDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        CustomerMapper $customerMapper,
        DataObjectProcessor $dataObjectProcessor,
        DataObjectHelper $dataObjectHelper,
        ObjectFactory $objectFactory,
        LayoutFactory $layoutFactory,
        ResultLayoutFactory $resultLayoutFactory,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        JsonFactory $resultJsonFactory,
        AuthHelper $authHelper
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $customerFactory,
            $addressFactory,
            $formFactory,
            $subscriberFactory,
            $viewHelper,
            $random,
            $customerRepository,
            $extensibleDataObjectConverter,
            $addressMapper,
            $customerAccountManagement,
            $addressRepository,
            $customerDataFactory,
            $addressDataFactory,
            $customerMapper,
            $dataObjectProcessor,
            $dataObjectHelper,
            $objectFactory,
            $layoutFactory,
            $resultLayoutFactory,
            $resultPageFactory,
            $resultForwardFactory,
            $resultJsonFactory
        );

        $this->authHelper = $authHelper;
    }

    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Fiko_CustomerTwoFactorAuth::generate_secret_key';

    /**
     * Generate Secret Key handler.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $customerId = $this->getRequest()->getParam('id');
            $customer = $this->authHelper->getCustomer($customerId);

            if ($this->authHelper->isOtpEnabled($customer)) {
                $resultRedirect->setPath('customer/index/edit', ['id' => $customerId]);
                $this->messageManager->addErrorMessage(
                    __('Two factor Authentication already enabled for this customer.')
                );

                return $resultRedirect;
            }

            $generatedSecretKey = $this->authHelper->generateSecret();

            $customer->setCustomAttribute(AuthHelper::TOTP_SECRET, $generatedSecretKey);
            $this->authHelper->customerRepository->save($customer);

            $resultRedirect->setPath('customer/index/edit', ['id' => $customerId]);
            $this->messageManager->addSuccessMessage(
                __('Two Factor Authentication secret key generated with the new one.')
            );

            return $resultRedirect;
        } catch (Exception $e) {
            $resultRedirect->setPath('customer/index/edit', ['id' => $customerId]);
            $this->messageManager->addErrorMessage($e->getMessage());

            return $resultRedirect;
        }
    }
}
