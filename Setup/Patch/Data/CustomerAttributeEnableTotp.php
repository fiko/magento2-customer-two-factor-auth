<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Setup\Patch\Data;

use Fiko\CustomerTwoFactorAuth\Helper\Data as AuthHelper;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Customer Enable OTP attribute class.
 */
class CustomerAttributeEnableTotp implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * AccountPurposeCustomerAttribute constructor.
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        Config $eavConfig,
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->setup = $setup;
        $this->eavConfig = $eavConfig;
    }

    /**
     * The code that you want apply in the patch
     * Please note, that one patch is responsible only for one setup version
     * So one UpgradeData can consist of few data patches.
     *
     * @return void
     */
    public function apply()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->setup]);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = $customerSetup->getDefaultAttributeSetId($customerEntity->getEntityTypeId());
        $attributeGroup = $customerSetup->getDefaultAttributeGroupId(
            $customerEntity->getEntityTypeId(),
            $attributeSetId
        );

        $customerSetup->addAttribute(Customer::ENTITY, AuthHelper::IS_ENABLE, [
            'type' => 'int',
            'input' => 'boolean',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'label' => 'Enable 2FA',
            'required' => false,
            'default' => 0,
            'visible' => true,
            'user_defined' => false,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 300,
        ]);

        $newAttribute = $this->eavConfig->getAttribute(Customer::ENTITY, AuthHelper::IS_ENABLE);
        $newAttribute->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup,
        ]);

        $newAttribute->save();
    }

    /**
     * Here should go code that will revert all operations from `apply` method
     * Please note, that some operations, like removing data from column, that is in role of foreign key reference
     * is dangerous, because it can trigger ON DELETE statement.
     *
     * @return void
     */
    public function revert()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->setup]);

        $customerSetup->removeAttribute(Customer::ENTITY, AuthHelper::IS_ENABLE);
    }

    /**
     * This internal Magento method, that means that some patches with time can change their names,
     * but changing name should not affect installation process, that's why if we will change name of the patch
     * we will add alias here.
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * This is dependency to another patch. Dependency should be applied first
     * One patch can have few dependencies
     * Patches do not have versions, so if in old approach with Install/Ugrade data scripts you used
     * versions, right now you need to point from patch with higher version to patch with lower version
     * But please, note, that some of your patches can be independent and can be installed in any sequence
     * So use dependencies only if this important for you.
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * This is version of the patch, it will be executed once the patch version is not lower than the module version.
     *
     * @return string
     */
    public static function getVersion()
    {
        return '1.0.0';
    }
}
