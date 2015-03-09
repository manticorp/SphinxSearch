<?php
/**
 * SphinxSearch Upgrade Script
 * 
 * @category   Manticorp
 * @package    Manticorp_SphinxSearch
 *
 * @author     Harry Mustoe-Playfair <h@hmp.is.it>
 */

/* $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$connection->addColumn(
    $installer->getTable('sphinx_catalogsearch_fulltext'),
    'sku',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'position'  => 3,
        'nullable'  => false,
        'comment'   => 'SKU'
    )
);

$installer->endSetup();