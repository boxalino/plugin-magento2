<?php
namespace Boxalino\Intelligence\Block\Product\ProductList;
use Magento\Catalog\Block\Product\ProductList\Crosssell as MageCrosssell;

/**
 * Class Crosssell
 * @package Boxalino\Intelligence\Block\Product\ProductList
 */
class Crosssell extends MageCrosssell{

    /**
     * @var string
     */
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    
    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    protected $p13nHelper;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory
     */
    protected $factory;
    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * Crosssell constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        array $data=[]
    )
    {
        $this->bxHelperData = $bxHelperData;
        $this->factory = $factory;
        $this->p13nHelper = $p13nHelper;
        parent::__construct($context, $data);
        $this->_prepareData(false);
    }

    /**
     * @return $this|MageCrosssell
     */
    protected function _prepareData($execute = true){
        
        if($this->bxHelperData->isCrosssellEnabled()){
            $products = $this->_coreRegistry->registry('product');
            $config = $this->_scopeConfig->getValue('bxRecommendations/cart',$this->scopeStore);
            $choiceId = (isset($config['widget']) && $config['widget'] != "") ? $config['widget'] : 'complementary';

            try{
                $entity_ids = $this->p13nHelper->getRecommendation(
                    $choiceId,
                    $products,
                    'basket',
                    $config['min'],
                    $config['max'],
                    $execute
                );  
            }catch(\Exception $e){
                $this->bxHelperData->setFallback(true);
                $this->_logger->critical($e);
                return parent::_prepareData();
            }
            
            if(!$execute){
                return null;
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
        return parent::_prepareData();
    }
}