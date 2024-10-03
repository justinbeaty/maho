<?php
/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Core
 * @copyright  @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
                   ->newTable('test_table')
                   ->addColumn('test_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
                       'identity' => true,
                       'primary'  => true,
                       'nullable' => false,
                       'unsigned' => true,
                   ]);
$installer->getConnection()->createTable($table);

$installer->endSetup();
