<?php

namespace Boxalino\Intelligence\Block;

use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;

/**
 * Class State
 * @package Boxalino\Intelligence\Block
 */
class State extends \Magento\Catalog\Model\Layer\State{
    
    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    private $p13nHelper;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
	private $objectManager;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    private $bxHelperData;

    /**
     * @var \Magento\Catalog\Model\Layer
     */
    private $_layer;

    /**
     * State constructor.
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     */
	public function __construct(
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
		array $data = []
    )
    {
        $this->_layer = $layerResolver->get();
        $this->bxHelperData = $bxHelperData;
        $this->_data = $data;
        $this->p13nHelper = $p13nHelper;
        $this->objectManager = $objectManager;
        parent::__construct($data);
    }

    /**
     * Get applied to layer filter items
     *
     * @return Item[]
     */
    public function getFilters(){
        
        if($this->bxHelperData->isFilterLayoutEnabled($this->_layer instanceof \Magento\Catalog\Model\Layer\Category)) {

            $filters = array();
            $facets = $this->p13nHelper->getFacets();
            if ($facets) {
                foreach ($this->bxHelperData->getAllFacetFieldNames() as $fieldName) {

                    if ($facets->isSelected($fieldName)) {
                        $filter = $this->objectManager->create(
                            "Boxalino\Intelligence\Model\LayerFilterItem"
                        );

                        $filter->setFacets($facets);
                        $filter->setFieldName($fieldName);
                        $filter->setClearLinkUrl("abc");
                        $filters[] = $filter;
                    }
                }
            }
            return $filters;
        }
        return parent::getFilters();
    }
}
