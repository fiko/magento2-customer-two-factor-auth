<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Block\Adminhtml\Edit;

use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Backend\Block\Widget\Context;
use Magento\Customer\Block\Adminhtml\Edit\GenericButton;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Generate 2FA Secret Key Button Block.
 */
class GenerateSecretKeyButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @var RequestInterface
     */
    public $request;

    /**
     * @var AuthorizationInterface
     */
    public $authorization;

    /**
     * @var AuthHelper
     */
    public $authHelper;

    /**
     * Constructor.
     *
     * @param Context                $context       Parent class purposes
     * @param Registry               $registry      Parent class purposes
     * @param RequestInterface       $request       Class to read the requests
     * @param AuthorizationInterface $authorization Class to get the authorization / ACL
     * @param AuthHelper             $authHelper    Current extension helper
     */
    public function __construct(
        Context $context,
        Registry $registry,
        RequestInterface $request,
        AuthorizationInterface $authorization,
        AuthHelper $authHelper
    ) {
        parent::__construct($context, $registry);

        $this->request = $request;
        $this->authorization = $authorization;
        $this->authHelper = $authHelper;
    }

    /**
     * Button Handler.
     *
     * @return array
     */
    public function getButtonData()
    {
        $customerId = $this->request->getParam('id');
        $customer = $this->authHelper->getCustomer($customerId);
        $deleteConfirmMsg = __("Are you sure you want to re-generate the customer's 2FA secret key?");

        if (
            $this->authHelper->isOtpEnabled($customer) ||
            $this->authorization->isAllowed(AuthHelper::ACL_GENERATE_SECRET_KEY) === false
        ) {
            return [];
        }

        return [
            'label' => __('Generate 2FA Secret Key'),
            'on_click' => 'deleteConfirm("'.$deleteConfirmMsg.'", "'.$this->getGenerateSecretKeyUrl().'")',
            'class' => 'fiko-generate-2fa-key',
            'sort_order' => 50,
        ];
    }

    /**
     * Get URL for generate secret key button.
     *
     * @return string
     */
    public function getGenerateSecretKeyUrl()
    {
        return $this->getUrl('customer/loginsecurity/generatesecretkey', ['id' => $this->getCustomerId()]);
    }
}
