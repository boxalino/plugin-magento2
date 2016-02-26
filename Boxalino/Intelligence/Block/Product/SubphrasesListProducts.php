<?php
namespace Boxalino\Intelligence\Block\Product;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Boxalino\Intelligence\Helper\Data;
class SubphrasesListProducts extends ListProduct
{
    protected $collection;
    protected $p13nHelper;
    protected $queryFactory;
    protected $queries;
    protected STATIC $count = 0;
    protected $nextCollection = false;
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        CollectionFactory $collectionFactory,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Magento\Search\Model\QueryFactory $queryFactory,
        array $data = [])
    {
        $this->queries = $p13nHelper->getSubPhrasesQueries();
        $this->queryFactory = $queryFactory;
        $this->collection = $collectionFactory;
        $this->p13nHelper = $p13nHelper;
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
    }

    public function _getProductCollection()
    {
        if ($this->_productCollection === null || $this->nextCollection) {
            $entity_ids = $this->p13nHelper->getSubPhraseEntitiesIds($this->queries[$this::$count]);

            if (count($entity_ids) == 0) {
                $entity_ids = array(0);
            }

            $list = $this->collection->create()->setStoreId($this->_storeManager->getStore()->getId())
                ->addFieldToFilter('entity_id', $entity_ids)->addAttributeToSelect('*');
            $list->getSelect()->order(new \Zend_Db_Expr('FIELD(e.entity_id,' . implode(',', $entity_ids) . ')'));
            $list->load();
            $this->_productCollection = $list;
            if ($this::$count == $this->getQueriesCount() - 1) {
                $this::$count = 0;
            } else {
                $this::$count++;
            }
            $this->setNextCollection(false);
        }
        return $this->_productCollection;
    }

    public function getQueriesCount(){
        return count($this->queries);
    }
    public function getLoadedProductCollection()
    {
        return $this->_getProductCollection();
    }
    public function setNextCollection($bool){
        $this->nextCollection = $bool;
        $this->_getProductCollection();
        return $this;
    }

}