<?php
namespace Boxalino\CemSearch\Block\Product;
class Boxalino_CemSearch_Block_Product_List extends Magento\Catalog\Block\Product\ListProduct
{
    /**
     * Retrieve loaded category collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected function _getProductCollection()
    {
        if (Mage::getStoreConfig('Boxalino_General/general/enabled') == 0) {
            return parent::_getProductCollection();
        }

        // make sure to only use products which are in the current category
        if ($category = Mage::registry('current_category')) {
            if (!$category->getIsAnchor()) {
                return parent::_getProductCollection();
            }
        }

        if (is_null($this->_productCollection)) {
            $entity_ids = Mage::helper('Boxalino_CemSearch')->getSearchAdapter()->getEntitiesIds();

            $this->_productCollection = Mage::getResourceModel('catalog/product_collection');

            // Added check if there are any entity ids, otherwise force empty result
            if (count($entity_ids) == 0) {
                $entity_ids = array(0);
            }
            $this->_productCollection->addFieldToFilter('entity_id', $entity_ids)
                 ->addAttributeToSelect('*');

            // enforce boxalino ranking
            $this->_productCollection->getSelect()->order(new Zend_Db_Expr('FIELD(e.entity_id,' . implode(',', $entity_ids).')'));

            if (Mage::helper('catalog')->isModuleEnabled('Mage_Checkout')) {
                Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter($this->_productCollection,
                    Mage::getSingleton('checkout/session')->getQuoteId()
                );
                $this->_addProductAttributesAndPrices($this->_productCollection);
            }
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_productCollection);

            // ensure data is loaded
            $this->_productCollection->load();
        }

        return $this->_productCollection;
    }
}
