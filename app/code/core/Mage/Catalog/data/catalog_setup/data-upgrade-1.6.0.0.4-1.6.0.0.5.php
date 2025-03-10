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

/** @var Mage_Catalog_Model_Resource_Setup $this */
$installer = $this;

/** @var Mage_Catalog_Model_Resource_Eav_Attribute $eavResource */
$eavResource = Mage::getResourceModel('catalog/eav_attribute');

$multiSelectAttributeCodes = $eavResource->getAttributeCodesByFrontendType('multiselect');

foreach ($multiSelectAttributeCodes as $attributeCode) {
    /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
    $attribute = $installer->getAttribute('catalog_product', $attributeCode);
    if ($attribute) {
        $attributeTable = $installer->getAttributeTable('catalog_product', $attributeCode);
        $select = $installer->getConnection()->select()
            ->from(['e' => $attributeTable])
            ->where('e.attribute_id=?', $attribute['attribute_id'])
            ->where('e.value LIKE "%,,%"');
        $result = $installer->getConnection()->fetchAll($select);

        if ($result) {
            foreach ($result as $row) {
                if (isset($row['value']) && !empty($row['value'])) {
                    // 1,2,,,3,5 --> 1,2,3,5
                    $row['value'] = preg_replace('/,{2,}/', ',', $row['value'], -1, $replaceCnt);

                    if ($replaceCnt) {
                        $installer->getConnection()
                            ->update($attributeTable, ['value' => $row['value']], 'value_id=' . $row['value_id']);
                    }
                }
            }
        }
    }
}
