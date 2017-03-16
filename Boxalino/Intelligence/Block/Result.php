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
     * @var mixed
     */
    protected $queries = array();

    /**
     * @var BxHelperData
     */
    protected $bxHelperData;

    /**
     * @var null
     */
    protected $subPhrases = null;

    /**
     * @var bool
     */
    protected $fallback = false;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    
    /**
     * Result constructor.
     * @param Context $context
     * @param LayerResolver $layerResolver
     * @param Data $catalogSearchData
     * @param QueryFactory $queryFactory
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param BxHelperData $bxHelperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        LayerResolver $layerResolver,
        Data $catalogSearchData,
        QueryFactory $queryFactory,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        array $data = []
    )
    {
        $this->_logger = $context->getLogger();
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;

        try{
            if( $this->bxHelperData->isSearchEnabled()){
                if($this->hasSubPhrases()){
                    $this->queries =  $this->p13nHelper->getSubPhrasesQueries();
                }
            }else{
                $this->fallback = true;
            }
        }catch(\Exception $e){
            $this->fallback = true;
            $this->_logger->critical($e);
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

        if($this->fallback){
            return parent::getSearchQueryText();
        }
        if($this->bxHelperData->isSearchEnabled() && $this->p13nHelper->areResultsCorrected()){

            $correctedQuery = $this->p13nHelper->getCorrectedQuery();
            return __("Corrected search results for: '%1'", $correctedQuery);
        } else if($this->hasSubPhrases()){
            return "";
        } else{
            return parent::getSearchQueryText();
        }
    }

    /**
     * @param $index
     * @return string
     */
    public function getSearchQueryLink($index){

        return $this->getUrl('*/*', array('_current' => 'true', '_query' => array('q' => $this->queries[$index])));
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout(){

        if($this->hasSubPhrases()){
            $title = __("Search result for: '%1'", implode(" ",  $this->queries));
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

        if($this->fallback){
            return 0;
        }

        try{
            if($this->bxHelperData->isSearchEnabled()){
                if($this->subPhrases == null){
                    $this->subPhrases = $this->p13nHelper->areThereSubPhrases();
                }
                return $this->subPhrases;
            }
        }catch(\Exception $e){
            $this->fallback = true;
            $this->_logger->critical($e);
        }
        return 0;
    }

    /**
     * @return string
     */
    public function getProductListHtml(){
        
        return $this->getChildHtml('search_result_list', false);
    }

    /**
     * Retrieve search result count
     *
     * @return string
     */
    public function getResultCount()
    {
        if($this->fallback){
            return parent::getResultCount();
        }
        if (!$this->getData('result_count')) { 
            $query = $this->_getQuery();
            $size = $this->hasSubPhrases() ?
                $this->p13nHelper->getSubPhraseTotalHitCount(
                $this->queries[\Boxalino\Intelligence\Block\Product\BxListProducts::$number]) :
                $this->p13nHelper->getTotalHitCount();
            $this->setResultCount($size);
            $query->saveNumResults($size);
        }
        return $this->getData('result_count');
    }

    /**
     * @return bool
     */
    public function getFallback(){

        return $this->fallback;
    }
}