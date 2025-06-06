<?php

/**
 * Maho
 *
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Catalog_Model_Product_Attribute_Backend_Groupprice extends Mage_Catalog_Model_Product_Attribute_Backend_Groupprice_Abstract
{
    /**
     * Retrieve resource instance
     *
     * @return Mage_Catalog_Model_Resource_Product_Attribute_Backend_Groupprice
     */
    protected function _getResource()
    {
        return Mage::getResourceSingleton('catalog/product_attribute_backend_groupprice');
    }

    /**
     * Error message when duplicates
     *
     * @return string
     */
    #[\Override]
    protected function _getDuplicateErrorMessage()
    {
        return Mage::helper('catalog')->__('Duplicate website group price customer group.');
    }
}
