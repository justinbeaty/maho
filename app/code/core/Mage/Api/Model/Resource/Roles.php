<?php

/**
 * Maho
 *
 * @package    Mage_Api
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024-2025 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Api_Model_Resource_Roles extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * User table name
     *
     * @var string
     */
    protected $_usersTable;

    /**
     * Rule table name
     *
     * @var string
     */
    protected $_ruleTable;

    #[\Override]
    protected function _construct()
    {
        $this->_init('api/role', 'role_id');

        $this->_usersTable = $this->getTable('api/user');
        $this->_ruleTable  = $this->getTable('api/rule');
    }

    /**
     * Process role before saving
     *
     * @return $this
     */
    #[\Override]
    protected function _beforeSave(Mage_Core_Model_Abstract $role)
    {
        if ($role->getId() == '') {
            if ($role->getIdFieldName()) {
                $role->unsetData($role->getIdFieldName());
            } else {
                $role->unsetData('id');
            }
        }

        if ($role->getPid() > 0) {
            $row = $this->load($role->getPid());
        } else {
            $row = ['tree_level' => 0];
        }

        $role->setTreeLevel($row['tree_level'] + 1);
        $role->setRoleName($role->getName());

        return $this;
    }

    /**
     * Action after save
     *
     * @return $this
     */
    #[\Override]
    protected function _afterSave(Mage_Core_Model_Abstract $role)
    {
        $this->_updateRoleUsersAcl($role);
        Mage::app()->getCache()->clean();
        return $this;
    }

    /**
     * Action after delete
     *
     * @return $this
     */
    #[\Override]
    protected function _afterDelete(Mage_Core_Model_Abstract $role)
    {
        $adapter = $this->_getWriteAdapter();
        $adapter->delete($this->getMainTable(), ['parent_id = ?' => (int) $role->getId()]);
        $adapter->delete($this->_ruleTable, ['role_id = ?' => (int) $role->getId()]);
        return $this;
    }

    /**
     * Get role users
     *
     * @return array
     */
    public function getRoleUsers(Mage_Api_Model_Roles $role)
    {
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getMainTable(), ['user_id'])
            ->where('parent_id = ?', $role->getId())
            ->where('role_type = ?', Mage_Api_Model_Acl::ROLE_TYPE_USER)
            ->where('user_id > 0');
        return $adapter->fetchCol($select);
    }

    /**
     * Update role users
     *
     * @return bool
     */
    private function _updateRoleUsersAcl(Mage_Api_Model_Roles $role)
    {
        $users = $this->getRoleUsers($role);
        $rowsCount = 0;

        if (count($users)) {
            $rowsCount = $this->_getWriteAdapter()->update(
                $this->_usersTable,
                ['reload_acl_flag' => 1],
                ['user_id IN (?)' => $users],
            );
        }

        return $rowsCount > 0;
    }
}
