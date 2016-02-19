<?php
namespace Boxalino\Frontend\Block\Product\ProductList;
use Magento\Catalog\Block\Product\ProductList\Related as MageRelated;
class Related extends MageRelated
{

    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    protected $p13nHelper;
    protected $factory;
    protected $bxHelperData;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Checkout\Model\ResourceModel\Cart $checkoutCart,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Module\Manager $moduleManager,
        \Boxalino\Frontend\Helper\Data $bxHelperData,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        array $data = []
    )
    {
        $this->bxHelperData = $bxHelperData;
        $this->factory = $factory;
        $this->p13nHelper = $p13nHelper;
        parent::__construct($context, $checkoutCart, $catalogProductVisibility, $checkoutSession, $moduleManager, $data);
    }

    protected function _prepareData()
    {
        if($this->bxHelperData->isRelatedEnabled()){
            $products = array($this->_coreRegistry->registry('product'));

            $config = $this->_scopeConfig->getValue('bxRecommendations/related',$this->scopeStore);

            $choiceId = (isset($config['widget']) && $config['widget'] != "") ? $config['widget'] : 'related';

            $recommendations = $this->p13nHelper->getRecommendation(
                'product',
                $choiceId,
                $config['min'],
                $config['max'],
                $products
            );

            $entity_ids = array_keys($recommendations);

            $this->_itemCollection = $this->factory->create()
                ->addFieldToFilter('entity_id', $entity_ids)->addAttributeToSelect('*');

            if ($this->moduleManager->isEnabled('Magento_Checkout')) {
                $this->_addProductAttributesAndPrices($this->_itemCollection);
            }
            $this->_itemCollection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());

            $this->_itemCollection->load();

            foreach ($this->_itemCollection as $product) {
                $product->setDoNotUseCategoryId(true);
            }
            return $this;
        }
        return parent::_prepareData();
    }
}