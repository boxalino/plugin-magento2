<?php

namespace Boxalino\Intelligence\Model;
/**
 * Class FilterList
 * @package Boxalino\Intelligence\Model
 */
class FilterList extends \Magento\Catalog\Model\Layer\FilterList {
    
    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    private $p13nHelper;
    
    /**
     * @var \BOxalino\Intelligence\Helper\Data
     */
    private $bxHelperData;

    /**
     * @var
     */
    private $bxFacets;

    /**
     * FilterList constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\Layer\FilterableAttributeListInterface $filterableAttributes
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \BOxalino\Intelligence\Helper\Data $bxHelperData
     * @param array $filters
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Layer\FilterableAttributeListInterface $filterableAttributes,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \BOxalino\Intelligence\Helper\Data $bxHelperData,
        array $filters = []
    )
    {
        parent::__construct($objectManager, $filterableAttributes, $filters);
        $this->bxHelperData = $bxHelperData;
        $this->p13nHelper = $p13nHelper;
    }

    /**
     * @param \Magento\Catalog\Model\Layer $layer
     * @return array|\Magento\Catalog\Model\Layer\Filter\AbstractFilter[]
     */
    public function getFilters(\Magento\Catalog\Model\Layer $layer){

        if($layer instanceof \Magento\Catalog\Model\Layer\Category\Interceptor && !$this->bxHelperData->isNavigationEnabled()){
            return parent::getFilters($layer);
        }
        $filters = array();
        if($this->bxHelperData->isFilterLayoutEnabled($layer instanceof \Magento\Catalog\Model\Layer\Category) && $this->bxHelperData->isLeftFilterEnabled()) {
            $facets = $this->getBxFacets();
            if($facets){
                foreach ($this->bxHelperData->getLeftFacetFieldNames() as $fieldName) {
                    $attribute = $this->objectManager->create("Magento\Catalog\Model\ResourceModel\Eav\Attribute");
                    $filter = $this->objectManager->create(
                        "Boxalino\Intelligence\Model\Attribute",
                        ['data' => ['attribute_model' => $attribute], 'layer' => $layer]
                    );

                    $filter->setFacets($facets);
                    $filter->setFieldName($fieldName);
                    $filters[] = $filter;
                }
            }
        }
        return $filters;
    }
    
    private function getBxFacets(){
        
        if($this->bxFacets == null){
            $this->bxFacets = $this->p13nHelper->getFacets();
        }
        return $this->bxFacets;
    }
}
