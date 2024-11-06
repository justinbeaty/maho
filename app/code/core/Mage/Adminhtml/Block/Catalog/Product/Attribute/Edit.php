<?php
/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Product attribute edit page
 */
class Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit extends Mage_Eav_Block_Adminhtml_Attribute_Edit
{
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'adminhtml';
        $this->_controller = 'catalog_product_attribute';

        if ($this->getRequest()->getParam('popup')) {
            $this->_removeButton('back');
            $this->_addButton('close', [
                'label'     => Mage::helper('catalog')->__('Close Window'),
                'class'     => 'cancel',
                'onclick'   => 'window.close()',
                'level'     => -1
            ]);
        } else {
            $this->_addButton('save_and_edit_button', [
                'label'     => Mage::helper('catalog')->__('Save and Continue Edit'),
                'onclick'   => 'saveAndContinueEdit()',
                'class'     => 'save'
            ], 100);
        }
    }
}
