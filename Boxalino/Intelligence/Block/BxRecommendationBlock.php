<?php
namespace Boxalino\Intelligence\Block;

/**
 * Class BxRecommendationBlock
 * @package Boxalino\Intelligence\Block
 */
Class BxRecommendationBlock extends \Magento\Catalog\Block\Product\AbstractProduct implements \Magento\Framework\DataObject\IdentityInterface{

    /**
     * @var
     */
    protected $_itemCollection;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory
     */
    protected $factory;

    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    protected $p13nHelper;

    /**
     * @var array
     */
    protected $_data;

    /**
     * @var mixed
     */
    protected $othersWidgetConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $config;

    /**
     * @var string
     */
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_catalogProductVisibility;

    /**
     * @var \Magento\Cms\Model\Page
     */
    protected $cmsPage;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * @var bool
     */
    protected $isCmsPage;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * BxRecommendationBlock constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory
     * @param \Magento\Framework\App\Request\Http $request
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        \Magento\Framework\App\Request\Http $request,
        array $data
    )
    {
        $this->_logger = $context->getLogger();
        $this->bxHelperData = $bxHelperData;
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->factory = $factory;
        $this->registry = $context->getRegistry();
        $this->p13nHelper = $p13nHelper;
        $this->config = $context->getScopeConfig();
        $this->_checkoutSession = $checkoutSession;
        $this->_data = $data;
        $this->othersWidgetConfig = $this->config->getValue('bxRecommendations/others', $this->scopeStore);
        parent::__construct($context, $data);
    }

    /**
     * Recommendation setup
     */
    public function _construct(){

        try{
            if($this->bxHelperData->isSetup()){
                $cmsBlock = $this->bxHelperData->getCmsBlock();
                if($cmsBlock){
                    $recommendationBlocks = $this->getCmsRecommendationBlocks($cmsBlock);
                    $this->prepareRecommendations($recommendationBlocks);
                    $this->bxHelperData->setSetup(false);
                }else{
                    $this->prepareRecommendations(array($this->_data));
                }
            }
        }catch(\Exception $e){
            $this->bxHelperData->setFallback(true);
            $this->_logger->critical($e);
        }
    }

    /**
     * @param $content
     * @return array
     */
    protected function getCmsRecommendationBlocks($content){

        $results = array();
        $recommendations = array();
        preg_match_all("/\{\{(.*?)\}\}/",$content, $results);

        if(isset($results[1])){
            foreach($results[1] as $index => $result){
                if(strpos($result,'Boxalino\Intelligence') !== false){
                    preg_match_all("/[-^\s](.*?)\=\"(.*?)\"/",$result, $sectionResults);
                    $result_holder = array();
                    foreach($sectionResults[1] as $index => $sectionResult){
                        $result_holder[$sectionResult] = $sectionResults[2][$index];
                    }
                    $recommendations[] = $result_holder;
                }
            }
        }
        return $recommendations;
    }

    /**
     * @param array $recommendations
     * @return null
     */
    protected function prepareRecommendations($recommendations = array()){

        if($recommendations && is_array($recommendations)){
            foreach($recommendations as $index => $widget){

                try{
                    $recommendation = array();
                    $widgetConfig = $this->bxHelperData->getWidgetConfig($widget['widget']);
                    $recommendation['scenario'] = isset($widget['scenario']) ? $widget['scenario'] :
                        $widgetConfig['scenario'];
                    $recommendation['min'] = isset($widget['min']) ? $widget['min'] : $widgetConfig['min'];
                    $recommendation['max'] = isset($widget['max']) ? $widget['max'] : $widgetConfig['max'];

                    if (isset($widget['context'])) {
                        $recommendation['context'] = explode(',', str_replace(' ', '', $widget['context']));
                    } else {
                        $recommendation['context']  = $this->getWidgetContext($widgetConfig['scenario']);
                    }

                    $this->p13nHelper->getRecommendation(
                        $widget['widget'],
                        $recommendation['context'],
                        $recommendation['scenario'],
                        $recommendation['min'],
                        $recommendation['max'],
                        false
                    );
                }catch(\Exception $e){
                    $this->_logger->critical($e);
                }
            }
        }
        return null;
    }

    /**
     * @return $this
     */
    protected function _prepareData(){

        $context = isset($this->_data['context']) ? $this->_data['context'] : array();
        $entity_ids = array();
        try{
            $entity_ids = $this->p13nHelper->getRecommendation($this->_data['widget'], $context);
        }catch (\Exception $e){
            $this->bxHelperData->setFallback(true);
            $this->_logger->critical($e);
            return $this;
        }
    
        if ((count($entity_ids) == 0)) {
            $entity_ids = array(0);
        }

        $this->_itemCollection = $this->factory->create()
            ->addFieldToFilter('entity_id', $entity_ids)->addAttributeToSelect('*');
        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }

    /**
     * @param $scenario
     * @return array|mixed
     */
    protected function getWidgetContext($scenario){
        
        $context = array();
        switch($scenario){
            case 'category':
                if($this->registry->registry('current_category') != null){
                    $context = $this->registry->registry('current_category')->getId();
                }
                break;
            case 'product':
                if($this->_coreRegistry->registry('product') != null){
                    $context = $this->_coreRegistry->registry('product');
                }
                break;
            case 'basket':
                if($this->getQuote() != null){
                    foreach ($this->getQuote()->getAllItems() as $item) {
                        $product = $item->getProduct();
                        if ($product) {
                            $context[] = $product;
                        }
                    }
                }
                break;
            default:
                break;
        }
        return $context;
    }

    /**
     * @return mixed|string
     */
    public function getRecommendationTitle(){
        
        return isset($this->_data['title']) ? $this->_data['title'] : 'Recommendation';
    }
    
    /**
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote(){
        
        return $this->_checkoutSession->getQuote();
    }

    /**
     * @return mixed
     */
    public function getItems(){
        
        return $this->_itemCollection;
    }

    /**
     * @return $this
     */
    protected function _beforeToHtml(){
        
        $this->_prepareData();
        return parent::_beforeToHtml();
    }

    /**
     * @return array
     */
    public function getIdentities(){
        
        $identities = [];
        if($this->getItems() != null){
            foreach ($this->getItems() as $item) {
                $identities = array_merge($identities, $item->getIdentities());
            }
        }
        return $identities;
    }

    /**
     * @return bool
     */
    public function canItemsAddToCart(){
        
        foreach ($this->getItems() as $item) {
            if (!$item->isComposite() && $item->isSaleable() && !$item->getRequiredOptions()) {
                return true;
            }
        }
        return false;
    }
}