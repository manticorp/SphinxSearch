<?php
/**
 * This class defines a source model for product attributes.
 *
 * Basically just gets all product attributes and defines a
 * toOptionArray method for use in source models
 */
class Manticorp_Stockcontrol_Model_System_Config_Source_Product_Attributes
{
    /**
     * @return mixed
     */
    public function toOptionArray()
    {
        $options      = array();
        $entityTypeId = Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getEntityTypeId();
        $attributes   = Mage::getModel('eav/entity_attribute')
            ->getCollection()
            ->addFilter('entity_type_id', $entityTypeId)
            ->addFilter('is_user_defined', 1)
            ->addFilter('is_unique', 0)
            // ->addFilter('frontend_input', 'text')
            ->addFieldToFilter('backend_type', array(array('eq'=>'int'),array('eq'=>'decimal')))
            ->setOrder('attribute_code', 'ASC');
        foreach ($attributes as $attribute) {
            $item          = array();
            $item['value'] = $attribute->getAttributeCode();
            $item['label'] = $attribute->getAttributeCode();
            $options[] = $item;
        }
        return $options;
    }
}
