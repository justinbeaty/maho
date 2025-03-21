<?php

/**
 * Maho
 *
 * @package    Mage_Api2
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Block tabs for role edit page
 *
 * @package    Mage_Api2
 *
 * @method Mage_Api2_Model_Acl_Global_Role getRole()
 * @method $this setRole(Mage_Api2_Model_Acl_Global_Role $role)
 */
class Mage_Api2_Block_Adminhtml_Roles_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('role_info_tabs');
        $this->setDestElementId('role_edit_form');
        $this->setData('title', Mage::helper('api2')->__('Role Information'));
    }

    #[\Override]
    protected function _beforeToHtml()
    {
        $role = $this->getRole();
        if ($role && Mage_Api2_Model_Acl_Global_Role::isSystemRole($role)) {
            $this->setActiveTab('api2_role_section_resources');
        } else {
            $this->setActiveTab('api2_role_section_info');
        }
        return parent::_beforeToHtml();
    }
}
