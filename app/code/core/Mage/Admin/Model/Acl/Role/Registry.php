<?php

/**
 * Maho
 *
 * @package    Mage_Admin
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Admin_Model_Acl_Role_Registry extends Zend_Acl_Role_Registry
{
    /**
     * Add parent to the $role node
     *
     * @param Zend_Acl_Role_Interface|string $role
     * @param array|Zend_Acl_Role_Interface|string $parents
     * @return $this
     */
    public function addParent($role, $parents)
    {
        try {
            if ($role instanceof Zend_Acl_Role_Interface) {
                $roleId = $role->getRoleId();
            } else {
                $roleId = $role;
                $role = $this->get($role);
            }
        } catch (Zend_Acl_Role_Registry_Exception $e) {
            throw new Zend_Acl_Role_Registry_Exception("Child Role id '$roleId' does not exist");
        }

        if (!is_array($parents)) {
            $parents = [$parents];
        }
        foreach ($parents as $parent) {
            try {
                if ($parent instanceof Zend_Acl_Role_Interface) {
                    $roleParentId = $parent->getRoleId();
                } else {
                    $roleParentId = $parent;
                }
                $roleParent = $this->get($roleParentId);
            } catch (Zend_Acl_Role_Registry_Exception $e) {
                throw new Zend_Acl_Role_Registry_Exception("Parent Role id '$roleParentId' does not exist");
            }
            $this->_roles[$roleId]['parents'][$roleParentId] = $roleParent;
            $this->_roles[$roleParentId]['children'][$roleId] = $role;
        }
        return $this;
    }
}
