<?php

/**
 * SphinxSearch Fulltext Index resource model
 *
 * @category   Manticorp
 * @package    Manticorp_SphinxSearch
 * @author     Harry Mustoe-Playfair <h@hmp.is.it>
 */
class Manticorp_SphinxSearch_Model_Resource_Fulltext extends Mage_CatalogSearch_Model_Resource_Fulltext
{

    /**
     * Init resource model
     *
     */
    protected function _construct()
    {
        // engine is only important fpr indexing
        if (!Mage::getStoreConfigFlag('sphinxsearch/active/indexer')) {
            return parent::_construct();
        }

        $this->_init('catalogsearch/fulltext', 'product_id');
        $this->_engine = Mage::helper('sphinxsearch')->getEngine();
    }

    /**
     * Regenerate search index for store(s)
     *
     * @param  int|null $storeId
     * @param  int|array|null $productIds
     * @return Magendoo_Fulltext_Model_Resource_Fulltext
     */
    public function rebuildAllIndexes()
    {

        $storeIds = array_keys(Mage::app()->getStores());
        foreach ($storeIds as $storeId) {
            $this->_rebuildStoreIndex($storeId);
        }

        $adapter = $this->_getWriteAdapter();

        $this->_engine->swapTables();
        $adapter->truncateTable($this->getTable('catalogsearch/result'));
        $adapter->update($this->getTable('catalogsearch/search_query'), array('is_processed' => 0));
        // If we need to change directory
        $prevdir = getcwd();
        if(
            Mage::getStoreConfig('sphinxsearch/search/sphinxpath') !== ''
            && Mage::getStoreConfig('sphinxsearch/search/sphinxpath') !== null
            && is_dir(Mage::getStoreConfig('sphinxsearch/search/sphinxpath'))
        ){
            chdir(Mage::getStoreConfig('sphinxsearch/search/sphinxpath'));
        }

        // sphinx indexer
        $output = `indexer --rotate --all --noprogress --quiet`;
        chdir($prevdir);

        return $this;
    }

    /**
     * Regenerate search index for store(s)
     *
     * @param  int|null $storeId
     * @param  int|array|null $productIds
     * @return Mage_CatalogSearch_Model_Resource_Fulltext
     */
    public function rebuildIndex($storeId = null, $productIds = null)
    {

        // Use the parent rebuild method
        parent::rebuildIndex($storeId, $productIds);

        // If we need to change directory
        if(
            Mage::getStoreConfigFlag('sphinxsearch/search/sphinxpath') !== ''
            && Mage::getStoreConfigFlag('sphinxsearch/search/sphinxpath') !== null
            && is_dir(Mage::getStoreConfigFlag('sphinxsearch/search/sphinxpath'))
        ){
            chdir(Mage::getStoreConfigFlag('sphinxsearch/search/sphinxpath'));
        }

        // sphinx indexer
        $output = `indexer --rotate --all --noprogress --quiet`;

        return $this;
    }

    /**
     * Prepare results for query
     *
     * @param  Mage_CatalogSearch_Model_Fulltext          $object
     * @param  string                                     $queryText
     * @param  Mage_CatalogSearch_Model_Query             $query
     * @return Mage_CatalogSearch_Model_Mysql4_Fulltext
     */
    public function prepareResult($object, $queryText, $query)
    {
        if (!Mage::getStoreConfigFlag('sphinxsearch/active/frontend')) {
            return parent::prepareResult($object, $queryText, $query);
        }

        $sphinx = Mage::helper('sphinxsearch')->getSphinxAdapter();

        $index = Mage::getStoreConfig('sphinxsearch/server/index');

        // Here we escape the query - this is important, because certain characters
        // will return an error otherwise!
        // $queryText = $sphinx->EscapeString($queryText);
        $queryText = str_replace('/','\\/',$queryText);

        if (empty($index)) {
            $sphinx->AddQuery($queryText);
        } else {
            $sphinx->AddQuery($queryText, $index);
        }

        $results = $sphinx->RunQueries();

        // Loop through our Sphinx results
        if ($results !== false) {
            $resultTable = $this->getTable('catalogsearch/result');
            foreach ($results as $item) {
                if (empty($item['matches'])) {
                    continue;
                }

                foreach ($item['matches'] as $doc => $docinfo) {
                    // Ensure we log query results into the Magento table.
                    $weight = $docinfo['weight'] / 1000;
                    $sql    = sprintf("INSERT INTO `%s` "
                        . " (`query_id`, `product_id`, `relevance`) VALUES "
                        . " (%d, %d, %f) "
                        . " ON DUPLICATE KEY UPDATE `relevance` = %f",
                        $resultTable,
                        $query->getId(),
                        $doc,
                        $weight,
                        $weight
                    );
                    try {
                        $this->_getWriteAdapter()->query($sql);
                    } catch (Zend_Db_Statement_Exception $e) {
                        /*
                         * if the sphinx index is out of date and returns
                         * product ids which are no longer in the database
                         * integrity contraint exceptions are thrown.
                         * we catch them here and simply skip them.
                         * all other exceptions are forwarded
                         */
                        $message = $e->getMessage();
                        if (strpos($message, 'SQLSTATE[23000]: Integrity constraint violation') === false) {
                            throw $e;
                        }
                    }
                }
            }
        }

        $query->setIsProcessed(1);
        return $this;
    }

    /**
     * Prepare Fulltext index value for product
     *
     * @param  array    $indexData
     * @param  array    $productData
     * @param  int      $storeId
     * @return string
     */
    protected function _prepareProductIndex($indexData, $productData, $storeId)
    {
        if (!Mage::getStoreConfigFlag('sphinxsearch/active/indexer')) {
            return parent::_prepareProductIndex($indexData, $productData, $storeId);
        }

        $index = array();

        foreach ($this->_getSearchableAttributes('static') as $attribute) {
            $attributeCode = $attribute->getAttributeCode();

            if (isset($productData[$attributeCode])) {
                $value = $this->_getAttributeValue($attribute->getId(), $productData[$attributeCode], $storeId);
                if ($value) {
                    //For grouped products
                    if (isset($index[$attributeCode])) {
                        if (!is_array($index[$attributeCode])) {
                            $index[$attributeCode] = array($index[$attributeCode]);
                        }
                        $index[$attributeCode][] = $value;
                    }
                    //For other types of products
                    else {
                        $index[$attributeCode] = $value;
                    }
                }
            }
        }

        foreach ($indexData as $entityId => $attributeData) {
            foreach ($attributeData as $attributeId => $attributeValue) {
                $value = $this->_getAttributeValue($attributeId, $attributeValue, $storeId);
                if (!is_null($value) && $value !== false) {
                    $attributeCode = $this->_getSearchableAttribute($attributeId)->getAttributeCode();

                    if (isset($index[$attributeCode])) {
                        $index[$attributeCode][$entityId] = $value;
                    } else {
                        $index[$attributeCode] = array($entityId => $value);
                    }
                }
            }
        }

        if (!$this->_engine->allowAdvancedIndex()) {
            $product = $this->_getProductEmulator()
                            ->setId($productData['entity_id'])
                            ->setTypeId($productData['type_id'])
                            ->setStoreId($storeId);
            $typeInstance = $this->_getProductTypeInstance($productData['type_id']);
            if ($data = $typeInstance->getSearchableData($product)) {
                $index['options'] = $data;
            }
        }

        if (isset($productData['in_stock'])) {
            $index['in_stock'] = $productData['in_stock'];
        }

        return $this->_engine->prepareEntityIndex($index, $this->_separator, $productData['entity_id']);
    }

}
