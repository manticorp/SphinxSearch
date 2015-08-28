<?php

/**
 * SphinxSearch Fulltext Index Engine resource model
 *
 * @category   Manticorp
 * @package    Manticorp_SphinxSearch
 * @author     Harry Mustoe-Playfair <h@hmp.is.it>
 */
class Manticorp_SphinxSearch_Model_Resource_Fulltext_Engine extends Mage_CatalogSearch_Model_Resource_Fulltext_Engine
{

    protected $_tmpTable  = null;

    /**
     * Multi add entities data to fulltext search table
     *
     * @param int $storeId
     * @param array $entityIndexes
     * @param string $entity 'product'|'cms'
     * @return Mage_CatalogSearch_Model_Mysql4_Fulltext_Engine
     */
    public function saveEntityIndexes($storeId, $entityIndexes, $entity = 'product')
    {
            $adapter = $this->_getWriteAdapter();
            $data   = array();
            $storeId = (int)$storeId;
            foreach ($entityIndexes as $entityId => &$index) {
                    $data[] = array(
                            'product_id'      => (int)$entityId,
                            'store_id'        => $storeId,
                            'data_index'      => $index['data_index'],
                            'name'            => $index['name'],
                            'name_attributes' => $index['name_attributes'],
                            'category'        => $index['category'],
                            'sku'             => $index['sku'],
                    );
            }

            if ($data) {
                $adapter->insertOnDuplicate(
                    $this->getTempTable(),
                    $data,
                    array('data_index', 'name', 'name_attributes', 'category', 'sku')
                );
            }

            return $this;
    }

    public function getMainTable()
    {
        $tablename = Mage::getSingleton('core/resource')->getTableName('sphinx_catalogsearch_fulltext');
        return $tablename;
        // return $this->getTable('sphinxsearch/catalogsearch_fulltext');
    }


    public function swapTables() {
        $adapter = $this->_getWriteAdapter();
        $mainTable  = $this->getMainTable();
        $prevTable  = $this->getMainTable().'_prev';
        $tempTable  = $this->getTempTable();

        $adapter->dropTable($prevTable);
        $adapter->query("RENAME TABLE `{$mainTable}` TO `{$prevTable}`,`{$tempTable}` TO `{$mainTable}`");
        $adapter->query("DROP TABLE IF EXISTS `{$prevTable}`");
    }



    public function getTempTable() {
        if(is_null($this->_tmpTable)) {
            $mainTable = $this->getMainTable();
            $this->_tmpTable = $mainTable.'_tmp';
            $this->_getWriteAdapter()->dropTable($this->_tmpTable);
            $this->_getWriteAdapter()->query("CREATE TABLE `{$this->_tmpTable}` LIKE  `{$mainTable}`");
        }
        return $this->_tmpTable;
    }

    /**
     * Remove entity data from fulltext search table
     *
     * @param int $storeId
     * @param int $entityId
     * @param string $entity 'product'|'cms'
     * @return Mage_CatalogSearch_Model_Mysql4_Fulltext_Engine
     */
    public function cleanIndex($storeId = null, $entityId = null, $entity = 'product')
    {
        $where = array();

        if (!is_null($storeId)) {
            $where[] = $this->_getWriteAdapter()->quoteInto('store_id=?', $storeId);
        }
        if (!is_null($entityId)) {
            $where[] = $this->_getWriteAdapter()->quoteInto('product_id IN(?)', $entityId);
        }

        $this->_getWriteAdapter()->delete($this->getMainTable(), join(' AND ', $where));

        return $this;
    }

    /**
     * Prepare index array as a string glued by separator
     *
     * @param array $index
     * @param string $separator
     * @return string
     */
    public function prepareEntityIndex($index, $separator = ' ', $entity_id = NULL)
    {
        return Mage::helper('sphinxsearch')->prepareIndexdata($index, $separator, $entity_id);
    }
}