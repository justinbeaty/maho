<?php

/**
 * Maho
 *
 * @package    Mage_CatalogSearch
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/** @var Mage_Core_Model_Resource_Setup $this */
$installer = $this;
$installer->startSetup();

/**
 * Create table 'catalogsearch/search_query'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('catalogsearch/search_query'))
    ->addColumn('query_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Query ID')
    ->addColumn('query_text', Varien_Db_Ddl_Table::TYPE_TEXT, 255, [
    ], 'Query text')
    ->addColumn('num_results', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ], 'Num results')
    ->addColumn('popularity', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ], 'Popularity')
    ->addColumn('redirect', Varien_Db_Ddl_Table::TYPE_TEXT, 255, [
    ], 'Redirect')
    ->addColumn('synonym_for', Varien_Db_Ddl_Table::TYPE_TEXT, 255, [
    ], 'Synonym for')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ], 'Store ID')
    ->addColumn('display_in_terms', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'nullable'  => false,
        'default'   => '1',
    ], 'Display in terms')
    ->addColumn('is_active', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'default'   => '1',
    ], 'Active status')
    ->addColumn('is_processed', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'default'   => '0',
    ], 'Processed status')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, [
        'nullable'  => false,
    ], 'Updated at')
    ->addIndex(
        $installer->getIdxName('catalogsearch/search_query', ['query_text','store_id','popularity']),
        ['query_text','store_id','popularity'],
    )
    ->addIndex($installer->getIdxName('catalogsearch/search_query', 'store_id'), 'store_id')
    ->addForeignKey(
        $installer->getFkName('catalogsearch/search_query', 'store_id', 'core/store', 'store_id'),
        'store_id',
        $installer->getTable('core/store'),
        'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->setComment('Catalog search query table');
$installer->getConnection()->createTable($table);

/**
 * Create table 'catalogsearch/result'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('catalogsearch/result'))
    ->addColumn('query_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Query ID')
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Product ID')
    ->addColumn('relevance', Varien_Db_Ddl_Table::TYPE_DECIMAL, '20,4', [
        'nullable'  => false,
        'default'   => '0.0000',
    ], 'Relevance')
    ->addIndex($installer->getIdxName('catalogsearch/result', 'query_id'), 'query_id')
    ->addForeignKey(
        $installer->getFkName('catalogsearch/result', 'query_id', 'catalogsearch/search_query', 'query_id'),
        'query_id',
        $installer->getTable('catalogsearch/search_query'),
        'query_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->addIndex($installer->getIdxName('catalogsearch/result', 'product_id'), 'product_id')
    ->addForeignKey(
        $installer->getFkName('catalogsearch/result', 'product_id', 'catalog/product', 'entity_id'),
        'product_id',
        $installer->getTable('catalog/product'),
        'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->setComment('Catalog search result table');
$installer->getConnection()->createTable($table);

/**
 * Create table 'catalogsearch/fulltext'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('catalogsearch/fulltext'))
    ->addColumn('fulltext_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Entity ID')
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned'  => true,
        'nullable'  => false,
    ], 'Product ID')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
    ], 'Store ID')
    ->addColumn('data_index', Varien_Db_Ddl_Table::TYPE_TEXT, '4g', [
    ], 'Data index')
    ->addIndex(
        $installer->getIdxName(
            'catalogsearch/fulltext',
            ['product_id', 'store_id'],
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE,
        ),
        ['product_id', 'store_id'],
        ['type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE],
    )
    ->addIndex(
        $installer->getIdxName(
            'catalogsearch/fulltext',
            'data_index',
            Varien_Db_Adapter_Interface::INDEX_TYPE_FULLTEXT,
        ),
        'data_index',
        ['type' => Varien_Db_Adapter_Interface::INDEX_TYPE_FULLTEXT],
    )
    ->setOption('type', 'MyISAM')
    ->setComment('Catalog search result table');
$installer->getConnection()->createTable($table);

$installer->endSetup();
