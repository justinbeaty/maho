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

class Mage_Adminhtml_Block_Api_User_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'user_id';
        $this->_controller = 'api_user';

        parent::__construct();

        $this->_updateButton('save', 'label', Mage::helper('adminhtml')->__('Save User'));
        $this->_updateButton('delete', 'label', Mage::helper('adminhtml')->__('Delete User'));
        $this->_updateButton('delete', 'onclick', 'if(confirm(\'' . Mage::helper('core')->jsQuoteEscape(
            Mage::helper('adminhtml')->__('Are you sure you want to do this?'),
        ) . '\')) editForm.submit(\'' . $this->getUrl('*/*/delete') . '\'); return false;');
    }

    /**
     * @return string
     */
    #[\Override]
    public function getHeaderText()
    {
        if (Mage::registry('api_user')->getId()) {
            return Mage::helper('adminhtml')->__("Edit User '%s'", $this->escapeHtml(Mage::registry('api_user')->getUsername()));
        }
        return Mage::helper('adminhtml')->__('New User');
    }
}
