<?php
namespace Boxalino\Frontend\Block\Product;

class Boxalino_Frontend_Block_Product_List extends \Magento\Catalog\Block\Product\ListProduct
{

    protected $scopeConfig;
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    protected $registry;
    protected $p13nHelper;
    protected $catalogCollection;
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $registry,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        \Magento\Catalog\Model\ResourceModel\Category\Collection $catalogCollection
    )
    {
        $this->catalogCollection = $catalogCollection;
        $this->p13nHelper =$p13nHelper;
        $this->scopeConfig = $scopeConfig;
        $this->registry = $registry;
    }

    /**
     * Retrieve loaded category collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected function _getProductCollection()
    {
        $registry = $this->registry;
        $config = $this->scopeConfig;

        if ($config->getValue('Boxalino_General/general/enabled', $this->scopeStore) == 0) {
            return parent::_getProductCollection();
        }

        // make sure to only use products which are in the current category
        if ($category = $registry->registry('current_category')) {
            if (!$category->getIsAnchor()) {
                return parent::_getProductCollection();
            }
        }

        if (is_null($this->_productCollection)) {
            $entity_ids = $this->p13nHelper->getEntitiesIds();

            $this->_productCollection = $this->catalogCollection;

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
