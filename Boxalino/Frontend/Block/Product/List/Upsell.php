<?php

/**
 * Created by: Szymon Nosal <szymon.nosal@boxalino.com>
 * Created at: 17.06.14 11:31
 */
class Boxalino_CemSearch_Block_Product_List_Upsell extends Mage_Catalog_Block_Product_List_Upsell
{
    /**
     * Default MAP renderer type
     *
     * @var string
     */
    protected $_mapRenderer = 'msrp_noform';

    protected $_itemCollection;

    protected function _prepareData()
    {

        if (Mage::getStoreConfig('Boxalino_General/general/enabled') == 0 || Mage::getStoreConfig('Boxalino_Recommendation/upsell/status', 0) == 0) {
            return parent::_prepareData();
        }
        $name = Mage::getStoreConfig('Boxalino_Recommendation/upsell/widget');

        $product = Mage::registry('product');
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

        if ($this->getItemLimit('upsell') > 0) {
            $this->_itemCollection->setPageSize($this->getItemLimit('upsell'));
        }

        $this->_itemCollection->load();
        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }
}