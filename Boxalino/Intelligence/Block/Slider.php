<?php
namespace Boxalino\Intelligence\Block;

class Slider extends \Magento\Framework\View\Element\Template
{

    protected $p13nHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        array $data = []
    )
    {
        $this->p13nHelper = $p13nHelper;
        parent::__construct($context, $data);
    }

    private function explodePrice($price)
    {
        return explode("-", $price);
    }

    // returns array with price ranges
    public function getSliderValues()
    {
        $facets = $this->p13nHelper->getFacets();
        if (empty($facets) || empty($facets->getFacetValues($facets->getPriceFieldName()))) {
            return null;
        }
        $priceRange = $this->explodePrice($facets->getFacetValues($facets->getPriceFieldName())[0]);
        $selectedPrice = $this->getRequest()->getParam('bx_discountedPrice') !== null ?
            $this->explodePrice($this->getRequest()->getParam('bx_discountedPrice')) : $priceRange;
        return array_merge($selectedPrice, $priceRange);
    }
}?>