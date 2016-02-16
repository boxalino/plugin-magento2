<?php
namespace Boxalino\Frontend\Block;
use Magento\CatalogSearch\Block\Result as Mage_Result;
use Boxalino\Frontend\Helper\Data as BxData;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\CatalogSearch\Helper\Data;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Search\Model\QueryFactory;
class Result extends Mage_Result
{

    protected $p13nHelper;
    protected $queryFactory;
    protected $bxListProducts;
    protected $queries;
    protected $phrase;
    public function __construct(
        Context $context,
        LayerResolver $layerResolver,
        Data $catalogSearchData,
        QueryFactory $queryFactory,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        array $data = [])
    {
        $this->p13nHelper = $p13nHelper;
        if($p13nHelper->areThereSubPhrases()){
            $this->queries = $p13nHelper->getSubPhrasesQueries();
        }
        $this->queryFactory = $queryFactory;
        parent::__construct($context, $layerResolver, $catalogSearchData, $queryFactory, $data);
    }
    public function getSubPhrasesResultText($index){
        return __("Search result for: '%1'", $this->queries[$index] );
    }

    public function getSearchQueryText()
    {
        if($this->p13nHelper->areResultsCorrected()){
            $query = $this->queryFactory->get();
            $query->setQueryText($this->p13nHelper->getCorrectedQuery());
            return __("Corrected search results for: '%1'", $this->catalogSearchData->getEscapedQueryText());
        } else if($this->p13nHelper->areThereSubPhrases()){
            return "";
        } else{
        return parent::getSearchQueryText();
        }
    }

    public function getSearchQueryLink($index){
        return $this->_storeManager->getStore()->getBaseUrl() . "catalogsearch/result/?q=" . $this->queries[$index];
    }

    protected function _prepareLayout()
    {
        if($this->hasSubPhrases()){
            $title = "Search result for: " . implode(", ",  $this->queries);
            $this->pageConfig->getTitle()->set($title);
            // add Home breadcrumb
            $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
            if ($breadcrumbs) {
                $breadcrumbs->addCrumb(
                    'home',
                    [
                        'label' => __('Home'),
                        'title' => __('Go to Home Page'),
                        'link' => $this->_storeManager->getStore()->getBaseUrl()
                    ]
                )->addCrumb(
                    'search',
                    ['label' => $title, 'title' => $title]
                );
            }
            return $this;
        }else{
            return parent::_prepareLayout();
        }
    }

    public function hasSubPhrases() {
        return $this->p13nHelper->areThereSubPhrases();
    }

    public function incrementCount(){
        $this->bxListProducts->incrementCount();
    }

    public function getProductListHtml()
    {
        return $this->getChildHtml('search_result_list', false);
    }

    /**
     * Retrieve search result count
     *
     * @return string
     */
//    public function getResultCount()
//    {
//        $size = $this->p13nHelper->getTotalHitCount();
//        $this->_getQuery()->setNumResults($size);
//        $this->setResultCount($size);
//        return $this->getData('result_count');
//    }
}