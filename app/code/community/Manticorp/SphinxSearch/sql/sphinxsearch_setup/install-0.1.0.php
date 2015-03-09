<?php
/**
 * SphinxSearch Install Script
 *
 * @category   Manticorp
 * @package    Manticorp_SphinxSearch
 *
 * @author     Harry Mustoe-Playfair <h@hmp.is.it>
 */

$installer = $this;
/* $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$table = $installer->getConnection()
->newTable($installer->getTable('sphinx_catalogsearch_fulltext'))
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        ), 'Product ID')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable'  => false,
        ), 'Store ID')
    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'Product Name')
    ->addColumn('name_attributes', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'Product Name + selected attributes')
    ->addColumn('category', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => true,
        ), 'Categories (with separator)')
    ->addColumn('data_index', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'Original Magento data_index')
    ->addIndex(
        $installer->getIdxName(
            $installer->getTable('sphinx_catalogsearch_fulltext'),
            array('product_id','store_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_PRIMARY
        ),
        array('product_id','store_id'),
        array(
            'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_PRIMARY
        )
    )
    ->addIndex(
        $installer->getIdxName(
            $installer->getTable('sphinx_catalogsearch_fulltext'),
            array('data_index'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_FULLTEXT
        ),
        array('data_index'),
        array(
            'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_FULLTEXT
        )
    )->setOption('type','MyISAM');
$installer->getConnection()->createTable($table);

$installer->endSetup();