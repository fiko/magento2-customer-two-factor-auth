<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Block\Adminhtml\Edit;

use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Backend\Block\Widget\Context;
use Magento\Customer\Block\Adminhtml\Edit\GenericButton;
use Magento\Framework\Registry;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class BackButton.
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
        AuthHelper $authHelper
    ) {
        parent::__construct($context, $registry);

        $this->request = $request;
        $this->authHelper = $authHelper;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $customerId = $this->request->getParam('id');
        $customer = $this->authHelper->getCustomer($customerId);
        if ($this->authHelper->isOtpEnabled($customer)) {
            return [];
        }

        return [
            'label' => __('Generate 2FA Secret Key'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class' => 'fiko-generate-2fa-key',
            'sort_order' => 50,
        ];
    }

    /**
     * Get URL for back (reset) button.
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }
}
