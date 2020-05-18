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
class Result extends Mage_Result
{

    /**
     * @var \Boxalino\Intelligence\Api\P13nAdapterInterface
     */
    protected $p13nHelper;

    /**
     * @var mixed
     */
    protected $queries = [];

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
     * @param \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper
     * @param BxHelperData $bxHelperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        LayerResolver $layerResolver,
        Data $catalogSearchData,
        QueryFactory $queryFactory,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        array $data = []
    )
    {
        $this->_logger = $context->getLogger();
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;

        try{
            if($this->bxHelperData->isSearchEnabled() && $this->bxHelperData->isPluginEnabled()){
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
    public function getSubPhrasesResultText($index)
    {
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
    public function getSearchQueryText()
    {
        if($this->fallback){
            return parent::getSearchQueryText();
        }

        if($this->bxHelperData->isSearchEnabled())
        {
            $queryText = $this->p13nHelper->getQueryText();
            if($this->p13nHelper->areResultsCorrected())
            {
                $correctedQuery = $this->p13nHelper->getCorrectedQuery();
                return __("Corrected search results for: '%1'", $correctedQuery);
            } else if($this->hasSubPhrases())
            {
                return "";
            } else if($this->bxHelperData->isEmptySearchEnabled() && empty($queryText))
            {
                $pageTitle = $this->bxHelperData->getEmptySearchPageTitle();
                $pageTitle .= " (%1)";
                $productCount = $this->getResultCount();

                return __($pageTitle, $productCount);
            }
        }

        return parent::getSearchQueryText();
    }

    /**
     * @param $index
     * @return string
     */
    public function getSearchQueryLink($index)
    {
        return $this->getUrl('*/*', array('_current' => 'true', '_query' => array(QueryFactory::QUERY_VAR_NAME => $this->queries[$index])));
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

    public function getBlogTotalHitCount(){
        return $this->p13nHelper->getBlogTotalHitCount();
    }

    /**
     * @return bool
     */
    public function getFallback(){
        return $this->fallback;
    }

    public function isBlogSearchActive() {
        return $this->bxHelperData->isBlogEnabled();
    }

    public function setTemplate($template) {
        if($this->bxHelperData->isSearchEnabled()){
            return parent::setTemplate('Boxalino_Intelligence::result.phtml');
        }
        return parent::setTemplate($template);
    }

    /**
     * @return bool
     */
    public function showNoResults()
    {
        return $this->bxHelperData->isNoResultsEnabled();
    }

    /**
     * @return string
     */
    public function getNoResultsWidgetName()
    {
        return $this->bxHelperData->getNoResultsWidgetName();
    }

    /**
     * @return string|null
     */
    public function getRequestUuid()
    {
        return $this->p13nHelper->getRequestUuid();
    }

    /**
     * @return string|null
     */
    public function getRequestGroupBy()
    {
        return $this->p13nHelper->getRequestGroupBy();
    }

}
