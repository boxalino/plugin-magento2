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
    protected $widgetName;

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
    protected $recommendationCollection;

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
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        \Magento\Framework\Registry $registry,
        array $data)
    {
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->factory = $factory;
        $this->registry = $registry;
        $this->p13nHelper = $p13nHelper;
        $this->config = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->widgetName = $data['widget'];
        $this->othersWidgetConfig = $this->config->getValue('bxRecommendations/others', $this->scopeStore);
        parent::__construct($context, $data);
        $this->_prepareData(false);
    }

    /**
     * @return $this
     */
    protected function _prepareData($execute=true){
        $widgetNames = explode(',',$this->othersWidgetConfig['widget']);

        if(in_array($this->widgetName,$widgetNames)){
            $index = array_search($this->widgetName,$widgetNames);
            $types = explode(',',$this->othersWidgetConfig['scenario']);
            $min = explode(',',$this->othersWidgetConfig['min']);
            $max = explode(',',$this->othersWidgetConfig['max']);
            $context = array();

            switch($types[$index]){
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
            
            $recommendations = $this->p13nHelper->getRecommendation(
                $types[$index],
                $this->widgetName,
                $min[$index],
                $max[$index],
                $context,
                $execute
            );

            $entity_ids = $recommendations;
            
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
            $this->recommendationCollection[$this->widgetName] = $this->_itemCollection;
        }
        return $this;
    }

    /**
     * @param $choiceId
     * @return itemCollection for choice Id
     */
    public function getRecommendation($choiceId){
        if($choiceId!= null){
            return $this->recommendationCollection[$choiceId];
        }
        return null;
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