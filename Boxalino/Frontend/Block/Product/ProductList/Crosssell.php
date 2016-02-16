<?php
namespace Boxalino\Frontend\Block\Product\ProductList;
use Magento\Catalog\Block\Product\ProductList\Crosssell as MageCrosssell;

class Crosssell extends MageCrosssell{

    protected $p13nHelper;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        array $data=[]
    )
    {
        $this->p13nHelper = $p13nHelper;
        parent::__construct($context, $data);
    }

    protected function _prepareData()
    {

        $product = $this->_coreRegistry->registry('product');
        /* @var $product \Magento\Catalog\Model\Product */

        $this->_itemCollection = $product->getCrossSellProductCollection()->addAttributeToSelect(
            $this->_catalogConfig->getProductAttributes()
        )->setPositionOrder()->addStoreFilter();

        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }
}