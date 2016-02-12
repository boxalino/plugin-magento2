<?php

namespace Boxalino\Frontend\Model;

class FilterList extends \Magento\Catalog\Model\Layer\FilterList {

    private $p13nHelper;
    private $scopeConfig;
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Layer\FilterableAttributeListInterface $filterableAttributes,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        array $filters = []
    )
    {
        parent::__construct($objectManager, $filterableAttributes, $filters);
        $this->p13nHelper = $p13nHelper;
        $this->scopeConfig = $scopeConfig;
    }

    public function getFilters(\Magento\Catalog\Model\Layer $layer) {

        $filters = array();
        $facets = $this->p13nHelper->getFacets();
        $fieldNames = explode(',', $this->scopeConfig->getValue('bxSearch/facets/left_filters_normal',$this->scopeStore));
        $normalFilters = array();
        $count = 0;
        foreach($fieldNames as $fieldName){
            $temp = explode(':', $fieldName);
            $normalFilters[$count++] = $temp[0];
        }

        foreach($normalFilters as $fieldName) {
            $attribute = $this->objectManager->create("Magento\Catalog\Model\ResourceModel\Eav\Attribute");
            $filter = $this->objectManager->create(
                "Boxalino\Frontend\Model\Attribute",
                ['data' => ['attribute_model' => $attribute], 'layer' => $layer]
            );

            $filter->setFacets($facets);
            $filter->setFieldName($fieldName);
            $filters[] = $filter;
        }
        return $filters;
    }
}
