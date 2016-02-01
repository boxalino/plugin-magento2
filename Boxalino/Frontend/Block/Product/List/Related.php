<?php

/**
 * Created by: Szymon Nosal <szymon.nosal@boxalino.com>
 * Created at: 16.06.14 12:29
 */
class Boxalino_CemSearch_Block_Product_List_Related extends Mage_Catalog_Block_Product_List_Related
{
    protected function _prepareData()
    {

        if (Mage::getStoreConfig('Boxalino_General/general/enabled') == 0 || Mage::getStoreConfig('Boxalino_Recommendation/related/status', 0) == 0) {
            return parent::_prepareData();
        }
        $name = Mage::getStoreConfig('Boxalino_Recommendation/related/widget');

        $product = Mage::registry('product');
        /* @var $product Mage_Catalog_Model_Product */

##################################################################################

        $_REQUEST['productId'] = $product->getId();

        $p13nRecommendation = Boxalino_CemSearch_Helper_P13n_Recommendation::Instance();

        $response = $p13nRecommendation->getRecommendation('product', $name);
        $entityIds = array();

        if ($response === null) {
            $this->_itemCollection = new Varien_Data_Collection();
            return $this;
        }

        foreach ($response as $item) {
            $entityIds[] = $item[Mage::getStoreConfig('Boxalino_General/search/entity_id')];
        }

###############################################################
        $this->_itemCollection = Mage::getResourceModel('catalog/product_collection')
            ->addFieldToFilter('entity_id', $entityIds)
            ->addAttributeToSelect('*');

        if (Mage::helper('catalog')->isModuleEnabled('Mage_Checkout')) {
            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter($this->_itemCollection,
                Mage::getSingleton('checkout/session')->getQuoteId()
            );
            $this->_addProductAttributesAndPrices($this->_itemCollection);
        }
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_itemCollection);

        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }

}