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
use Magento\Framework\Session\SessionManager;
class BxListProducts extends ListProduct
{
    public static $number = 0;
    protected $count = -1;
    protected $collection;
    protected $p13nHelper;
    protected $queryFactory;
    protected $queries;
    protected $session;
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        CollectionFactory $collectionFactory,
        \Magento\Search\Model\QueryFactory $queryFactory,
        SessionManager $session,
        array $data = [])
    {
        $this->p13nHelper = $p13nHelper;
        if($p13nHelper->areThereSubPhrases()){
            $this->queries = $p13nHelper->getSubPhrasesQueries();
        }
        $this->queryFactory = $queryFactory;
        $this->collection = $collectionFactory;
        $this->p13nHelper = $p13nHelper;
        $this->session = $session;
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
    }

    public function _getProductCollection()
    {
        if($this->p13nHelper->areThereSubPhrases()) {
            $entity_ids = array_slice($this->p13nHelper->getSubPhraseEntitiesIds($this->queries[self::$number]), 0, $this->_scopeConfig->getValue('bxSearch/advanced/limit',$this->scopeStore));

        }else{
            $entity_ids = $this->p13nHelper->getEntitiesIds();

        }

        // Added check if there are any entity ids, otherwise force empty result
        if ((count($entity_ids) == 0)) {
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