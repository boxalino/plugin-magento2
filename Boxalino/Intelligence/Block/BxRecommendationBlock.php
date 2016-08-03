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
     * @var mixed
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
     * @var
     */
    protected $cmsPage;

    /**
     * @var
     */
    protected $bxHelperData;

    /**
     * @var
     */
    protected $isCmsPage;
    
    /**
     * BxRecommendationBlock constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Cms\Model\Page $cmsPage,
        array $data)
    {
        $this->isCmsPage = $request->getModuleName() == 'cms' ? true : false;
        $this->cmsPage = $cmsPage;
        $this->bxHelperData = $bxHelperData;
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->factory = $factory;
        $this->registry = $registry;
        $this->p13nHelper = $p13nHelper;
        $this->config = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_data = $data;
        $this->othersWidgetConfig = $this->config->getValue('bxRecommendations/others', $this->scopeStore);
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        if($this->bxHelperData->isSetup() && $this->isCmsPage){
            $recommendationBlocks = $this->getCmsRecommendationBlocks();
            $this->prepareRecommendations($recommendationBlocks);
            $this->bxHelperData->setSetup(false);
        }elseif(!$this->isCmsPage){
            $this->prepareRecommendations(array($this->_data));
        }
    }

    protected function getCmsRecommendationBlocks(){
        $results = array();
        $recommendations = array();
        preg_match_all("/\{\{(.*?)\}\}/",$this->cmsPage->getContent(), $results);

        if(isset($results[1])){
            foreach($results[1] as $index => $result){
                if(strpos($result,'Boxalino\Intelligence')){
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

    protected function prepareRecommendations($recommendations = array()){
        $otherWidgetConfiguration = $this->bxHelperData->getOtherWidgetConfiguration();
        if($recommendations){
            foreach($recommendations as $index => $widget){

                if(isset($otherWidgetConfiguration[$widget['widget']])){
                    $config = $otherWidgetConfiguration[$widget['widget']];
                    $context = isset($widget['context']) ? $widget['context'] :
                        $this->getWidgetContext($config['scenario']);

                    $this->p13nHelper->getRecommendation(
                        $widget['widget'],
                        $config['scenario'],
                        $config['min'],
                        $config['max'],
                        $context,
                        false
                    );
                }
            }
        }
        return null;
    }

    /**
     * @return $this
     */
    protected function _prepareData(){

        $entity_ids = $this->p13nHelper->getRecommendation($this->_data['widget']);
        
        if ((count($entity_ids) == 0)) {
            $entity_ids = array(0);
        }

        $this->_itemCollection = $this->factory->create()
            ->addFieldToFilter('entity_id', $entity_ids)->addAttributeToSelect('*');

        $this->_itemCollection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());

        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }

    /**
     * @param $scenario
     * @return array
     */
    protected function getWidgetContext($scenario){
        $context = array();
        switch($scenario){
            case 'category':
                if($this->registry->registry('current_category') != null){
                    $context[] = $this->registry->registry('current_category')->getId();
                }
                break;
            case 'product':
                if($this->_coreRegistry->registry('product') != null){
                    $context = array($this->_coreRegistry->registry('product'));
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
        $this->_prepareData(true);
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