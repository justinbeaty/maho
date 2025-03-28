<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Permissions_UsernRoles extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $userCollection = Mage::getModel('permissions/users')->getCollection()->load();
        $rolesCollection = Mage::getModel('permissions/roles')->getCollection()->load();

        $this->setTemplate('permissions/usernroles.phtml')
            ->assign('users', $userCollection)
            ->assign('roles', $rolesCollection);
    }
}
