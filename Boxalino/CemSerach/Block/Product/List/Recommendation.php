<?php
namespace Boxalino\CemSearch\Block\Product\List;
class Boxalino_CemSearch_Block_Product_List_Recommendation extends Mage_Catalog_Block_Product_Abstract
{
    protected $_itemCollection;
    protected $_recommendationName = '';
    protected $_recommendationAmount = 3;
    protected $_recommendationContext = array();
    protected $_recommendationParameterValues = array();

    public function configure($name, $amount = 3, $context = array(), $parameterValues = array())
    {
        $this->_recommendationName = $name;
        $this->_recommendationAmount = $amount;
        $this->_recommendationContext = $context;
        $this->_recommendationParameterValues = $parameterValues;
    }

    protected function _prepareData()
    {
        if (
            Mage::getStoreConfig('Boxalino_General/general/enabled') == 0 ||
            Mage::getStoreConfig('Boxalino_Recommendation/related/status', 0) == 0
        ) {
            return parent::_prepareData();
        }

        if (isset($this->_recommendationContext['id'])) {
            $_REQUEST['productId'] = $this->_recommendationContext['id'];
        } elseif (count($this->_recommendationContext)) {
            $_REQUEST['p13nRequestContext'] = $this->_recommendationContext;
            foreach($_REQUEST['p13nRequestContext'] as $k => $v) {
                if($v == '') {
                    switch($k) {
                        case 'id':
                            $_REQUEST['p13nRequestContext'][$k] = Mage::registry('product')->getId();
                            break;
                        case 'category':
                            $_REQUEST['p13nRequestContext'][$k] = Mage::registry('current_category');
                            break;
                        default:
                            if(isset($this->$_recommendationParameterValues[$k])) {
                                $_REQUEST['p13nRequestContext'][$k] = $this->_recommendationParameterValues[$k];
                            }
                    }
                }
            }
        }

        $p13nRecommendation = \Boxalino\CemSearch\Helper\P13n\Recommendation::Instance();

        $response = $p13nRecommendation->getRecommendation(
            'free', $this->_recommendationName, $this->_recommendationAmount
        );
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
            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter(
                $this->_itemCollection,
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

    protected function _beforeToHtml()
    {
        $this->_prepareData();
        return parent::_beforeToHtml();
    }

    public function getItems()
    {
        return $this->_itemCollection;
    }

    /**
     * Get tags array for saving cache
     *
     * @return array
     */
    public function getCacheTags()
    {
        return array_merge(parent::getCacheTags(), $this->getItemsTags($this->getItems()));
    }
}
