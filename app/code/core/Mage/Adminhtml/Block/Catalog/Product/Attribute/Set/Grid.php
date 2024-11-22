<?php
/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Catalog_Product_Attribute_Set_Grid extends Mage_Eav_Block_Adminhtml_Attribute_Set_Grid
{
    public function __construct()
    {
        // For backwards compatibility, register entity_type if not already defined
        if (!Mage::registry('entity_type')) {
            $entityType = Mage::getSingleton('eav/config')->getEntityType(Mage_Catalog_Model_Product::ENTITY);
            Mage::register('entity_type', $entityType);
        }
        parent::__construct();
    }
}
