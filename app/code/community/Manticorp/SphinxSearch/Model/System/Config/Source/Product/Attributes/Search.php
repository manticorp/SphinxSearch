<?php
/**
 * This class defines a source model for product attributes.
 *
 * Basically just gets all product attributes and defines a
 * toOptionArray method for use in source models
 */
class Manticorp_SphinxSearch_Model_System_Config_Source_Product_Attributes_Search
{
    /**
     * @return mixed
     */
    public function toOptionArray()
    {
        $options      = array();
        $atts = $this->getSearchableAttributes();
        foreach($atts as $attribute){
            $item          = array();
            $item['value'] = $attribute->getAttributeCode();
            $item['label'] = $attribute->getAttributeCode();
            $options[] = $item;
        }
        return $options;
    }

    public function getSearchableAttributes()
    {
        $searchableAttributes = array();

        $productAttributeCollection = Mage::getResourceModel('catalog/product_attribute_collection');
        $productAttributeCollection->addSearchableAttributeFilter();
        $attributes = $productAttributeCollection->getItems();

        Mage::dispatchEvent('catalogsearch_searchable_attributes_load_after', array(
            'engine' => $this->_engine,
            'attributes' => $attributes
        ));

        $entity = $this->getEavConfig()
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)
            ->getEntity();

        foreach ($attributes as $attribute) {
            $attribute->setEntity($entity);
        }

        $searchableAttributes = $attributes;

        if (!is_null($backendType)) {
            $attributes = array();
            foreach ($searchableAttributes as $attributeId => $attribute) {
                if ($attribute->getBackendType() == $backendType) {
                    $attributes[$attributeId] = $attribute;
                }
            }

            return $attributes;
        }

        return $searchableAttributes;
    }

    /**
     * Retrieve EAV Config Singleton
     *
     * @return Mage_Eav_Model_Config
     */
    public function getEavConfig()
    {
        return Mage::getSingleton('eav/config');
    }
}
