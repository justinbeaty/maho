<?php
/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml Catalog Attribute Set Main Block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Catalog_Product_Attribute_Set_Main extends Mage_Eav_Block_Adminhtml_Attribute_Set_Main
{
    /**
     * Initialize template
     */
    #[\Override]
    protected function _construct()
    {
        // TODO, merge and delete
        $this->setTemplate('catalog/product/attribute/set/main.phtml');
    }

    /**
     * Prepare Global Layout
     *
     * @return $this
     */
    #[\Override]
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setChild(
            'group_tree',
            $this->getLayout()->createBlock('adminhtml/catalog_product_attribute_set_main_tree_group')
        );

        $this->setChild(
            'edit_set_form',
            $this->getLayout()->createBlock('adminhtml/catalog_product_attribute_set_main_formset')
        );
    }
}
