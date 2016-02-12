<?php

namespace Boxalino\Frontend\Model;

class Attribute extends \Magento\Catalog\Model\Layer\Filter\Attribute {

    private $bxFacets = null;
    private $fieldName = array();

    public function setFacets($bxFacets) {
        $this->bxFacets = $bxFacets;
    }

    public function setFieldName($fieldName) {
        $this->fieldName = $fieldName;
    }

    public function getName()
    {
        return $this->fieldName;
    }

    protected function _getItemsData()
    {
        $this->_requestVar = $this->bxFacets->getFacetParameterName($this->fieldName);
        foreach($this->bxFacets->getFacetValues($this->fieldName) as $facetValue) {
            $this->itemDataBuilder->addItemData(
                $this->tagFilter->filter($facetValue),
                $this->bxFacets->getFacetValueParameterValue($this->fieldName, $facetValue),
                $this->bxFacets->getFacetValueCount($this->fieldName, $facetValue)
            );
        }

        return $this->itemDataBuilder->build();
    }
}
