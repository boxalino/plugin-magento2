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
use Boxalino\Intelligence\Helper\Data;
use Magento\Framework\Session\SessionManager;
use Magento\Catalog\Model\Product\ProductList\Toolbar;
use Magento\UrlRewrite\Helper\UrlRewrite;

/**
 * Class BxListProducts
 * @package Boxalino\Intelligence\Block\Product
 */
class BxListProducts extends ListProduct
{

    /**
     * @var int
     */
    public static $number = 0;

    /**
     * @var \Boxalino\Intelligence\Api\P13nAdapterInterface
     */
    protected $p13nHelper;

    /**
     * @var mixed
     */
    protected $queries;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var Data
     */
    protected $bxHelperData;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $categoryHelper;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Catalog\Block\Category\View
     */
    protected $categoryViewBlock;

    /**
     * @var \Boxalino\Intelligence\Model\Collection
     */
    protected $bxListCollection;

    /**
     * @var \Magento\Framework\App\Action\Action
     */
    protected $_response;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * BxListProducts constructor.
     * @param Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param Data $bxHelperData
     * @param \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper
     * @param \Magento\Framework\App\Action\Context $actionContext
     * @param \Magento\Framework\UrlFactory $urlFactory
     * @param \Magento\Catalog\Helper\Category $categoryHelper
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Block\Category\View $categoryViewBlock
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Magento\Framework\App\Action\Context $actionContext,
        \Magento\Framework\UrlFactory $urlFactory,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Block\Category\View $categoryViewBlock,
        array $data = []
    )
    {
        $this->_logger = $context->getLogger();
        $this->_response = $actionContext->getResponse();
        $this->categoryViewBlock = $categoryViewBlock;
        $this->categoryFactory = $categoryFactory;
        $this->categoryHelper = $categoryHelper;
        $this->bxHelperData = $bxHelperData;
        $this->p13nHelper = $p13nHelper;
        $this->_url = $actionContext->getUrl();
        $this->_objectManager = $actionContext->getObjectManager();
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
    }

    /**
     * @return AbstractCollection|mixed
     */
    public function _getProductCollection(){

        try{
            $layer = $this->getLayer();
            if($this->bxHelperData->isEnabledOnLayer($layer) && $this->bxHelperData->isPluginEnabled()){
                if(!empty($this->_productCollection) && count($this->_productCollection) && !$this->p13nHelper->areThereSubPhrases()){
                    return $this->_productCollection;
                }

                if($layer instanceof \Magento\Catalog\Model\Layer\Category){
                    if(!is_null($this->categoryViewBlock->getCurrentCategory()) && $this->categoryViewBlock->isContentMode()){
                        $this->bxHelperData->setFallback(true);
                        return parent::_getProductCollection();
                    }
                }

                if($this->p13nHelper->areThereSubPhrases()){
                    $this->queries = $this->p13nHelper->getSubPhrasesQueries();
                    $entity_ids = $this->p13nHelper->getSubPhraseEntitiesIds($this->queries[self::$number]);
                    $entity_ids = array_slice($entity_ids, 0, $this->bxHelperData->getSubPhrasesLimit());
                }else{
                    $entity_ids = $this->p13nHelper->getEntitiesIds();
                }

                if (empty($entity_ids)) {
                    $entity_ids = [0];
                }
                $this->_setupCollection($entity_ids);
                return $this->_productCollection;
            }else{
                return parent::_getProductCollection();
            }
        }catch(\Exception $e){
            $this->bxHelperData->setFallback(true);
            $this->_logger->critical($e);
            return parent::_getProductCollection();
        }
    }

    /**
     * @param $entity_ids
     */
    protected function _setupCollection($entity_ids)
    {
        $list = $this->_objectManager->create('\\Boxalino\\Intelligence\\Model\\Collection');
        $list = $this->bxHelperData->prepareProductCollection($list, $entity_ids);
        $list->setStoreId($this->_storeManager->getStore()->getId())->addAttributeToSelect('*');
        $list->load();

        $list->setCurBxPage($this->getToolbarBlock()->getCurrentPage());
        $limit = $this->getRequest()->getParam('product_list_limit')
            ? $this->getRequest()->getParam('product_list_limit')
            : $this->getToolbarBlock()->getDefaultPerPageValue();

        $totalHitCount = $this->p13nHelper->getTotalHitCount();
        if((ceil($totalHitCount / $limit) < $list->getCurPage()) && $this->getRequest()->getParam('p')){

            $url = $this->_url->getCurrentUrl();
            $url = preg_replace('/(\&|\?)p=+(\d|\z)/','$1p=1',$url);
            $this->_response->setRedirect($url);
        }

        $lastPage = ceil($totalHitCount /$limit);
        $list->setLastBxPage($lastPage);
        $list->setBxTotal($totalHitCount);
        $this->_productCollection = $list;
    }

    /**
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $layer = $this->getLayer();
        if($this->bxHelperData->isEnabledOnLayer($layer) && $this->bxHelperData->isPluginEnabled()) {
            $toolbar = $this->getToolbarBlock();

            // called prepare sortable parameters

            $collection = $this->_getProductCollection();

            // use sortable parameters
            $orders = $this->getAvailableOrders();
            if ($orders) {
                $toolbar->setAvailableOrders($orders);
            }

            $toolbar->setDefaultOrder('relevance');

            $dir = $this->getDefaultDirection();
            if ($dir) {
                $toolbar->setDefaultDirection($dir);
            }

            $modes = $this->getModes();
            if ($modes) {
                $toolbar->setModes($modes);
            }

            // set collection to toolbar and apply sort
            $toolbar->setCollection($collection);
            $this->setChild('toolbar', $toolbar);
            $this->_eventManager->dispatch(
                'catalog_block_product_list_collection',
                ['collection' => $this->_getProductCollection()]
            );

            $this->_getProductCollection()->load();
        }
        return parent::_beforeToHtml();
    }

    /**
     * @return string|null
     */
    public function getRequestUuid()
    {
        if($this->bxHelperData->isEnabledOnLayer($this->getLayer()) && $this->bxHelperData->isPluginEnabled())
        {
            if(in_array(
                get_class($this->getLayer()),
                ['Magento\Catalog\Model\Layer\Search\Interceptor', 'Magento\Catalog\Model\Layer\Search'])
            ){
                return $this->p13nHelper->getRequestUuid("search");
            }

            return $this->p13nHelper->getRequestUuid("navigation");
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getRequestGroupBy()
    {
        if($this->bxHelperData->isEnabledOnLayer($this->getLayer()) && $this->bxHelperData->isPluginEnabled())
        {
            return $this->p13nHelper->getRequestGroupBy();
        }

        return null;
    }

}
