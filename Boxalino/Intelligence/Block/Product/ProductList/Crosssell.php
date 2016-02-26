<?php
namespace Boxalino\Intelligence\Block\Product\ProductList;
use Magento\Catalog\Block\Product\ProductList\Crosssell as MageCrosssell;

class Crosssell extends MageCrosssell{

    protected $p13nHelper;
    protected $factory;
    protected $bxHelperData;
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
    }

    protected function _prepareData()
    {
        if($this->bxHelperData->isCrosssellEnabled()){
            $products = $this->_coreRegistry->registry('product');

            $config = $this->_scopeConfig->getValue('bxRecommendations/cart',$this->scopeStore);

            $choiceId = (isset($config['widget']) && $config['widget'] != "") ? $config['widget'] : 'related';

            $recommendations = $this->p13nHelper->getRecommendation(
                'basket',
                $choiceId,
                $config['min'],
                $config['max'],
                $products
            );

            $entity_ids = array_keys($recommendations);

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