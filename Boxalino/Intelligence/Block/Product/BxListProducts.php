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

class BxListProducts extends ListProduct
{
    public static $number = 0;
    protected $count = -1;
    protected $p13nHelper;
    protected $queries;
    protected $_objectManager;
    protected $abstractAction;
    protected $urlFactory;
    protected $bxHelperData;
    protected $request;
    protected $categoryHelper;
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    protected $categoryFactory;
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\App\Action\AbstractAction $abstractAction,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\UrlFactory $urlFactory,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = [])
    {
        $this->categoryFactory = $categoryFactory;
        $this->categoryHelper = $categoryHelper;
        $this->request = $request;
        $this->bxHelperData = $bxHelperData;
        $this->p13nHelper = $p13nHelper;
        $this->urlFactory = $urlFactory;
        $this->abstractAction = $abstractAction;
        $this->_objectManager = $objectManager;
        $this->p13nHelper = $p13nHelper;
        if($this->bxHelperData->isSearchEnabled() && $p13nHelper->areThereSubPhrases()){
            $this->queries = $p13nHelper->getSubPhrasesQueries();
        }
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
    }


    public function _getProductCollection()
    {
        if(!$this->bxHelperData->isSearchEnabled()){
           return parent::_getProductCollection();
        }
        $layer = $this->getLayer();
        if($layer instanceof \Magento\Catalog\Model\Layer\Category\Interceptor || $layer instanceof \Magento\Catalog\Model\Layer\Search\Interceptor ){
            if(!$this->bxHelperData->isNavigationEnabled() && $layer instanceof \Magento\Catalog\Model\Layer\Category\Interceptor){
                return parent::_getProductCollection();
            }
//            print_r($this->request->getParams());
            if($this->request->getParam('bx_category_id') && $layer instanceof \Magento\Catalog\Model\Layer\Category\Interceptor) {
                $selectedCategory = $this->categoryFactory->create()->load($this->request->getParam('bx_category_id'));
                if($selectedCategory->getLevel() != 1){
                    $url = $selectedCategory->getUrl();
                    $bxParams = $this->checkForBxParams($this->request->getParams());
                    $this->abstractAction->getResponse()->setRedirect($url . $bxParams);
                }
            }

            if($this->p13nHelper->areThereSubPhrases()) {
                $entity_ids = array_slice($this->p13nHelper->getSubPhraseEntitiesIds($this->queries[self::$number]), 0, $this->_scopeConfig->getValue('bxSearch/advanced/limit',$this->scopeStore));

            }else{
                $entity_ids = $this->p13nHelper->getEntitiesIds();
            }

            // Added check if there are any entity ids, otherwise force empty result
            if ((count($entity_ids) == 0)) {
                $entity_ids = array(0);
            }

            $list = $this->_objectManager->create('\\Boxalino\\Intelligence\\Model\\Collection');
            $list->setStoreId($this->_storeManager->getStore()->getId())
                ->addFieldToFilter('entity_id', $entity_ids)->addAttributeToSelect('*');
            $list->getSelect()->order(new \Zend_Db_Expr('FIELD(e.entity_id,' . implode(',', $entity_ids).')'));
            $list->load();
            $list->setCurBxPage($this->getToolbarBlock()->getCurrentPage());
            $limit = $this->getRequest()->getParam('product_list_limit') ? $this->getRequest()->getParam('product_list_limit') : $this->getToolbarBlock()->getDefaultPerPageValue();
            $totalHitCount = $this->p13nHelper->getTotalHitCount();

            if((ceil($totalHitCount / $limit) < $list->getCurPage()) && $this->getRequest()->getParam('p')){
                $url = $this->urlFactory->create()->getCurrentUrl();
                $url = preg_replace('/(\&|\?)p=+(\d|\z)/','$1p=1',$url);
                $this->abstractAction->getResponse()->setRedirect($url);
            }
            $lastPage = ceil($totalHitCount /$limit);
            $list->setLastBxPage($lastPage);
            $list->setBxTotal($totalHitCount);
            return $list;
        }else{
            return parent::_getProductCollection();
        }
    }

    private function checkForBxParams($params){
        $paramString = '';
        $first = true;
        foreach($params as $key => $param){
            if(preg_match("/^bx_/",$key)){
                if(preg_match("/^bx_category_id/",$key)){
                    continue;
                }
                if($first) {
                    $paramString = '?';
                    $first = false;
                }else{
                    $paramString = $paramString . "&";
                }
                $paramString = $paramString . $key . '=' . $param;
            }
        }
        return $paramString;
    }

    protected function _beforeToHtml()
    {
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

        return parent::_beforeToHtml();
    }
}