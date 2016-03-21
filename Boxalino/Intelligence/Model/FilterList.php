<?php

namespace Boxalino\Intelligence\Model;

class FilterList extends \Magento\Catalog\Model\Layer\FilterList {

    private $p13nHelper;
    private $bxHelperData;
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

    public function getFilters(\Magento\Catalog\Model\Layer $layer)
    {
        if($this->bxHelperData->isLeftFilterEnabled()) {
            $filters = array();
            $facets = $this->p13nHelper->getFacets();
            foreach ($this->p13nHelper->getLeftFacetFieldNames() as $fieldName) {
                $attribute = $this->objectManager->create("Magento\Catalog\Model\ResourceModel\Eav\Attribute");
                $filter = $this->objectManager->create(
                    "Boxalino\Intelligence\Model\Attribute",
                    ['data' => ['attribute_model' => $attribute], 'layer' => $layer]
                );

                $filter->setFacets($facets);
                $filter->setFieldName($fieldName);
                $filters[] = $filter;
            }
            return $filters;

        }

        return array();
    }
}
