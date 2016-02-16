<?php
namespace Boxalino\Frontend\Block\Product\ProductList;
use Magento\Catalog\Block\Product\ProductList\Upsell as  MageUpsell;
class Upsell extends MageUpsell
{

    protected $p13nHelper;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Checkout\Model\ResourceModel\Cart $checkoutCart,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Module\Manager $moduleManager,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        array $data = []
    )
    {
        $this->p13nHelper = $p13nHelper;
        parent::__construct($context, $checkoutCart, $catalogProductVisibility, $checkoutSession, $moduleManager, $data);
    }

//    protected function _prepareData()
//    {
//
//        $product = $this->_coreRegistry->registry('product');
//        /* @var $product \Magento\Catalog\Model\Product */
////        $this->_itemCollection->addFieldToFilter('entity_id', )
//        if ($this->moduleManager->isEnabled('Magento_Checkout')) {
//            $this->_addProductAttributesAndPrices($this->_itemCollection);
//        }
//        $this->_itemCollection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
//
//        $this->_itemCollection->load();
//
//        /**
//         * Updating collection with desired items
//         */
//        $this->_eventManager->dispatch(
//            'catalog_product_upsell',
//            ['product' => $product, 'collection' => $this->_itemCollection, 'limit' => null]
//        );
//
//        foreach ($this->_itemCollection as $product) {
//            $product->setDoNotUseCategoryId(true);
//        }
//
//        return $this;
//    }

}