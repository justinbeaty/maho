<?php

/**
 * Maho
 *
 * @package    Mage_Bundle
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installFile = __DIR__ . DS . 'upgrade-1.6.0.0-1.6.0.0.1.php';
if (file_exists($installFile)) {
    include $installFile;
}

/** @var Mage_Catalog_Model_Resource_Setup $this */
$installer = $this;

/** @var Varien_Db_Adapter_Pdo_Mysql $connection */
$connection = $installer->getConnection();

$memoryTables = [
    'bundle/option_indexer_tmp',
    'bundle/selection_indexer_tmp',
    'bundle/price_indexer_tmp',
];

foreach ($memoryTables as $table) {
    $connection->changeTableEngine($installer->getTable($table), Varien_Db_Adapter_Pdo_Mysql::ENGINE_MEMORY);
}
