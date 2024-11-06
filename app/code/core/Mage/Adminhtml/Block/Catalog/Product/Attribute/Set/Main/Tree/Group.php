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
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Catalog_Product_Attribute_Set_Main_Tree_Group extends Mage_Eav_Block_Adminhtml_Attribute_Set_Main_Tree_Group
{
    #[\Override]
    protected function _construct()
    {
        // TODO, merge and delete
        $this->setTemplate('catalog/product/attribute/set/main/tree/group.phtml');
    }
}
