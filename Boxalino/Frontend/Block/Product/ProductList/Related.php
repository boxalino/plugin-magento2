<?php
namespace Boxalino\Frontend\Block\Product\ProductList;
use Magento\Catalog\Block\Product\ProductList\Related as MageRelated;
class Related extends MageRelated
{

    protected $p13nHelper;
    protected $factory;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Checkout\Model\ResourceModel\Cart $checkoutCart,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        array $data = []
    )
    {
        $this->factory = $factory;
        $this->p13nHelper = $p13nHelper;
        parent::__construct($context, $checkoutCart, $catalogProductVisibility, $checkoutSession, $moduleManager, $data);
    }

    protected function _prepareData()
    {
        $this->p13nHelper->search('pack');
        $entity_ids = $this->p13nHelper->getEntitiesIds();

        $product = $this->_coreRegistry->registry('product');
//        $product->setRelatedProductIds($entity_ids);

//        $this->factory->create()->
        /* @var $product \Magento\Catalog\Model\Product */
        $this->_itemCollection = $this->factory->create()
            ->addFieldToFilter('entity_id', $entity_ids)->addAttributeToSelect('*')
            ->setPositionOrder();

//        $this->_itemCollection = $product->getRelatedProductCollection()->addFieldToFilter('entity_id', $entity_ids)->addAttributeToSelect(
//            '*'
//        )->setPositionOrder()->addStoreFilter();

//        $this->_itemCollection = $product->getRelatedProductCollection()->addAttributeToSelect(
//            '*'
//        )->setPositionOrder()->addStoreFilter();

        if ($this->moduleManager->isEnabled('Magento_Checkout')) {
            $this->_addProductAttributesAndPrices($this->_itemCollection);
        }
        $this->_itemCollection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());

        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
            $product->setHasOptions(true);
            $product->setRequiredOptions(true);

        }

        return $this;
    }
    public function canItemsAddToCart()
    {
        return false;
    }
}