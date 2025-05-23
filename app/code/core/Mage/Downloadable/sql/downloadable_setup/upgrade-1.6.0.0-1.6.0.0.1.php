<?php

/**
 * Maho
 *
 * @package    Mage_Downloadable
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2025 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/** @var Mage_Catalog_Model_Resource_Setup $this */
$installer = $this;

$msrpEnabled = $installer->getAttribute('catalog_product', 'msrp_enabled', 'apply_to');
if ($msrpEnabled && !str_contains($msrpEnabled, Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE)) {
    $installer->updateAttribute('catalog_product', 'msrp_enabled', [
        'apply_to'      => $msrpEnabled . ',' . Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
    ]);
}

$msrpDisplay = $installer->getAttribute('catalog_product', 'msrp_display_actual_price_type', 'apply_to');
if ($msrpDisplay && !str_contains($msrpEnabled, Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE)) {
    $installer->updateAttribute('catalog_product', 'msrp_display_actual_price_type', [
        'apply_to'      => $msrpDisplay . ',' . Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
    ]);
}

$msrp = $installer->getAttribute('catalog_product', 'msrp', 'apply_to');
if ($msrp && !str_contains($msrpEnabled, Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE)) {
    $installer->updateAttribute('catalog_product', 'msrp', [
        'apply_to'      => $msrp . ',' . Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
    ]);
}
