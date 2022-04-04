<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
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
 * Class Generate 2FA Secret Key
 */
class GenerateSecretKeyButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @param Context    $context    parent class requirement
     * @param Registry   $registry   parent class requirement
     * @param AuthHelper $authHelper This module helper
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
     * Get URL for back (reset) button.
     *
     * @return string
     */
    public function getGenerateSecretKeyUrl()
    {
        return $this->getUrl('customer/loginsecurity/generatesecretkey', ['id' => $this->getCustomerId()]);
    }
}
