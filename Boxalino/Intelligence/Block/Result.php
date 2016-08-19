<?php
namespace Boxalino\Intelligence\Block;
use Magento\CatalogSearch\Block\Result as Mage_Result;
use Boxalino\Intelligence\Helper\Data as BxHelperData;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\CatalogSearch\Helper\Data;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Search\Model\QueryFactory;

/**
 * Class Result
 * @package Boxalino\Intelligence\Block
 */
class Result extends Mage_Result{
    
    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    protected $p13nHelper;

    /**
     * @var
     */
    protected $bxListProducts;

    /**
     * @var mixed
     */
    protected $queries;

    /**
     * @var
     */
    protected $phrase;

    /**
     * @var BxHelperData
     */
    protected $bxHelperData;

    /**
     * @var null
     */
    protected $subPhrases = null;

    /**
     * Result constructor.
     * @param Context $context
     * @param LayerResolver $layerResolver
     * @param Data $catalogSearchData
     * @param QueryFactory $queryFactory
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param BxHelperData $bxHelperData
     * @param \Magento\Framework\App\Action\Context $actionContext
     * @param array $data
     */
    public function __construct(
        Context $context,
        LayerResolver $layerResolver,
        Data $catalogSearchData,
        QueryFactory $queryFactory,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Framework\App\Action\Context $actionContext,
        array $data = []
    )
    {
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;
        if($this->bxHelperData->isSearchEnabled() && $this->hasSubPhrases()){
            $this->queries = $p13nHelper->getSubPhrasesQueries();
            if(count($this->queries) < 2){
                $url = $actionContext->getUrl()->getCurrentUrl();
                $replace = '?q=' . $this->queries[0];
                $url = substr_replace ($url, $replace, strpos($url, '?'));
                $actionContext->getResponse()->setRedirect($url);
            }
        }
        
        parent::__construct($context, $layerResolver, $catalogSearchData, $queryFactory, $data);
    }

    /**
     * @param $index
     * @return \Magento\Framework\Phrase
     */
    public function getSubPhrasesResultText($index){

        return __("Search result for: '%1'", $this->queries[$index] );
    }

    /**
     * @return int
     */
    public function getSubPhrasesResultCount() {
        return sizeof($this->queries);
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getSearchQueryText(){

        if($this->bxHelperData->isSearchEnabled() && $this->p13nHelper->areResultsCorrected()){
            
            $correctedQuery = $this->p13nHelper->getCorrectedQuery();
            return __("Corrected search results for: '%1'", $correctedQuery);
        } else if($this->hasSubPhrases()){
            return "";
        }
        return parent::getSearchQueryText();
    }

    /**
     * @param $index
     * @return string
     */
    public function getSearchQueryLink($index){

        return $this->_storeManager->getStore()->getBaseUrl() . "catalogsearch/result/?q=" . $this->queries[$index];
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout(){

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
        }
        return parent::_prepareLayout();
    }

    /**
     * @return int|mixed
     */
    public function hasSubPhrases(){
        
        if($this->bxHelperData->isSearchEnabled()){
            if($this->subPhrases == null){
                $this->subPhrases = $this->p13nHelper->areThereSubPhrases();
            }
            return $this->subPhrases;
        }
        return 0;
    }

    /**
     * @return string
     */
    public function getProductListHtml(){
        
        return $this->getChildHtml('search_result_list', false);
    }
}