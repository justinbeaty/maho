<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Block_Permissions_Tab_Rolesusers extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();

        $roleId = $this->getRequest()->getParam('rid', false);

        $users = Mage::getModel('admin/user')->getCollection()->load();
        $this->setTemplate('permissions/rolesusers.phtml')
            ->assign('users', $users->getItems())
            ->assign('roleId', $roleId);
    }

    #[\Override]
    protected function _prepareLayout()
    {
        $this->setChild('userGrid', $this->getLayout()->createBlock('adminhtml/permissions_role_grid_user', 'roleUsersGrid'));
        return parent::_prepareLayout();
    }

    protected function _getGridHtml()
    {
        return $this->getChildHtml('userGrid');
    }

    protected function _getJsObjectName()
    {
        return $this->getChild('userGrid')->getJsObjectName();
    }
}
