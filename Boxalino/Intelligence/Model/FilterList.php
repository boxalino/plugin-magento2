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
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * FilterList constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\Layer\FilterableAttributeListInterface $filterableAttributes
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \BOxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $filters
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Layer\FilterableAttributeListInterface $filterableAttributes,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \BOxalino\Intelligence\Helper\Data $bxHelperData,
        \Psr\Log\LoggerInterface $logger,
        array $filters = []
    )
    {
        parent::__construct($objectManager, $filterableAttributes, $filters);
        $this->_logger = $logger;
        $this->bxHelperData = $bxHelperData;
        $this->p13nHelper = $p13nHelper;
    }

    /**
     * @param \Magento\Catalog\Model\Layer $layer
     * @return array|\Magento\Catalog\Model\Layer\Filter\AbstractFilter[]
     */
    public function getFilters(\Magento\Catalog\Model\Layer $layer){

        try {
            if ($this->bxHelperData->isEnabledOnLayer($layer)) {
                $filters = array();
                $facets = $this->getBxFacets();
                if ($facets) {
                    foreach ($facets->getLeftFacets() as $fieldName) {
                        if ($facets->getFacetCoverage($fieldName) <= 0) continue;
                        $attribute = $this->objectManager->create("Magento\Catalog\Model\ResourceModel\Eav\Attribute");
                        $filter = $this->objectManager->create(
                            "Boxalino\Intelligence\Model\Attribute",
                            ['data' => ['attribute_model' => $attribute], 'layer' => $layer]
                        );

                        $filter->setFacets($facets);
                        $filter->setFieldName($fieldName);
                        $filters[] = $filter;
                        $filter = null;
                    }
                } else {
                    $this->p13nHelper->notifyWarning(["message"=>"BxFacets is not defined in " . get_class($this),
                        "stacktrace"=>$this->bxHelperData->notificationTrace()]);
                }
                return $filters;
            }else{
                return parent::getFilters($layer);
            }
        }catch(\Exception $e){
            $this->bxHelperData->setFallback(true);
            $this->_logger->critical($e);
            return parent::getFilters($layer);
        }
    }
    
    private function getBxFacets(){
        if($this->bxFacets == null){
            $this->bxFacets = $this->p13nHelper->getFacets();
        }
        return $this->bxFacets;
    }
}
