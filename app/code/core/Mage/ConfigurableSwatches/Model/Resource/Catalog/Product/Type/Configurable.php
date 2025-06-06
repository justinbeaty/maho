<?php

/**
 * Maho
 *
 * @package    Mage_ConfigurableSwatches
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_ConfigurableSwatches_Model_Resource_Catalog_Product_Type_Configurable extends Mage_Catalog_Model_Resource_Product_Type_Configurable
{
    /**
     * Retrieve Required children ids
     * Grouped by parent id.
     *
     * @param mixed $parentId may be array of integers or scalar integer.
     * @param bool $required
     * @return array
     * @see Mage_Catalog_Model_Resource_Product_Type_Configurable::getChildrenIds()
     */
    #[\Override]
    public function getChildrenIds($parentId, $required = true)
    {
        if (is_array($parentId)) {
            $childrenIds = [];
            if (!empty($parentId)) {
                $select = $this->_getReadAdapter()->select()
                    ->from(['l' => $this->getMainTable()], ['product_id', 'parent_id'])
                    ->join(
                        ['e' => $this->getTable('catalog/product')],
                        'e.entity_id = l.product_id AND e.required_options = 0',
                        [],
                    )
                    ->where('parent_id IN (?)', $parentId);

                foreach ($this->_getReadAdapter()->fetchAll($select) as $row) {
                    $childrenIds[$row['parent_id']][$row['product_id']] = $row['product_id'];
                }
            }
            return $childrenIds;
        } else {
            return parent::getChildrenIds($parentId, $required);
        }
    }
}
