<?php
namespace Boxalino\Intelligence\Block;

class Slider extends \Magento\Framework\View\Element\Template
{

    protected $p13nHelper;
    protected $bxHelperData;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        array $data = []
    )
    {
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;
        parent::__construct($context, $data);
    }

    private function explodePrice($price)
    {
        return explode("-", $price);
    }

    // returns array with price ranges
    public function getSliderValues()
    {
        if($this->bxHelperData->isLeftFilterEnabled()) {
            $facets = $this->p13nHelper->getFacets();
            if (empty($facets) || empty($facets->getFacetValues($facets->getPriceFieldName()))) {
                return null;
            }
            $priceRange = $this->explodePrice($facets->getPriceRanges()[0]);
            $selectedPrice = $this->getRequest()->getParam('bx_discountedPrice') !== null ?
                $this->explodePrice($this->getRequest()->getParam('bx_discountedPrice')) : $priceRange;
            if($priceRange[0] == $priceRange[1]){
                $priceRange[1]++;
            }
            return array_merge($selectedPrice, $priceRange);
        }
        return array();
    }
}?>