<?php
namespace Boxalino\Frontend\Block\Product;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Boxalino\Frontend\Helper\Data;
class BxListProducts extends ListProduct
{
    protected $collection;
    protected $p13nHelper;
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        CollectionFactory $collectionFactory,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        array $data = [])
    {
        $this->collection = $collectionFactory;
        $this->p13nHelper = $p13nHelper;
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
    }

    public function _getProductCollection()
    {
        /*if (Mage::getStoreConfig('Boxalino_General/general/enabled') == 0) {
            return parent::_getProductCollection();
        }*/

        // make sure to only use products which are in the current category
        /*if ($category = Mage::registry('current_category')) {
            if (!$category->getIsAnchor()) {
                return parent::_getProductCollection();
            }
        }*/

        $entity_ids = $this->p13nHelper->getEntitiesIds();
//        print_r($entity_ids);
        // Added check if there are any entity ids, otherwise force empty result
        if (count($entity_ids) == 0) {
            $entity_ids = array(0);
        }

        $list = $this->collection->create()->setStoreId($this->_storeManager->getStore()->getId())
            ->addFieldToFilter('entity_id', $entity_ids)->addAttributeToSelect('*');
        $list->getSelect()->order(new \Zend_Db_Expr('FIELD(e.entity_id,' . implode(',', $entity_ids).')'));
        $list->load();
        //Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_productCollection);

        return $list;
    }
}