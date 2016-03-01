<?php

namespace Boxalino\Intelligence\Model;

class Attribute extends \Magento\Catalog\Model\Layer\Filter\Attribute {

    private $bxFacets = null;
    private $fieldName = array();
    private $bxDataHelper;
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory $filterAttributeFactory,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Filter\StripTags $tagFilter,
        \Boxalino\Intelligence\Helper\Data $bxDataHelper,
        array $data=[])
    {
        $this->bxDataHelper = $bxDataHelper;
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $filterAttributeFactory, $string, $tagFilter, $data);
    }

    public function setFacets($bxFacets) {
        $this->bxFacets = $bxFacets;
    }

    public function setFieldName($fieldName) {
        $this->fieldName = $fieldName;
    }

    public function getName()
    {
        return $this->bxFacets->getFacetLabel($this->fieldName);
    }

    protected function _getItemsData()
    {
        $this->_requestVar = $this->bxFacets->getFacetParameterName($this->fieldName);
        if(!$this->bxDataHelper->isHierarchical($this->fieldName)){
            foreach($this->bxFacets->getFacetValues($this->fieldName) as $facetValue) {
                if($this->bxFacets->getSelectedValues($this->fieldName) && $this->bxFacets->getSelectedValues($this->fieldName)[0] == $facetValue){
                    $value = $this->bxFacets->getSelectedValues($this->fieldName)[0] == $facetValue ? true : false;
                    $this->itemDataBuilder->addItemData(
                        $this->tagFilter->filter($this->bxFacets->getFacetValueLabel($this->fieldName, $facetValue)),
                        0,
                        $this->bxFacets->getFacetValueCount($this->fieldName, $facetValue),
                        $value,
                        'flat'
                    );
                }else{
                    $value = false;
                    $this->itemDataBuilder->addItemData(
                        $this->tagFilter->filter($this->bxFacets->getFacetValueLabel($this->fieldName, $facetValue)),
                        $this->bxFacets->getFacetValueParameterValue($this->fieldName, $facetValue),
                        $this->bxFacets->getFacetValueCount($this->fieldName, $facetValue),
                        $value,
                        'flat'
                    );
                }
            }
        }else{
            $count = 1;
            $parentCount = count($this->bxFacets->getParentCategories());
            $value = false;
            foreach($this->bxFacets->getParentCategories() as $key => $facetvalue) {
                if($count == 1){
                    $count++;
                    continue;
                }
                if($count == 2){
                    $count++;
                    $this->itemDataBuilder->addItemData(
                        $this->tagFilter->filter("Home"),
                        2,
                        $this->bxFacets->getParentCategoriesHitCount(1),
                        $value,
                        'home parent'
                    );
                    continue;
                }
                if($parentCount == $count++){
                    $value = true;
                }
                $this->itemDataBuilder->addItemData(
                    $this->tagFilter->filter($facetvalue),
                    $key,
                    $this->bxFacets->getParentCategoriesHitCount($key),
                    $value,
                    'parent'
                );
            }
            foreach($this->bxFacets->getCategories() as $facetValue) {
                $this->itemDataBuilder->addItemData(
                    $this->tagFilter->filter($this->bxFacets->getFacetValueLabel($this->fieldName, $facetValue)),
                    $this->bxFacets->getFacetValueParameterValue($this->fieldName, $facetValue),
                    $this->bxFacets->getFacetValueCount($this->fieldName, $facetValue),
                    false,
                    $value ? 'children' : 'home children'
                );
            }

        }
        return $this->itemDataBuilder->build();
    }
}
