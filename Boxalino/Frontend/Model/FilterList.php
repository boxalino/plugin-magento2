<?php

namespace Boxalino\Frontend\Model;

class FilterList extends \Magento\Catalog\Model\Layer\FilterList {

    private $p13nHelper;
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Layer\FilterableAttributeListInterface $filterableAttributes,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        array $filters = []
    )
    {
        parent::__construct($objectManager, $filterableAttributes, $filters);
        $this->p13nHelper = $p13nHelper;
    }

    public function getFilters(\Magento\Catalog\Model\Layer $layer) {

        $filters = array();
        $facets = $this->p13nHelper->getFacets();
        foreach($this->p13nHelper->getLeftFacetFieldNames() as $fieldName) {
            $attribute = $this->objectManager->create("Magento\Catalog\Model\ResourceModel\Eav\Attribute");
            $filter = $this->objectManager->create(
                "Boxalino\Frontend\Model\Attribute",
                ['data' => ['attribute_model' => $attribute], 'layer' => $layer]
            );

            $filter->setFacets($facets);
            $filter->setFieldName($fieldName);
            $filters[] = $filter;
        }
		if(sizeof($filters) == 0) {
			return parent::getFilters();
		}
        return $filters;
    }
}
